<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Exports\SurveyTypeExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreSurveyTypeRequest;
use App\Http\Requests\Master\UpdateSurveyTypeRequest;
use App\Imports\SurveyTypeImport;
use App\Models\SurveyType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SurveyTypeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', SurveyType::class);

        return view('master.jenis-survei.index');
    }

    public function create(): View
    {
        Gate::authorize('create', SurveyType::class);

        return view('master.jenis-survei.create');
    }

    public function store(StoreSurveyTypeRequest $request): RedirectResponse
    {
        Gate::authorize('create', SurveyType::class);

        DB::transaction(function () use ($request): void {
            SurveyType::create([
                'code' => $request->string('code')->toString(),
                'name' => $request->string('name')->toString(),
                'description' => $request->input('description'),
                'is_active' => $request->boolean('is_active', true),
            ]);
        });

        return redirect()->route('master.jenis-survei.index');
    }

    public function edit(SurveyType $surveyType): View
    {
        Gate::authorize('update', $surveyType);

        return view('master.jenis-survei.edit', ['type' => $surveyType]);
    }

    public function update(UpdateSurveyTypeRequest $request, SurveyType $surveyType): RedirectResponse
    {
        Gate::authorize('update', $surveyType);

        DB::transaction(function () use ($request, $surveyType): void {
            $surveyType->update([
                'name' => $request->string('name')->toString(),
                'description' => $request->input('description'),
                'is_active' => $request->boolean('is_active', $surveyType->is_active),
            ]);
        });

        return redirect()->route('master.jenis-survei.index');
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', SurveyType::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string'],
        ]);

        return Excel::download(new SurveyTypeExport($filters), 'survey-types.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('create', SurveyType::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120'],
        ]);

        $import = new SurveyTypeImport;
        Excel::import($import, $request->file('file'));

        return redirect()->route('master.jenis-survei.index')->with('status', __('Impor selesai: :n data baru.', ['n' => $import->imported]));
    }
}
