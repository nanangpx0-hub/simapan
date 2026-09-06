<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah jenis survei') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('master.jenis-survei.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="code" class="block text-sm font-medium">{{ __('Kode') }}</label>
                            <input id="code" name="code" type="text" value="{{ old('code') }}" required readonly class="mt-1 block w-full border rounded px-3 py-2 bg-gray-100" />
                            <p class="mt-1 text-xs text-gray-500">{{ __('Contoh: SUSENAS, SERUTI, SUSENAS-2026') }}</p>
                        </div>
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium">{{ __('Nama') }}</label>
                            <select id="name" name="name" required class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="">{{ __('— Pilih nama survei —') }}</option>
                                <option value="SUSENAS" @selected(old('name') === 'SUSENAS')>SUSENAS</option>
                                <option value="SERUTI" @selected(old('name') === 'SERUTI')>SERUTI</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">{{ __('Pilihan: SUSENAS atau SERUTI') }}</p>
                        </div>
                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium">{{ __('Deskripsi') }}</label>
                            <textarea id="description" name="description" class="mt-1 block w-full border rounded px-3 py-2">{{ old('description') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">{{ __('Contoh: Survei Sosial Ekonomi Nasional, Survei Rumah Tiga Bulanan') }}</p>
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
                    <script>
                        document.getElementById('name').addEventListener('change', function () {
                            document.getElementById('code').value = this.value;
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
