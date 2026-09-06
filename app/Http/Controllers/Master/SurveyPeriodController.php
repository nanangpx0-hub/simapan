<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Actions\Master\ActivateSurveyPeriod;
use App\Actions\Master\ArchiveSurveyPeriod;
use App\Actions\Master\CloseSurveyPeriod;
use App\Exports\SurveyPeriodExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreSurveyPeriodRequest;
use App\Http\Requests\Master\UpdateSurveyPeriodRequest;
use App\Imports\SurveyPeriodImport;
use App\Models\SurveyPeriod;
use App\Models\SurveyType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SurveyPeriodController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', SurveyPeriod::class);

        return view('master.periode-survei.index');
    }

    public function create(): View
    {
        Gate::authorize('create', SurveyPeriod::class);

        $types = SurveyType::query()->where('is_active', true)->orderBy('code')->get();

        return view('master.periode-survei.create', [
            'types' => $types,
            'periodTypes' => SurveyPeriod::PERIOD_TYPES,
        ]);
    }

    public function store(StoreSurveyPeriodRequest $request): RedirectResponse
    {
        Gate::authorize('create', SurveyPeriod::class);

        DB::transaction(function () use ($request): void {
            SurveyPeriod::create([
                'code' => $request->string('code')->toString(),
                'survey_type_id' => (int) $request->input('survey_type_id'),
                'name' => $request->string('name')->toString(),
                'period_type' => $request->string('period_type')->toString(),
                'period_number' => $request->input('period_number') !== null ? (int) $request->input('period_number') : null,
                'year' => (int) $request->input('year'),
                'start_date' => $request->date('start_date'),
                'end_date' => $request->date('end_date'),
                'status' => $request->string('status', 'DRAFT')->toString(),
                'created_by' => $request->user()->getKey(),
            ]);
        });

        return redirect()->route('master.survey_periods.index');
    }

    public function show(SurveyPeriod $surveyPeriod): View
    {
        Gate::authorize('view', $surveyPeriod);

        return view('master.periode-survei.show', [
            'period' => $surveyPeriod->load(['surveyType', 'creator', 'closer']),
        ]);
    }

    public function edit(SurveyPeriod $surveyPeriod): View
    {
        Gate::authorize('update', $surveyPeriod);

        return view('master.periode-survei.edit', ['period' => $surveyPeriod->load('surveyType')]);
    }

    public function update(UpdateSurveyPeriodRequest $request, SurveyPeriod $surveyPeriod): RedirectResponse
    {
        Gate::authorize('update', $surveyPeriod);

        DB::transaction(function () use ($request, $surveyPeriod): void {
            $surveyPeriod->update([
                'name' => $request->string('name')->toString(),
                'start_date' => $request->date('start_date'),
                'end_date' => $request->date('end_date'),
            ]);
        });

        return redirect()->route('master.survey_periods.index');
    }

    public function destroy(SurveyPeriod $surveyPeriod): RedirectResponse
    {
        Gate::authorize('delete', $surveyPeriod);

        if ($surveyPeriod->allocations()->exists()) {
            return redirect()->route('master.survey_periods.index')
                ->with('error', __('Tidak dapat menghapus periode survei karena masih terhubung dengan alokasi.'));
        }

        DB::transaction(function () use ($surveyPeriod): void {
            $surveyPeriod->delete();
        });

        return redirect()->route('master.survey_periods.index')
            ->with('status', __('Periode survei berhasil dihapus.'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', SurveyPeriod::class);

        $filters = $request->validate([
            'survey_type_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
            'year' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        return Excel::download(new SurveyPeriodExport($filters), 'survey-periods.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('create', SurveyPeriod::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120'],
        ]);

        $import = new SurveyPeriodImport;
        Excel::import($import, $request->file('file'));

        return redirect()->route('master.survey_periods.index')->with('status', __('Impor selesai: :n data baru.', ['n' => $import->imported]));
    }

    public function activate(Request $request, SurveyPeriod $surveyPeriod, ActivateSurveyPeriod $action): RedirectResponse
    {
        Gate::authorize('update', $surveyPeriod);

        $action->handle($surveyPeriod, $request->user());

        return redirect()->route('master.survey_periods.show', $surveyPeriod);
    }

    public function close(Request $request, SurveyPeriod $surveyPeriod, CloseSurveyPeriod $action): RedirectResponse
    {
        Gate::authorize('update', $surveyPeriod);

        $action->handle($surveyPeriod, $request->user());

        return redirect()->route('master.survey_periods.show', $surveyPeriod);
    }

    public function archive(Request $request, SurveyPeriod $surveyPeriod, ArchiveSurveyPeriod $action): RedirectResponse
    {
        Gate::authorize('update', $surveyPeriod);

        $action->handle($surveyPeriod, $request->user());

        return redirect()->route('master.survey_periods.show', $surveyPeriod);
    }
}
