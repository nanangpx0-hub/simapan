<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @if (! is_null($stats['users'] ?? null))
                    @can('admin.user.manage')
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-indigo-600">
                        <div class="p-5">
                            <p class="text-sm font-medium text-gray-500">{{ __('Pengguna') }}</p>
                            <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['users'] }}</p>
                            <a href="{{ route('admin.users.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Kelola →') }}</a>
                        </div>
                    </div>
                    @endcan
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

            @if (! is_null($tasks['allocations'] ?? null) || ! is_null($tasks['dsrt_pending'] ?? null) || ! is_null($tasks['documents'] ?? null))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p class="font-semibold">{{ __('Tugas Aktif Saya') }}</p>
                        <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                            @if (! is_null($tasks['allocations'] ?? null))
                                <div class="border rounded p-4">
                                    <p class="text-sm text-gray-500">{{ __('Alokasi Aktif Saya') }}</p>
                                    <p class="mt-1 text-2xl font-semibold">{{ $tasks['allocations'] }}</p>
                                    <a href="{{ route('allocations.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Lihat →') }}</a>
                                </div>
                            @endif
                            @if (! is_null($tasks['dsrt_pending'] ?? null))
                                <div class="border rounded p-4">
                                    <p class="text-sm text-gray-500">{{ __('DSRT Menunggu Verifikasi') }}</p>
                                    <p class="mt-1 text-2xl font-semibold">{{ $tasks['dsrt_pending'] }}</p>
                                    <a href="{{ route('allocations.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Lihat →') }}</a>
                                </div>
                            @endif
                            @if (! is_null($tasks['documents'] ?? null))
                                <div class="border rounded p-4">
                                    <p class="text-sm text-gray-500">{{ __('Batch Pengolahan Saya') }}</p>
                                    <p class="mt-1 text-2xl font-semibold">{{ $tasks['documents'] }}</p>
                                    <a href="{{ route('documents.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Lihat →') }}</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($social['visible'] ?? false)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p class="font-semibold">{{ __('Pemantauan Tim Sosial') }}</p>
                        <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="border rounded p-4">
                                <p class="text-sm text-gray-500">{{ __('Alokasi Belum Lengkap Petugas') }}</p>
                                <p class="mt-1 text-2xl font-semibold">{{ $social['incomplete_allocations'] }}</p>
                                <a href="{{ route('allocations.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Lihat →') }}</a>
                            </div>
                            <div class="border rounded p-4">
                                <p class="text-sm text-gray-500">{{ __('DSRT Siap Verifikasi') }}</p>
                                <p class="mt-1 text-2xl font-semibold">{{ $social['dsrt_pending'] }}</p>
                                <a href="{{ route('allocations.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Lihat →') }}</a>
                            </div>
                            <div class="border rounded p-4">
                                <p class="text-sm text-gray-500">{{ __('Manifest Masuk dari PML') }}</p>
                                <p class="mt-1 text-2xl font-semibold">{{ $social['incoming_manifests'] }}</p>
                                <a href="{{ route('document_manifests.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Lihat →') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($processing['visible'] ?? false)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p class="font-semibold">{{ __('Pemantauan Ruang Pengolahan') }}</p>
                        <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="border rounded p-4">
                                <p class="text-sm text-gray-500">{{ __('Manifest Masuk Belum Diterima') }}</p>
                                <p class="mt-1 text-2xl font-semibold">{{ $processing['incoming_manifests'] }}</p>
                                <a href="{{ route('document_manifests.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Lihat →') }}</a>
                            </div>
                            <div class="border rounded p-4">
                                <p class="text-sm text-gray-500">{{ __('Dokumen Siap Olah') }}</p>
                                <p class="mt-1 text-2xl font-semibold">{{ $processing['ready_docs'] }}</p>
                                <a href="{{ route('documents.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Lihat →') }}</a>
                            </div>
                            <div class="border rounded p-4">
                                <p class="text-sm text-gray-500">{{ __('Dokumen Sedang Diolah') }}</p>
                                <p class="mt-1 text-2xl font-semibold">{{ $processing['processing_docs'] }}</p>
                                <a href="{{ route('documents.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Lihat →') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($executive['visible'] ?? false)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p class="font-semibold">{{ __('Executive Monitoring Dashboard') }}</p>
                        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                            <div class="border rounded p-4">
                                <p class="text-sm text-gray-500">{{ __('Progres Lapangan') }}</p>
                                <p class="mt-1 text-2xl font-semibold">{{ $executive['field_percent'] }}%</p>
                                <p class="text-xs text-gray-500">{{ __(':done dari :total alokasi selesai', ['done' => $executive['field_completed'], 'total' => $executive['field_total']]) }}</p>
                                <a href="{{ route('allocations.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Lihat →') }}</a>
                            </div>
                            <div class="border rounded p-4">
                                <p class="text-sm text-gray-500">{{ __('Verifikasi DSRT') }}</p>
                                <p class="mt-1 text-2xl font-semibold">{{ $executive['dsrt_percent'] }}%</p>
                                <p class="text-xs text-gray-500">{{ __(':done dari :total sampel terverifikasi', ['done' => $executive['dsrt_verified'], 'total' => $executive['dsrt_total']]) }}</p>
                                <a href="{{ route('allocations.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Lihat →') }}</a>
                            </div>
                            <div class="border rounded p-4">
                                <p class="text-sm text-gray-500">{{ __('Alur Dokumen') }}</p>
                                <ul class="mt-1 text-sm text-gray-700">
                                    <li>{{ __('Di Lapangan: :n', ['n' => $executive['flow']['lapangan']]) }}</li>
                                    <li>{{ __('Menuju Pengolahan: :n', ['n' => $executive['flow']['menuju']]) }}</li>
                                    <li>{{ __('Sedang Diolah: :n', ['n' => $executive['flow']['diolah']]) }}</li>
                                    <li>{{ __('Selesai: :n', ['n' => $executive['flow']['selesai']]) }}</li>
                                </ul>
                                <a href="{{ route('documents.index') }}" class="mt-2 inline-block text-sm text-indigo-600 hover:text-indigo-800">{{ __('Lihat →') }}</a>
                            </div>
                            <div class="border rounded p-4">
                                <p class="text-sm text-gray-500">{{ __('Deadline Periode') }}</p>
                                <ul class="mt-1 text-sm text-gray-700">
                                    @forelse ($executive['periods'] as $period)
                                        <li>
                                            {{ $period['code'] }} ({{ $period['end_date'] ?? '—' }})
                                            @if (! is_null($period['days_left']))
                                                <span class="ml-1 inline-block rounded px-2 py-0.5 text-xs {{ $period['days_left'] < 0 ? 'bg-red-100 text-red-700' : ($period['days_left'] <= 30 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                                    {{ $period['days_left'] < 0 ? __('Terlambat :n hari', ['n' => abs($period['days_left'])]) : __(':n hari lagi', ['n' => $period['days_left']]) }}
                                                </span>
                                            @endif
                                        </li>
                                    @empty
                                        <li>{{ __('Tidak ada periode aktif.') }}</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead>
                                    <tr class="border-b">
                                        <th class="py-2">{{ __('Jenis Survei') }}</th>
                                        <th class="py-2">{{ __('NKS Total') }}</th>
                                        <th class="py-2">{{ __('NKS Selesai') }}</th>
                                        <th class="py-2">{{ __('Progres') }}</th>
                                        <th class="py-2">{{ __('DSRT Terverifikasi') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($executive['by_type'] as $row)
                                        <tr class="border-b">
                                            <td class="py-2">{{ $row['code'] }}</td>
                                            <td class="py-2">{{ $row['total'] }}</td>
                                            <td class="py-2">{{ $row['completed'] }}</td>
                                            <td class="py-2">{{ $row['percent'] }}%</td>
                                            <td class="py-2">{{ $row['dsrt_verified'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

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
