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
                    <livewire:master.document-location-table />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
