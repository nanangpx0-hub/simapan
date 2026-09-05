<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manifest Pemutakhiran (VSEN.P)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <p>{{ __('Daftar BAST penyerahan dokumen pemutakhiran Susenas.') }}</p>
                        @can('create', App\Models\DocumentManifest::class)
                            <a href="{{ route('updating_manifests.create') }}" class="underline">{{ __('Buat manifest pemutakhiran') }}</a>
                        @endcan
                    </div>
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Nomor BAST') }}</th>
                                <th class="py-2">{{ __('Dari') }}</th>
                                <th class="py-2">{{ __('Ke') }}</th>
                                <th class="py-2">{{ __('NKS') }}</th>
                                <th class="py-2">{{ __('Status') }}</th>
                                <th class="py-2">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($manifests as $manifest)
                                <tr class="border-b">
                                    <td class="py-2 font-mono">{{ $manifest->manifest_number }}</td>
                                    <td class="py-2">{{ $manifest->fromUnit->code ?? '—' }}</td>
                                    <td class="py-2">{{ $manifest->toUnit->code ?? '—' }}</td>
                                    <td class="py-2">{{ $manifest->updating_items_count }}</td>
                                    <td class="py-2">{{ $manifest->status }}</td>
                                    <td class="py-2"><a href="{{ route('updating_manifests.show', $manifest) }}" class="underline">{{ __('Detail') }}</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-4 text-center text-gray-500">{{ __('Tidak ada data.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $manifests->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
