<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah sampel DSRT') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('allocations.dsrt.store', $allocation) }}">
                        @csrf
                        <div class="mb-4 flex gap-2">
                            <div class="w-1/2">
                                <label for="nus" class="block text-sm font-medium">{{ __('NUS (immutable)') }}</label>
                                <input id="nus" name="nus" type="text" value="{{ old('nus') }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                            </div>
                            <div class="w-1/2">
                                <label for="nurt" class="block text-sm font-medium">{{ __('NURT (immutable)') }}</label>
                                <input id="nurt" name="nurt" type="text" value="{{ old('nurt') }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                            </div>
                        </div>
                        <div class="mb-4 flex gap-2">
                            <div class="w-1/3">
                                <label for="family_number" class="block text-sm font-medium">{{ __('No. keluarga') }}</label>
                                <input id="family_number" name="family_number" type="text" value="{{ old('family_number') }}" class="mt-1 block w-full border rounded px-3 py-2" />
                            </div>
                            <div class="w-1/3">
                                <label for="building_number" class="block text-sm font-medium">{{ __('No. bangunan') }}</label>
                                <input id="building_number" name="building_number" type="text" value="{{ old('building_number') }}" class="mt-1 block w-full border rounded px-3 py-2" />
                            </div>
                            <div class="w-1/3">
                                <label for="household_number" class="block text-sm font-medium">{{ __('No. rumah tangga') }}</label>
                                <input id="household_number" name="household_number" type="text" value="{{ old('household_number') }}" class="mt-1 block w-full border rounded px-3 py-2" />
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="krt_name" class="block text-sm font-medium">{{ __('Nama KRT') }}</label>
                            <input id="krt_name" name="krt_name" type="text" value="{{ old('krt_name') }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4 flex gap-2">
                            <div class="w-1/2">
                                <label for="krt_education_code" class="block text-sm font-medium">{{ __('Kode pendidikan KRT') }}</label>
                                <input id="krt_education_code" name="krt_education_code" type="text" value="{{ old('krt_education_code') }}" class="mt-1 block w-full border rounded px-3 py-2" />
                            </div>
                            <div class="w-1/2">
                                <label for="enumeration_status" class="block text-sm font-medium">{{ __('Status pencacahan') }}</label>
                                <select id="enumeration_status" name="enumeration_status" class="mt-1 block w-full border rounded px-3 py-2">
                                    @foreach ($enumerationStatuses as $status)
                                        <option value="{{ $status }}" @selected(old('enumeration_status', 'PENDING') === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="address" class="block text-sm font-medium">{{ __('Alamat (sensitif, tidak penuh di daftar)') }}</label>
                            <textarea id="address" name="address" class="mt-1 block w-full border rounded px-3 py-2"></textarea>
                        </div>
                        <div class="mb-4 flex gap-2">
                            <div class="w-1/2">
                                <label for="contact_person" class="block text-sm font-medium">{{ __('Contact person (sensitif)') }}</label>
                                <input id="contact_person" name="contact_person" type="text" value="" class="mt-1 block w-full border rounded px-3 py-2" />
                            </div>
                            <div class="w-1/2">
                                <label for="contact_phone" class="block text-sm font-medium">{{ __('Nomor kontak (sensitif)') }}</label>
                                <input id="contact_phone" name="contact_phone" type="text" value="" class="mt-1 block w-full border rounded px-3 py-2" />
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="notes" class="block text-sm font-medium">{{ __('Catatan') }}</label>
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
