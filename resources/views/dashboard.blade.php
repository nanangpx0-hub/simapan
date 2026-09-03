<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __('SIMAPAN') }}
                    <p class="mt-1 font-semibold">{{ __('Sistem Informasi Manajemen Pengolahan dan Pengawasan') }}</p>
                    <p class="mt-2 text-sm text-gray-600">{{ __('Dari lapangan hingga data final.') }}</p>
                    <p class="mt-4 text-sm">{{ __('Pengguna: :name', ['name' => auth()->user()->name]) }}</p>
                    <p class="mt-1 text-sm">{{ __('Role: :roles', ['roles' => auth()->user()->getRoleNames()->join(', ')]) }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
