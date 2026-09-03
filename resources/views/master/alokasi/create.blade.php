<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah alokasi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('allocations.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="survey_period_id" class="block text-sm font-medium">{{ __('Periode survei') }}</label>
                            <select id="survey_period_id" name="survey_period_id" required class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="">{{ __('— Pilih periode —') }}</option>
                                @foreach ($periods as $period)
                                    <option value="{{ $period->id }}" @selected((string) old('survey_period_id') === (string) $period->id)>{{ $period->code }} ({{ $period->status }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="village_region_id" class="block text-sm font-medium">{{ __('Desa (aktif, level desa)') }}</label>
                            <select id="village_region_id" name="village_region_id" required class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="">{{ __('— Pilih desa —') }}</option>
                                @foreach ($villages as $village)
                                    <option value="{{ $village->id }}" @selected((string) old('village_region_id') === (string) $village->id)>{{ $village->full_code }} — {{ $village->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="nks" class="block text-sm font-medium">{{ __('NKS (unik per periode, immutable)') }}</label>
                            <input id="nks" name="nks" type="text" value="{{ old('nks') }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="sls_code" class="block text-sm font-medium">{{ __('Kode SLS (opsional)') }}</label>
                            <input id="sls_code" name="sls_code" type="text" value="{{ old('sls_code') }}" class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="sub_sls_code" class="block text-sm font-medium">{{ __('Kode Sub-SLS (opsional)') }}</label>
                            <input id="sub_sls_code" name="sub_sls_code" type="text" value="{{ old('sub_sls_code') }}" class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="sls_name" class="block text-sm font-medium">{{ __('Nama SLS') }}</label>
                            <input id="sls_name" name="sls_name" type="text" value="{{ old('sls_name') }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="notes" class="block text-sm font-medium">{{ __('Catatan (opsional)') }}</label>
                            <textarea id="notes" name="notes" class="mt-1 block w-full border rounded px-3 py-2">{{ old('notes') }}</textarea>
                        </div>
                        @if ($errors->any())
                            <ul class="mb-4 text-sm text-red-600">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <button type="submit" class="underline">{{ __('Simpan sebagai DRAFT') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
