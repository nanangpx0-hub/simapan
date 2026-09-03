<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pengguna') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <p>{{ __('Daftar pengguna SIMAPAN.') }}</p>
                        <a href="{{ route('admin.users.create') }}" class="underline">{{ __('Tambah pengguna') }}</a>
                    </div>
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Nama') }}</th>
                                <th class="py-2">{{ __('Email') }}</th>
                                <th class="py-2">{{ __('Status') }}</th>
                                <th class="py-2">{{ __('Role') }}</th>
                                <th class="py-2">{{ __('Dibuat') }}</th>
                                <th class="py-2">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr class="border-b">
                                    <td class="py-2">{{ $user->name }}</td>
                                    <td class="py-2">{{ $user->email }}</td>
                                    <td class="py-2">{{ $user->is_active ? __('Aktif') : __('Nonaktif') }}</td>
                                    <td class="py-2">{{ $user->getRoleNames()->join(', ') }}</td>
                                    <td class="py-2">{{ $user->created_at }}</td>
                                    <td class="py-2">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="underline">{{ __('Ubah') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $users->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
