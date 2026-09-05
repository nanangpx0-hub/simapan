<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Alokasi Kegiatan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <p>{{ __('Daftar alokasi kegiatan Susenas/Seruti.') }}</p>
                        <div class="flex gap-4">
                            @can('allocation.view')
                                <a href="{{ route('allocations.executive-export') }}" class="underline">{{ __('Unduh Rekap Eksekutif') }}</a>
                            @endcan
                            @can('allocation.manage')
                                <a href="{{ route('allocations.create') }}" class="underline">{{ __('Tambah alokasi') }}</a>
                            @endcan
                        </div>
                    </div>
                    <livewire:master.allocation-table />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
