<div class="space-y-4">
    <div class="flex flex-wrap items-end gap-2 text-sm">
        <div class="flex flex-col">
            <label for="region-search" class="mb-1 font-medium text-gray-700">{{ __('Cari kode/nama') }}</label>
            <input id="region-search" type="text" wire:model.live.debounce.500ms="q" placeholder="{{ __('Cari kode/nama') }}" class="border rounded px-2 py-1 w-56" />
        </div>
        <div class="flex flex-col">
            <label for="region-level" class="mb-1 font-medium text-gray-700">{{ __('Level') }}</label>
            <select id="region-level" wire:model.live="level" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua level') }}</option>
                @foreach ($levels as $level)
                    <option value="{{ $level }}">{{ $level }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="region-parent" class="mb-1 font-medium text-gray-700">{{ __('Parent') }}</label>
            <select id="region-parent" wire:model.live="parentId" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua parent') }}</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}">{{ $parent->full_code }} — {{ $parent->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="region-status" class="mb-1 font-medium text-gray-700">{{ __('Status') }}</label>
            <select id="region-status" wire:model.live="status" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua status') }}</option>
                <option value="aktif">{{ __('Aktif') }}</option>
                <option value="nonaktif">{{ __('Nonaktif') }}</option>
            </select>
        </div>
        <div class="flex flex-col">
            <label for="region-perpage" class="mb-1 font-medium text-gray-700">{{ __('Per halaman') }}</label>
            <select id="region-perpage" wire:model.live="perPage" class="border rounded px-2 py-1">
                @foreach (\App\Livewire\Master\RegionTable::PER_PAGE_OPTIONS as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 pb-0.5">
            <button type="button" wire:click="resetFilters" class="underline">{{ __('Reset') }}</button>
            <a href="{{ $this->exportUrl() }}" class="underline">{{ __('Ekspor') }}</a>
        </div>
        @if ($canManage)
            <form method="POST" action="{{ route('master.wilayah.import') }}" enctype="multipart/form-data" class="flex items-end gap-2">
                @csrf
                <div class="flex flex-col">
                    <label for="region-import" class="mb-1 font-medium text-gray-700">{{ __('Impor .xlsx') }}</label>
                    <input id="region-import" name="file" type="file" accept=".xlsx,.csv" class="text-xs" required />
                </div>
                <button type="submit" class="underline">{{ __('Impor') }}</button>
            </form>
        @endif
    </div>

    <p class="text-xs text-gray-500" aria-live="polite">
        {{ __('Menampilkan :from–:to dari :total data', ['from' => $regions->firstItem() ?? 0, 'to' => $regions->lastItem() ?? 0, 'total' => $regions->total()]) }}
    </p>

    <div wire:loading.delay class="text-xs text-gray-500">{{ __('Memuat…') }}</div>

    <div class="overflow-x-auto rounded border border-gray-200">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50">
                <tr class="border-b">
                    @foreach (['full_code' => __('Full Code'), 'code' => __('Code'), 'name' => __('Nama'), 'created_at' => __('Dibuat')] as $field => $label)
                        <th class="py-2 px-3 whitespace-nowrap">
                            <button type="button" wire:click="sortBy('{{ $field }}')" class="font-semibold hover:underline focus:ring-2 focus:ring-indigo-500 rounded">
                                {{ $label }} @if ($sortField === $field) {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                            </button>
                        </th>
                    @endforeach
                    <th class="py-2 px-3">{{ __('Level') }}</th>
                    <th class="py-2 px-3">{{ __('Parent') }}</th>
                    <th class="py-2 px-3">{{ __('Status') }}</th>
                    <th class="py-2 px-3">{{ __('Child aktif') }}</th>
                    <th class="py-2 px-3">{{ __('Aksi') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($regions as $region)
                    <tr wire:click="selectRow({{ $region->id }})" class="border-b cursor-pointer transition-colors {{ $selectedId === $region->id ? 'bg-indigo-50' : 'hover:bg-gray-50' }}">
                        <td class="py-2 px-3 font-mono">{{ $region->full_code }}</td>
                        <td class="py-2 px-3 font-mono">{{ $region->code }}</td>
                        <td class="py-2 px-3">{{ $region->name }}</td>
                        <td class="py-2 px-3 whitespace-nowrap">{{ $region->created_at?->format('Y-m-d') }}</td>
                        <td class="py-2 px-3">{{ $region->level }}</td>
                        <td class="py-2 px-3">{{ $region->parent ? $region->parent->full_code.' — '.$region->parent->name : __('—') }}</td>
                        <td class="py-2 px-3">{{ $region->is_active ? __('Aktif') : __('Nonaktif') }}</td>
                        <td class="py-2 px-3">{{ $region->active_children_count }}</td>
                        <td class="py-2 px-3 whitespace-nowrap" wire:click.stop>
                            <a href="{{ route('master.wilayah.edit', $region) }}" class="underline">{{ __('Ubah') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="py-4 px-3 text-center text-gray-500">{{ __('Tidak ada data.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $regions->links() }}</div>
</div>
