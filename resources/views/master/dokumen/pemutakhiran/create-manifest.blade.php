<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Buat manifest pemutakhiran') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4 text-sm text-gray-600">{{ __('Pilih batch alokasi NKS, isi baseline jumlah rumah tangga listing, dan centang kelengkapan berkas fisik (VSEN26.P dan Peta WS).') }}</p>
                    <form method="POST" action="{{ route('updating_manifests.store') }}">
                        @csrf
                        <div class="overflow-x-auto rounded border border-gray-200">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-50">
                                    <tr class="border-b">
                                        <th class="py-2 px-3">{{ __('Pilih') }}</th>
                                        <th class="py-2 px-3">{{ __('NKS') }}</th>
                                        <th class="py-2 px-3">{{ __('Periode') }}</th>
                                        <th class="py-2 px-3">{{ __('Ruta listing') }}</th>
                                        <th class="py-2 px-3">{{ __('VSEN26.P') }}</th>
                                        <th class="py-2 px-3">{{ __('Peta WS') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($allocations as $allocation)
                                        <tr class="border-b">
                                            <td class="py-2 px-3"><input type="checkbox" name="selected[]" value="{{ $allocation->id }}" /></td>
                                            <td class="py-2 px-3 font-mono">{{ $allocation->nks }}</td>
                                            <td class="py-2 px-3">{{ $allocation->period->code ?? '—' }}</td>
                                            <td class="py-2 px-3"><input type="number" name="rows[{{ $allocation->id }}][household_count_listing]" min="0" max="99999" value="0" class="border rounded px-2 py-1 w-24" /></td>
                                            <td class="py-2 px-3"><input type="checkbox" name="rows[{{ $allocation->id }}][has_vsen_p]" value="1" /></td>
                                            <td class="py-2 px-3"><input type="checkbox" name="rows[{{ $allocation->id }}][has_peta_ws]" value="1" /></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="py-4 px-3 text-center text-gray-500">{{ __('Tidak ada alokasi tersedia.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if ($errors->any())
                            <ul class="mt-4 text-sm text-red-600">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <button type="submit" class="mt-4 underline">{{ __('Buat BAST penyerahan') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
