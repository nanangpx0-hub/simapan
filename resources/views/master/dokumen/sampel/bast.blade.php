<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Daftar BAST Penyerahan Dokumen Sampel Susenas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <p>{{ __('Daftar Berita Acara Serah Terima dokumen sampel Susenas dari Tim Sosial.') }}</p>
                        <a href="{{ route('master.jenis-survei.create') }}" class="underline">{{ __('Buat BAST Baru') }}</a>
                    </div>

                    <div class="overflow-x-auto rounded border border-gray-200">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50">
                                <tr class="border-b">
                                    <th class="py-2 px-3">{{ __('No') }}</th>
                                    <th class="py-2 px-3">{{ __('Nomor BAST') }}</th>
                                    <th class="py-2 px-3">{{ __('Tanggal') }}</th>
                                    <th class="py-2 px-3">{{ __('Dari') }}</th>
                                    <th class="py-2 px-3">{{ __('Ke') }}</th>
                                    <th class="py-2 px-3">{{ __('Jumlah Dokumen') }}</th>
                                    <th class="py-2 px-3">{{ __('Status') }}</th>
                                    <th class="py-2 px-3">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($manifests as $index => $manifest)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-2 px-3">{{ $index + 1 }}</td>
                                        <td class="py-2 px-3 font-mono">{{ $manifest->manifest_number }}</td>
                                        <td class="py-2 px-3 whitespace-nowrap">{{ $manifest->submitted_at?->format('d M Y') ?? '—' }}</td>
                                        <td class="py-2 px-3">{{ $manifest->fromUnit->name ?? '—' }}</td>
                                        <td class="py-2 px-3">{{ $manifest->toUnit->name ?? '—' }}</td>
                                        <td class="py-2 px-3">{{ $manifest->items->count() }}</td>
                                        <td class="py-2 px-3">
                                            <span class="inline-block px-2 py-0.5 rounded text-xs font-medium
                                                {{ $manifest->status === 'RECEIVED_COMPLETE' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $manifest->status === 'SUBMITTED' ? 'bg-blue-100 text-blue-800' : '' }}
                                                {{ $manifest->status === 'RECEIVED_PARTIAL' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                {{ $manifest->status === 'REJECTED' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ !in_array($manifest->status, ['RECEIVED_COMPLETE', 'SUBMITTED', 'RECEIVED_PARTIAL', 'REJECTED']) ? 'bg-gray-100 text-gray-800' : '' }}">
                                                {{ $manifest->status }}
                                            </span>
                                        </td>
                                        <td class="py-2 px-3 whitespace-nowrap">
                                            <a href="#" class="underline text-blue-600 hover:text-blue-800">{{ __('Lihat') }}</a>
                                            <a href="#" class="underline text-indigo-600 hover:text-indigo-800 ms-2">{{ __('Cetak') }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="py-4 px-3 text-center text-gray-500">
                                            {{ __('Belum ada BAST penyerahan dokumen sampel.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($manifests instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <div class="mt-4">{{ $manifests->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>