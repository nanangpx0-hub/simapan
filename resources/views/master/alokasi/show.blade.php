<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detail alokasi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <dl class="text-sm space-y-2">
                        <div><dt class="font-medium inline">{{ __('NKS:') }}</dt> <dd class="inline">{{ $allocation->nks }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Periode:') }}</dt> <dd class="inline">{{ $allocation->period->code }} ({{ $allocation->period->status }})</dd></div>
                        <div><dt class="font-medium inline">{{ __('Jenis survei:') }}</dt> <dd class="inline">{{ $allocation->period->surveyType->code }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Desa:') }}</dt> <dd class="inline">{{ $allocation->village->full_code }} — {{ $allocation->village->name }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('SLS/Sub-SLS:') }}</dt> <dd class="inline">{{ $allocation->sls_code ?? '—' }}{{ $allocation->sub_sls_code ? '/'.$allocation->sub_sls_code : '' }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Nama SLS:') }}</dt> <dd class="inline">{{ $allocation->sls_name }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Status:') }}</dt> <dd class="inline">{{ $allocation->status }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Catatan:') }}</dt> <dd class="inline">{{ $allocation->notes ?? '—' }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Dibuat oleh:') }}</dt> <dd class="inline">{{ $allocation->creator->name ?? '—' }}</dd></div>
                    </dl>
                    <div class="mt-4 flex flex-wrap gap-4 text-sm">
                        @can('allocation.manage')
                            @if (in_array($allocation->status, ['DRAFT', 'ACTIVE'], true))
                                <a href="{{ route('allocations.edit', $allocation) }}" class="underline">{{ __('Ubah') }}</a>
                            @endif
                        @endcan
                        @can('allocation.assign')
                            <a href="{{ route('allocations.assignments.index', $allocation) }}" class="underline">{{ __('Kelola penugasan') }}</a>
                        @endcan
                        @can('allocation.manage')
                        @if ($allocation->status === 'DRAFT')
                            <form method="POST" action="{{ route('allocations.activate', $allocation) }}">
                                @csrf
                                <button type="submit" class="underline">{{ __('Aktifkan') }}</button>
                            </form>
                            <form method="POST" action="{{ route('allocations.archive', $allocation) }}">
                                @csrf
                                <button type="submit" class="underline">{{ __('Arsipkan') }}</button>
                            </form>
                        @endif
                        @if ($allocation->status === 'ACTIVE')
                            <form method="POST" action="{{ route('allocations.suspend', $allocation) }}">
                                @csrf
                                <button type="submit" class="underline">{{ __('Tangguhkan') }}</button>
                            </form>
                            <form method="POST" action="{{ route('allocations.complete', $allocation) }}">
                                @csrf
                                <button type="submit" class="underline">{{ __('Selesaikan') }}</button>
                            </form>
                        @endif
                        @if ($allocation->status === 'SUSPENDED')
                            <form method="POST" action="{{ route('allocations.resume', $allocation) }}">
                                @csrf
                                <button type="submit" class="underline">{{ __('Lanjutkan') }}</button>
                            </form>
                            <form method="POST" action="{{ route('allocations.archive', $allocation) }}">
                                @csrf
                                <button type="submit" class="underline">{{ __('Arsipkan') }}</button>
                            </form>
                        @endif
                        @if ($allocation->status === 'COMPLETED')
                            <form method="POST" action="{{ route('allocations.archive', $allocation) }}">
                                @csrf
                                <button type="submit" class="underline">{{ __('Arsipkan') }}</button>
                            </form>
                        @endif
                        @endcan
                    </div>
                    @if ($errors->any())
                        <ul class="mt-4 text-sm text-red-600">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <h3 class="mt-6 font-semibold">{{ __('Penugasan aktif') }}</h3>
                    <ul class="mt-2 text-sm space-y-1">
                        @foreach ($allocation->activeAssignments as $assignment)
                            <li>{{ $assignment->assignment_role }}: {{ $assignment->officer->code }} — {{ $assignment->officer->name }}</li>
                        @endforeach
                    </ul>
                    <h3 class="mt-4 font-semibold">{{ __('Riwayat penugasan') }}</h3>
                    <table class="mt-2 w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Role') }}</th>
                                <th class="py-2">{{ __('Petugas') }}</th>
                                <th class="py-2">{{ __('Kategori') }}</th>
                                <th class="py-2">{{ __('Aktif') }}</th>
                                <th class="py-2">{{ __('Mulai') }}</th>
                                <th class="py-2">{{ __('Selesai') }}</th>
                                <th class="py-2">{{ __('Oleh') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($allocation->assignments as $assignment)
                                <tr class="border-b">
                                    <td class="py-2">{{ $assignment->assignment_role }}</td>
                                    <td class="py-2">{{ $assignment->officer->code }}</td>
                                    <td class="py-2">{{ $assignment->employment_category ?? '—' }}</td>
                                    <td class="py-2">{{ $assignment->is_active ? __('Ya') : __('Tidak') }}</td>
                                    <td class="py-2">{{ $assignment->started_at }}</td>
                                    <td class="py-2">{{ $assignment->ended_at ?? '—' }}</td>
                                    <td class="py-2">{{ $assignment->assigner->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
