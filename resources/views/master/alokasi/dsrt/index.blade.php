<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('DSRT: :nks', ['nks' => $allocation->nks]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4 text-sm text-gray-600">{{ __('Periode: :code — Desa: :desa', ['code' => $allocation->period->code, 'desa' => $allocation->village->full_code]) }}</p>
                    <div class="flex justify-between items-center mb-4">
                        <a href="{{ route('allocations.show', $allocation) }}" class="underline">{{ __('Kembali ke alokasi') }}</a>
                        <a href="{{ route('allocations.dsrt.create', $allocation) }}" class="underline">{{ __('Tambah sampel') }}</a>
                    </div>
                    <form method="GET" action="{{ route('allocations.dsrt.index', $allocation) }}" class="mb-4 flex flex-wrap gap-2 text-sm">
                        <select name="record_status" class="border rounded px-2 py-1">
                            <option value="">{{ __('Semua status') }}</option>
                            @foreach ($recordStatuses as $status)
                                <option value="{{ $status }}" @selected(($filters['record_status'] ?? '') === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                        <select name="enumeration_status" class="border rounded px-2 py-1">
                            <option value="">{{ __('Semua pencacahan') }}</option>
                            @foreach ($enumerationStatuses as $status)
                                <option value="{{ $status }}" @selected(($filters['enumeration_status'] ?? '') === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                        <input name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Cari NUS/NURT/nama') }}" class="border rounded px-2 py-1" />
                        <button type="submit" class="underline">{{ __('Filter') }}</button>
                    </form>
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('NUS') }}</th>
                                <th class="py-2">{{ __('NURT') }}</th>
                                <th class="py-2">{{ __('KK/Bangunan/RT') }}</th>
                                <th class="py-2">{{ __('Nama KRT') }}</th>
                                <th class="py-2">{{ __('Pendidikan') }}</th>
                                <th class="py-2">{{ __('Cacah') }}</th>
                                <th class="py-2">{{ __('Record') }}</th>
                                <th class="py-2">{{ __('Kontak') }}</th>
                                <th class="py-2">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($samples as $sample)
                                <tr class="border-b">
                                    <td class="py-2">{{ $sample->nus }}</td>
                                    <td class="py-2">{{ $sample->nurt }}</td>
                                    <td class="py-2">{{ $sample->family_number ?? '—' }} / {{ $sample->building_number ?? '—' }} / {{ $sample->household_number ?? '—' }}</td>
                                    <td class="py-2">{{ $sample->krt_name }}</td>
                                    <td class="py-2">{{ $sample->krt_education_code ?? '—' }}</td>
                                    <td class="py-2">{{ $sample->enumeration_status }}</td>
                                    <td class="py-2">{{ $sample->record_status }}</td>
                                    <td class="py-2">{{ $sample->maskedContactPhone() }}</td>
                                    <td class="py-2">
                                        <a href="{{ route('allocations.dsrt.show', [$allocation, $sample]) }}" class="underline">{{ __('Detail') }}</a>
                                        <a href="{{ route('allocations.dsrt.edit', [$allocation, $sample]) }}" class="underline ms-2">{{ __('Ubah') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $samples->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
