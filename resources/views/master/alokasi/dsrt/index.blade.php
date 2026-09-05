<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('DSRT: :nks', ['nks' => $allocation->nks]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4 text-sm text-gray-600">{{ __('Periode: :code — Desa: :desa', ['code' => $allocation->period->code, 'desa' => $allocation->village->full_code]) }}</p>
                    <div class="flex justify-between items-center mb-4">
                        <a href="{{ route('allocations.show', $allocation) }}" class="underline">{{ __('Kembali ke alokasi') }}</a>
                        <a href="{{ route('allocations.dsrt.create', $allocation) }}" class="underline">{{ __('Tambah sampel') }}</a>
                    </div>
                    <livewire:master.dsrt-sample-table :allocation-id="$allocation->id" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
