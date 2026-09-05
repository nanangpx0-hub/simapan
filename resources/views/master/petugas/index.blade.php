<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Master Petugas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <p>{{ __('Daftar petugas operasional.') }}</p>
                        <a href="{{ route('master.officers.create') }}" class="underline">{{ __('Tambah petugas') }}</a>
                    </div>
                    <livewire:master.officer-table />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
