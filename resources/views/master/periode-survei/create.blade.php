<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah periode survei') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('master.survey_periods.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="survey_type_id" class="block text-sm font-medium">{{ __('Jenis survei (harus aktif)') }}</label>
                            <select id="survey_type_id" name="survey_type_id" required class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="">{{ __('— Pilih jenis —') }}</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}" @selected((string) old('survey_type_id') === (string) $type->id)>{{ $type->code }} — {{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="code" class="block text-sm font-medium">{{ __('Kode (tidak dapat diubah setelah dibuat)') }}</label>
                            <input id="code" name="code" type="text" value="{{ old('code') }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium">{{ __('Nama') }}</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="period_type" class="block text-sm font-medium">{{ __('Tipe periode') }}</label>
                            <select id="period_type" name="period_type" required class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="">{{ __('— Pilih tipe —') }}</option>
                                @foreach ($periodTypes as $periodType)
                                    <option value="{{ $periodType }}" @selected(old('period_type') === $periodType)>{{ $periodType }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="period_number" class="block text-sm font-medium">{{ __('Nomor periode (kosongkan untuk TAHUNAN)') }}</label>
                            <input id="period_number" name="period_number" type="number" min="1" max="4" value="{{ old('period_number') }}" class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="year" class="block text-sm font-medium">{{ __('Tahun (2000–2100)') }}</label>
                            <input id="year" name="year" type="number" min="2000" max="2100" value="{{ old('year') }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="start_date" class="block text-sm font-medium">{{ __('Tanggal mulai') }}</label>
                            <input id="start_date" name="start_date" type="date" value="{{ old('start_date') }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="end_date" class="block text-sm font-medium">{{ __('Tanggal selesai') }}</label>
                            <input id="end_date" name="end_date" type="date" value="{{ old('end_date') }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="status" class="block text-sm font-medium">{{ __('Status awal') }}</label>
                            <select id="status" name="status" class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="DRAFT" @selected(old('status', 'DRAFT') === 'DRAFT')>DRAFT</option>
                                <option value="ARCHIVED" @selected(old('status') === 'ARCHIVED')>ARCHIVED</option>
                            </select>
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
