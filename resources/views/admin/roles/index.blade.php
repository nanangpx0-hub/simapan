<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Katalog Role') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4 text-sm text-gray-600">{{ __('Role bersifat sistem-terkelola. Perubahan hanya melalui seeder dan kode.') }}</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Nama') }}</th>
                                <th class="py-2">{{ __('Slug') }}</th>
                                <th class="py-2">{{ __('Permission') }}</th>
                                <th class="py-2">{{ __('Jumlah user') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                <tr class="border-b">
                                    <td class="py-2">{{ $labels[$role->name] ?? $role->name }}</td>
                                    <td class="py-2">{{ $role->name }}</td>
                                    <td class="py-2">{{ $role->permissions->pluck('name')->join(', ') }}</td>
                                    <td class="py-2">{{ $role->users->count() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
