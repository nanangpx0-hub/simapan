<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @if (! is_null($stats['users'] ?? null))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-indigo-600">
                        <div class="p-5">
                            <p class="text-sm font-medium text-gray-500">{{ __('Pengguna') }}</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['users'] }}</p>
                            <a href="{{ route('admin.users.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Kelola →') }}</a>
                        </div>
                    </div>
                @endif
                @if (! is_null($stats['officers'] ?? null))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-emerald-500">
                        <div class="p-5">
                            <p class="text-sm font-medium text-gray-500">{{ __('Petugas aktif') }}</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['officers'] }}</p>
                            <a href="{{ route('master.officers.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Lihat →') }}</a>
                        </div>
                    </div>
                @endif
                @if (! is_null($stats['allocations'] ?? null))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-amber-500">
                        <div class="p-5">
                            <p class="text-sm font-medium text-gray-500">{{ __('Alokasi aktif') }}</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['allocations'] }}</p>
                            <a href="{{ route('allocations.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Lihat →') }}</a>
                        </div>
                    </div>
                @endif
                @if (! is_null($stats['documents'] ?? null))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-sky-500">
                        <div class="p-5">
                            <p class="text-sm font-medium text-gray-500">{{ __('Dokumen berjalan') }}</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['documents'] }}</p>
                            <a href="{{ route('documents.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Lihat →') }}</a>
                        </div>
                    </div>
                @endif
                @if (! is_null($stats['periods'] ?? null))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-violet-500">
                        <div class="p-5">
                            <p class="text-sm font-medium text-gray-500">{{ __('Periode aktif') }}</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['periods'] }}</p>
                            <a href="{{ route('master.survey_periods.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Lihat →') }}</a>
                        </div>
                    </div>
                @endif
                @if (! is_null($stats['manifests'] ?? null))
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-rose-500">
                        <div class="p-5">
                            <p class="text-sm font-medium text-gray-500">{{ __('Manifest menunggu') }}</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['manifests'] }}</p>
                            <a href="{{ route('document_manifests.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Lihat →') }}</a>
                        </div>
                    </div>
                @endif
            </div>

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
