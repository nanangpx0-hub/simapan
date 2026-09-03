<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Periode Survei') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <p>{{ __('Daftar periode survei.') }}</p>
                        <a href="{{ route('master.survey_periods.create') }}" class="underline">{{ __('Tambah periode') }}</a>
                    </div>
                    <form method="GET" action="{{ route('master.survey_periods.index') }}" class="mb-4 flex flex-wrap gap-2 text-sm">
                        <select name="survey_type_id" class="border rounded px-2 py-1">
                            <option value="">{{ __('Semua jenis') }}</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->id }}" @selected((string) ($filters['survey_type_id'] ?? '') === (string) $type->id)>{{ $type->code }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="border rounded px-2 py-1">
                            <option value="">{{ __('Semua status') }}</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                        <input name="year" type="number" value="{{ $filters['year'] ?? '' }}" placeholder="{{ __('Tahun') }}" class="border rounded px-2 py-1 w-28" />
                        <button type="submit" class="underline">{{ __('Filter') }}</button>
                    </form>
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Kode') }}</th>
                                <th class="py-2">{{ __('Jenis') }}</th>
                                <th class="py-2">{{ __('Nama') }}</th>
                                <th class="py-2">{{ __('Tipe/No') }}</th>
                                <th class="py-2">{{ __('Tahun') }}</th>
                                <th class="py-2">{{ __('Periode') }}</th>
                                <th class="py-2">{{ __('Status') }}</th>
                                <th class="py-2">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($periods as $period)
                                <tr class="border-b">
                                    <td class="py-2">{{ $period->code }}</td>
                                    <td class="py-2">{{ $period->surveyType->code }}</td>
                                    <td class="py-2">{{ $period->name }}</td>
                                    <td class="py-2">{{ $period->period_type }}{{ $period->period_number !== null ? ' '.$period->period_number : '' }}</td>
                                    <td class="py-2">{{ $period->year }}</td>
                                    <td class="py-2">{{ $period->start_date->format('Y-m-d') }} — {{ $period->end_date->format('Y-m-d') }}</td>
                                    <td class="py-2">{{ $period->status }}</td>
                                    <td class="py-2">
                                        <a href="{{ route('master.survey_periods.show', $period) }}" class="underline">{{ __('Detail') }}</a>
                                        <a href="{{ route('master.survey_periods.edit', $period) }}" class="underline ms-2">{{ __('Ubah') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $periods->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
