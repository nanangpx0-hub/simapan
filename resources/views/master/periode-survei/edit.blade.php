<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ubah periode survei') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4 text-sm text-gray-600">{{ __('Hanya periode DRAFT yang dapat diubah. Kode, jenis, tipe, nomor, dan tahun terkunci.') }}</p>
                    <form method="POST" action="{{ route('master.survey_periods.update', $period) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <span class="block text-sm font-medium">{{ __('Kode / Jenis / Tipe / Nomor / Tahun (terkunci)') }}</span>
                            <p class="mt-1 text-sm">{{ $period->code }} — {{ $period->surveyType->code }} — {{ $period->period_type }}{{ $period->period_number !== null ? ' '.$period->period_number : '' }} — {{ $period->year }}</p>
                        </div>
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium">{{ __('Nama') }}</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $period->name) }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="start_date" class="block text-sm font-medium">{{ __('Tanggal mulai') }}</label>
                            <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $period->start_date->format('Y-m-d')) }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="end_date" class="block text-sm font-medium">{{ __('Tanggal selesai') }}</label>
                            <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $period->end_date->format('Y-m-d')) }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        @if ($errors->any())
                            <ul class="mb-4 text-sm text-red-600">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <button type="submit" class="underline">{{ __('Simpan') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
