<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ubah pengguna') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium">{{ __('Nama') }}</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium">{{ __('Email') }}</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="is_active" class="block text-sm font-medium">{{ __('Status') }}</label>
                            <select id="is_active" name="is_active" class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="1" @selected((string) old('is_active', (string) (int) $user->is_active) === '1')>{{ __('Aktif') }}</option>
                                <option value="0" @selected((string) old('is_active', (string) (int) $user->is_active) === '0')>{{ __('Nonaktif') }}</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <span class="block text-sm font-medium">{{ __('Role') }}</span>
                            @php($current = $user->getRoleNames()->all())
                            @foreach ($catalog as $slug => $label)
                                <label class="block text-sm mt-1">
                                    <input type="checkbox" name="roles[]" value="{{ $slug }}" @checked(in_array($slug, old('roles', $current), true)) />
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <div class="mb-4">
                            <label for="officer_id" class="block text-sm font-medium">{{ __('Tautkan ke data petugas (opsional)') }}</label>
                            <select id="officer_id" name="officer_id" class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="">{{ __('— Tidak ditautkan —') }}</option>
                                @foreach ($officers as $officer)
                                    <option value="{{ $officer->id }}" @selected(old('officer_id', $user->officer?->id) == $officer->id)>{{ $officer->code }} — {{ $officer->name }}</option>
                                @endforeach
                            </select>
                            @error('officer_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
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
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="mt-4" onsubmit="return confirm('{{ __('Hapus pengguna ini?') }}');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="underline text-red-600">{{ __('Hapus') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
