<div class="space-y-4">
    <div class="flex flex-wrap items-end gap-2 text-sm">
        <div class="flex flex-col">
            <label for="manifest-search" class="mb-1 font-medium text-gray-700">{{ __('Cari nomor') }}</label>
            <input id="manifest-search" type="text" wire:model.live.debounce.500ms="q" placeholder="{{ __('Cari nomor') }}" class="border rounded px-2 py-1 w-56" />
        </div>
        <div class="flex flex-col">
            <label for="manifest-status" class="mb-1 font-medium text-gray-700">{{ __('Status') }}</label>
            <select id="manifest-status" wire:model.live="status" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua status') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}">{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="manifest-tounit" class="mb-1 font-medium text-gray-700">{{ __('Tujuan') }}</label>
            <select id="manifest-tounit" wire:model.live="toUnitId" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua tujuan') }}</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="manifest-perpage" class="mb-1 font-medium text-gray-700">{{ __('Per halaman') }}</label>
            <select id="manifest-perpage" wire:model.live="perPage" class="border rounded px-2 py-1">
                @foreach (\App\Livewire\Master\DocumentManifestTable::PER_PAGE_OPTIONS as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 pb-0.5">
            <button type="button" wire:click="resetFilters" class="underline">{{ __('Reset') }}</button>
            <a href="{{ $this->exportUrl() }}" class="underline">{{ __('Ekspor') }}</a>
        </div>
        @if ($canManage)
            <form method="POST" action="{{ route('document_manifests.import') }}" enctype="multipart/form-data" class="flex items-end gap-2">
                @csrf
                <div class="flex flex-col">
                    <label for="manifest-import" class="mb-1 font-medium text-gray-700">{{ __('Impor .xlsx') }}</label>
                    <input id="manifest-import" name="file" type="file" accept=".xlsx,.csv" class="text-xs" required />
                </div>
                <button type="submit" class="underline">{{ __('Impor') }}</button>
            </form>
        @endif
    </div>

    <p class="text-xs text-gray-500" aria-live="polite">
        {{ __('Menampilkan :from–:to dari :total data', ['from' => $manifests->firstItem() ?? 0, 'to' => $manifests->lastItem() ?? 0, 'total' => $manifests->total()]) }}
    </p>

    <div wire:loading.delay class="text-xs text-gray-500">{{ __('Memuat…') }}</div>

    <div class="overflow-x-auto rounded border border-gray-200">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50">
                <tr class="border-b">
                    <th class="py-2 px-3 whitespace-nowrap">
                        <button type="button" wire:click="sortBy('manifest_number')" class="font-semibold hover:underline focus:ring-2 focus:ring-indigo-500 rounded">
                            {{ __('Nomor') }} @if ($sortField === 'manifest_number') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                        </button>
                    </th>
                    <th class="py-2 px-3">{{ __('Dari') }}</th>
                    <th class="py-2 px-3">{{ __('Ke') }}</th>
                    <th class="py-2 px-3">{{ __('Item') }}</th>
                    <th class="py-2 px-3 whitespace-nowrap">
                        <button type="button" wire:click="sortBy('status')" class="font-semibold hover:underline focus:ring-2 focus:ring-indigo-500 rounded">
                            {{ __('Status') }} @if ($sortField === 'status') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                        </button>
                    </th>
                    <th class="py-2 px-3 whitespace-nowrap">
                        <button type="button" wire:click="sortBy('created_at')" class="font-semibold hover:underline focus:ring-2 focus:ring-indigo-500 rounded">
                            {{ __('Dibuat') }} @if ($sortField === 'created_at') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                        </button>
                    </th>
                    <th class="py-2 px-3">{{ __('Aksi') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($manifests as $manifest)
                    <tr wire:click="selectRow({{ $manifest->id }})" class="border-b cursor-pointer transition-colors {{ $selectedId === $manifest->id ? 'bg-indigo-50' : 'hover:bg-gray-50' }}">
                        <td class="py-2 px-3 font-mono">{{ $manifest->manifest_number }}</td>
                        <td class="py-2 px-3">{{ $manifest->fromUnit->code ?? '—' }}</td>
                        <td class="py-2 px-3">{{ $manifest->toUnit->code ?? '—' }}</td>
                        <td class="py-2 px-3">{{ $manifest->items_count }}</td>
                        <td class="py-2 px-3">{{ $manifest->status }}</td>
                        <td class="py-2 px-3 whitespace-nowrap">{{ $manifest->created_at?->format('Y-m-d') }}</td>
                        <td class="py-2 px-3 whitespace-nowrap" wire:click.stop>
                            <a href="{{ route('document_manifests.show', $manifest) }}" class="underline">{{ __('Detail') }}</a>
                            <a href="{{ route('document_manifests.edit', $manifest) }}" class="underline ms-2">{{ __('Ubah') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-4 px-3 text-center text-gray-500">{{ __('Tidak ada data.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $manifests->links() }}</div>
</div>
