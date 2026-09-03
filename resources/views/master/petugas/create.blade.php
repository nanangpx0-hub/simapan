<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah petugas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('master.officers.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="code" class="block text-sm font-medium">{{ __('Kode (tidak dapat diubah setelah dibuat)') }}</label>
                            <input id="code" name="code" type="text" value="{{ old('code') }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium">{{ __('Nama') }}</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="work_unit_id" class="block text-sm font-medium">{{ __('Unit kerja (harus aktif)') }}</label>
                            <select id="work_unit_id" name="work_unit_id" required class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="">{{ __('— Pilih unit —') }}</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}" @selected((string) old('work_unit_id') === (string) $unit->id)>{{ $unit->code }} — {{ $unit->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="user_id" class="block text-sm font-medium">{{ __('Akun user (opsional)') }}</label>
                            <select id="user_id" name="user_id" class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="">{{ __('— Tanpa akun —') }}</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>{{ $user->name }} — {{ $user->email }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="phone" class="block text-sm font-medium">{{ __('Telepon (opsional, sensitif)') }}</label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium">{{ __('Email (opsional, sensitif)') }}</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="status" class="block text-sm font-medium">{{ __('Status') }}</label>
                            <select id="status" name="status" class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="ACTIVE" @selected(old('status', 'ACTIVE') === 'ACTIVE')>{{ __('Aktif') }}</option>
                                <option value="INACTIVE" @selected(old('status') === 'INACTIVE')>{{ __('Nonaktif') }}</option>
                            </select>
                        </div>
                        <div class="mb-4 flex gap-2">
                            <div class="w-1/2">
                                <label for="active_from" class="block text-sm font-medium">{{ __('Aktif dari') }}</label>
                                <input id="active_from" name="active_from" type="date" value="{{ old('active_from') }}" class="mt-1 block w-full border rounded px-3 py-2" />
                            </div>
                            <div class="w-1/2">
                                <label for="active_until" class="block text-sm font-medium">{{ __('Aktif sampai') }}</label>
                                <input id="active_until" name="active_until" type="date" value="{{ old('active_until') }}" class="mt-1 block w-full border rounded px-3 py-2" />
                            </div>
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
