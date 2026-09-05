<div class="space-y-4">
    <div class="flex flex-wrap items-end gap-2 text-sm">
        <div class="flex flex-col">
            <label for="work-unit-search" class="mb-1 font-medium text-gray-700">{{ __('Cari kode/nama') }}</label>
            <input id="work-unit-search" type="text" wire:model.live.debounce.500ms="q" placeholder="{{ __('Cari kode/nama') }}" class="border rounded px-2 py-1 w-56" />
        </div>
        <div class="flex flex-col">
            <label for="work-unit-status" class="mb-1 font-medium text-gray-700">{{ __('Status') }}</label>
            <select id="work-unit-status" wire:model.live="status" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua status') }}</option>
                <option value="aktif">{{ __('Aktif') }}</option>
                <option value="nonaktif">{{ __('Nonaktif') }}</option>
            </select>
        </div>
        <div class="flex flex-col">
            <label for="work-unit-perpage" class="mb-1 font-medium text-gray-700">{{ __('Per halaman') }}</label>
            <select id="work-unit-perpage" wire:model.live="perPage" class="border rounded px-2 py-1">
                @foreach (\App\Livewire\Master\WorkUnitTable::PER_PAGE_OPTIONS as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 pb-0.5">
            <button type="button" wire:click="resetFilters" class="underline">{{ __('Reset') }}</button>
            <a href="{{ $this->exportUrl() }}" class="underline">{{ __('Ekspor') }}</a>
        </div>
        @if ($canManage)
            <form method="POST" action="{{ route('master.unit-kerja.import') }}" enctype="multipart/form-data" class="flex items-end gap-2">
                @csrf
                <div class="flex flex-col">
                    <label for="work-unit-import" class="mb-1 font-medium text-gray-700">{{ __('Impor .xlsx') }}</label>
                    <input id="work-unit-import" name="file" type="file" accept=".xlsx,.csv" class="text-xs" required />
                </div>
                <button type="submit" class="underline">{{ __('Impor') }}</button>
            </form>
        @endif
    </div>

    <p class="text-xs text-gray-500" aria-live="polite">
        {{ __('Menampilkan :from–:to dari :total data', ['from' => $units->firstItem() ?? 0, 'to' => $units->lastItem() ?? 0, 'total' => $units->total()]) }}
    </p>

    <div wire:loading.delay class="text-xs text-gray-500">{{ __('Memuat…') }}</div>

    <div class="overflow-x-auto rounded border border-gray-200">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50">
                <tr class="border-b">
                    @foreach (['code' => __('Kode'), 'name' => __('Nama'), 'created_at' => __('Dibuat')] as $field => $label)
                        <th class="py-2 px-3 whitespace-nowrap">
                            <button type="button" wire:click="sortBy('{{ $field }}')" class="font-semibold hover:underline focus:ring-2 focus:ring-indigo-500 rounded">
                                {{ $label }} @if ($sortField === $field) {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                            </button>
                        </th>
                    @endforeach
                    <th class="py-2 px-3">{{ __('Parent') }}</th>
                    <th class="py-2 px-3">{{ __('Status') }}</th>
                    <th class="py-2 px-3">{{ __('Aksi') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($units as $unit)
                    <tr wire:click="selectRow({{ $unit->id }})" class="border-b cursor-pointer transition-colors {{ $selectedId === $unit->id ? 'bg-indigo-50' : 'hover:bg-gray-50' }}">
                        <td class="py-2 px-3 font-mono">{{ $unit->code }}</td>
                        <td class="py-2 px-3">{{ $unit->name }}</td>
                        <td class="py-2 px-3 whitespace-nowrap">{{ $unit->created_at?->format('Y-m-d') }}</td>
                        <td class="py-2 px-3">{{ $unit->parent ? $unit->parent->code.' — '.$unit->parent->name : __('—') }}</td>
                        <td class="py-2 px-3">{{ $unit->is_active ? __('Aktif') : __('Nonaktif') }}</td>
                        <td class="py-2 px-3 whitespace-nowrap" wire:click.stop>
                            <a href="{{ route('master.unit-kerja.edit', $unit) }}" class="underline">{{ __('Ubah') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-4 px-3 text-center text-gray-500">{{ __('Tidak ada data.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $units->links() }}</div>
</div>
