<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah wilayah') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('master.wilayah.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="level" class="block text-sm font-medium">{{ __('Level') }}</label>
                            <select id="level" name="level" required class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="">{{ __('— Pilih level —') }}</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level }}" @selected(old('level') === $level)>{{ $level }}</option>
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
                            <label for="parent_id" class="block text-sm font-medium">{{ __('Parent (wajib kecuali PROVINSI)') }}</label>
                            <select id="parent_id" name="parent_id" class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="">{{ __('— Tanpa parent —') }}</option>
                                @foreach ($parents as $parent)
                                    <option value="{{ $parent->id }}" @selected((string) old('parent_id') === (string) $parent->id)>{{ $parent->full_code }} — {{ $parent->name }} ({{ $parent->level }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="is_active" class="block text-sm font-medium">{{ __('Status') }}</label>
                            <select id="is_active" name="is_active" class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="1" @selected(old('is_active', '1') === '1')>{{ __('Aktif') }}</option>
                                <option value="0" @selected(old('is_active') === '0')>{{ __('Nonaktif') }}</option>
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
