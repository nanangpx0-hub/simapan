<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Alokasi Kegiatan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <p>{{ __('Daftar alokasi kegiatan Susenas/Seruti.') }}</p>
                        @can('allocation.manage')
                            <a href="{{ route('allocations.create') }}" class="underline">{{ __('Tambah alokasi') }}</a>
                        @endcan
                    </div>
                    <form method="GET" action="{{ route('allocations.index') }}" class="mb-4 flex flex-wrap gap-2 text-sm">
                        <select name="survey_period_id" class="border rounded px-2 py-1">
                            <option value="">{{ __('Semua periode') }}</option>
                            @foreach ($periods as $period)
                                <option value="{{ $period->id }}" @selected((string) ($filters['survey_period_id'] ?? '') === (string) $period->id)>{{ $period->code }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="border rounded px-2 py-1">
                            <option value="">{{ __('Semua status') }}</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                        <select name="village_region_id" class="border rounded px-2 py-1">
                            <option value="">{{ __('Semua desa') }}</option>
                            @foreach ($villages as $village)
                                <option value="{{ $village->id }}" @selected((string) ($filters['village_region_id'] ?? '') === (string) $village->id)>{{ $village->full_code }}</option>
                            @endforeach
                        </select>
                        <input name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Cari NKS/nama SLS') }}" class="border rounded px-2 py-1" />
                        <button type="submit" class="underline">{{ __('Filter') }}</button>
                    </form>
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('NKS') }}</th>
                                <th class="py-2">{{ __('Periode') }}</th>
                                <th class="py-2">{{ __('Jenis') }}</th>
                                <th class="py-2">{{ __('Desa') }}</th>
                                <th class="py-2">{{ __('SLS/Sub') }}</th>
                                <th class="py-2">{{ __('Status') }}</th>
                                @foreach ($roles as $role)
                                    <th class="py-2">{{ $role }}</th>
                                @endforeach
                                <th class="py-2">{{ __('Dibuat') }}</th>
                                <th class="py-2">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($allocations as $allocation)
                                <tr class="border-b">
                                    <td class="py-2">{{ $allocation->nks }}</td>
                                    <td class="py-2">{{ $allocation->period->code }}</td>
                                    <td class="py-2">{{ $allocation->period->surveyType->code }}</td>
                                    <td class="py-2">{{ $allocation->village->full_code }}</td>
                                    <td class="py-2">{{ $allocation->sls_code ?? '—' }}{{ $allocation->sub_sls_code ? '/'.$allocation->sub_sls_code : '' }}</td>
                                    <td class="py-2">{{ $allocation->status }}</td>
                                    @foreach ($roles as $role)
                                        <td class="py-2">{{ $allocation->activeAssignments->firstWhere('assignment_role', $role)?->officer->code ?? '—' }}</td>
                                    @endforeach
                                    <td class="py-2">{{ $allocation->created_at }}</td>
                                    <td class="py-2">
                                        <a href="{{ route('allocations.show', $allocation) }}" class="underline">{{ __('Detail') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $allocations->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
