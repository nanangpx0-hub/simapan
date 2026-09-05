<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dokumen Fisik') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <p>{{ __('Daftar dokumen fisik.') }}</p>
                        <a href="{{ route('documents.create') }}" class="underline">{{ __('Tambah dokumen') }}</a>
                    </div>
                    <livewire:master.document-table />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
