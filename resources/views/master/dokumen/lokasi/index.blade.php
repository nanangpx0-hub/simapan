<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Lokasi Dokumen') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <p>{{ __('Katalog lokasi penyimpanan.') }}</p>
                        <a href="{{ route('document_locations.create') }}" class="underline">{{ __('Tambah lokasi') }}</a>
                    </div>
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Kode') }}</th>
                                <th class="py-2">{{ __('Nama') }}</th>
                                <th class="py-2">{{ __('Status') }}</th>
                                <th class="py-2">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($locations as $location)
                                <tr class="border-b">
                                    <td class="py-2">{{ $location->code }}</td>
                                    <td class="py-2">{{ $location->name }}</td>
                                    <td class="py-2">{{ $location->is_active ? __('Aktif') : __('Nonaktif') }}</td>
                                    <td class="py-2">
                                        <a href="{{ route('document_locations.edit', $location) }}" class="underline">{{ __('Ubah') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $locations->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
