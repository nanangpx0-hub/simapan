<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Exports\OfficerExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreOfficerRequest;
use App\Http\Requests\Master\UpdateOfficerRequest;
use App\Imports\OfficerImport;
use App\Models\Officer;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OfficerController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Officer::class);

        return view('master.petugas.index');
    }

    public function create(): View
    {
        Gate::authorize('create', Officer::class);

        $units = WorkUnit::query()->where('is_active', true)->orderBy('code')->get();
        $users = User::query()->orderBy('name')->get();

        return view('master.petugas.create', ['units' => $units, 'users' => $users]);
    }

    public function store(StoreOfficerRequest $request): RedirectResponse
    {
        Gate::authorize('create', Officer::class);

        DB::transaction(function () use ($request): void {
            Officer::create([
                'code' => $request->string('code')->toString(),
                'name' => $request->string('name')->toString(),
                'work_unit_id' => (int) $request->input('work_unit_id'),
                'user_id' => $request->input('user_id') !== null ? (int) $request->input('user_id') : null,
                'phone' => $request->input('phone'),
                'email' => $request->input('email'),
                'status' => $request->string('status', 'ACTIVE')->toString(),
                'active_from' => $request->date('active_from'),
                'active_until' => $request->date('active_until'),
            ]);
        });

        return redirect()->route('master.officers.index');
    }

    public function show(Officer $officer): View
    {
        Gate::authorize('view', $officer);

        return view('master.petugas.show', [
            'officer' => $officer->load(['workUnit', 'user', 'aliases.creator']),
        ]);
    }

    public function edit(Officer $officer): View
    {
        Gate::authorize('update', $officer);

        $units = WorkUnit::query()
            ->where(function ($query) use ($officer): void {
                $query->where('is_active', true);

                if ($officer->work_unit_id !== null) {
                    $query->orWhere('id', $officer->work_unit_id);
                }
            })
            ->orderBy('code')
            ->get();
        $users = User::query()->orderBy('name')->get();

        return view('master.petugas.edit', ['officer' => $officer->load('workUnit'), 'units' => $units, 'users' => $users]);
    }

    public function update(UpdateOfficerRequest $request, Officer $officer): RedirectResponse
    {
        Gate::authorize('update', $officer);

        DB::transaction(function () use ($request, $officer): void {
            $officer->update([
                'name' => $request->string('name')->toString(),
                'work_unit_id' => (int) $request->input('work_unit_id'),
                'user_id' => $request->input('user_id') !== null ? (int) $request->input('user_id') : null,
                'phone' => $request->input('phone'),
                'email' => $request->input('email'),
                'status' => $request->string('status', $officer->status)->toString(),
                'active_from' => $request->date('active_from'),
                'active_until' => $request->date('active_until'),
            ]);
        });

        return redirect()->route('master.officers.index');
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Officer::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string'],
            'work_unit_id' => ['nullable', 'integer'],
        ]);

        return Excel::download(new OfficerExport($filters), 'officers.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('create', Officer::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120'],
        ]);

        $import = new OfficerImport;
        Excel::import($import, $request->file('file'));

        return redirect()->route('master.officers.index')->with('status', __('Impor selesai: :n data baru.', ['n' => $import->imported]));
    }
}
