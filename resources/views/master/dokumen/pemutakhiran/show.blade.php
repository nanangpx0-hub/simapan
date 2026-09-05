<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detail manifest pemutakhiran') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <dl class="text-sm space-y-2">
                        <div><dt class="font-medium inline">{{ __('Nomor BAST:') }}</dt> <dd class="inline font-mono">{{ $manifest->manifest_number }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Dari:') }}</dt> <dd class="inline">{{ $manifest->fromUnit->code ?? '—' }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Ke:') }}</dt> <dd class="inline">{{ $manifest->toUnit->code ?? '—' }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Status:') }}</dt> <dd class="inline">{{ $manifest->status }}</dd></div>
                    </dl>
                    <div class="mt-4 flex flex-wrap gap-4 text-sm">
                        <a href="{{ route('updating_manifests.print', $manifest) }}" class="underline">{{ __('Cetak BAST') }}</a>
                        <a href="{{ route('updating_manifests.export', $manifest) }}" class="underline">{{ __('Unduh Excel') }}</a>
                    </div>
                    @if (session('status'))
                        <p class="mt-4 text-sm text-emerald-700">{{ session('status') }}</p>
                    @endif
                    @if (session('warning'))
                        <p class="mt-4 text-sm text-amber-700">{{ session('warning') }}</p>
                    @endif
                    @if ($errors->any())
                        <ul class="mt-4 text-sm text-red-600">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            @can('receive', $manifest)
                @if ($manifest->status === 'SUBMITTED')
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <h3 class="font-semibold">{{ __('Checklist penerimaan fisik (PLS)') }}</h3>
                            <form method="POST" action="{{ route('updating_manifests.receive', $manifest) }}" class="mt-2">
                                @csrf
                                <div class="overflow-x-auto rounded border border-gray-200">
                                    <table class="w-full text-sm text-left">
                                        <thead class="bg-gray-50">
                                            <tr class="border-b">
                                                <th class="py-2 px-3">{{ __('NKS') }}</th>
                                                <th class="py-2 px-3">{{ __('VSEN26.P ada') }}</th>
                                                <th class="py-2 px-3">{{ __('Peta WS ada') }}</th>
                                                <th class="py-2 px-3">{{ __('Catatan (wajib bila tak lengkap)') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($manifest->updatingItems as $item)
                                                <tr class="border-b">
                                                    <td class="py-2 px-3 font-mono">{{ $item->nks }}</td>
                                                    <td class="py-2 px-3"><input type="checkbox" name="items[{{ $item->id }}][has_vsen_p]" value="1" @checked($item->has_vsen_p) /></td>
                                                    <td class="py-2 px-3"><input type="checkbox" name="items[{{ $item->id }}][has_peta_ws]" value="1" @checked($item->has_peta_ws) /></td>
                                                    <td class="py-2 px-3"><input type="text" name="items[{{ $item->id }}][receive_note]" value="{{ $item->receive_note }}" class="border rounded px-2 py-1 w-full" /></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <button type="submit" class="mt-4 underline">{{ __('Sahkan serah terima') }}</button>
                            </form>
                        </div>
                    </div>
                @endif
            @endcan

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <livewire:master.assign-updating-processor-table :manifestId="$manifest->id" />
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="font-semibold">{{ __('Validasi integritas entri vs listing') }}</h3>
                    <div class="overflow-x-auto rounded border border-gray-200 mt-2">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50">
                                <tr class="border-b">
                                    <th class="py-2 px-3">{{ __('NKS') }}</th>
                                    <th class="py-2 px-3">{{ __('Listing') }}</th>
                                    <th class="py-2 px-3">{{ __('Pengolah') }}</th>
                                    <th class="py-2 px-3">{{ __('Jumlah dientri') }}</th>
                                    <th class="py-2 px-3">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($manifest->updatingItems as $item)
                                    <tr class="border-b">
                                        <td class="py-2 px-3 font-mono">{{ $item->nks }}</td>
                                        <td class="py-2 px-3">{{ $item->household_count_listing }}</td>
                                        <td class="py-2 px-3">{{ $item->processor?->name ?? '—' }}</td>
                                        <td class="py-2 px-3">
                                            @can('assignUpdatingEntry', [$manifest, $item])
                                                <form method="POST" action="{{ route('updating_manifests.validate-entry', [$manifest, $item]) }}" class="flex gap-2">
                                                    @csrf
                                                    <input type="number" name="entry_count" min="0" class="border rounded px-2 py-1 w-24" required />
                                                    <button type="submit" class="underline">{{ __('Validasi') }}</button>
                                                </form>
                                            @else
                                                —
                                            @endcan
                                        </td>
                                        <td class="py-2 px-3">{{ $item->delivery_status }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
