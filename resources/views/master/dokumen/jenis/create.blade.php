<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah jenis dokumen') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('document_types.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="code" class="block text-sm font-medium">{{ __('Kode') }}</label>
                            <input id="code" name="code" type="text" value="{{ old('code') }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium">{{ __('Nama') }}</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium">{{ __('Deskripsi') }}</label>
                            <textarea id="description" name="description" class="mt-1 block w-full border rounded px-3 py-2">{{ old('description') }}</textarea>
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
