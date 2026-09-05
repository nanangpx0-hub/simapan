<div class="space-y-4">
    <div class="flex flex-wrap items-end gap-2 text-sm">
        <div class="flex flex-col">
            <label for="officer-search" class="mb-1 font-medium text-gray-700">{{ __('Cari kode/nama/alias') }}</label>
            <input id="officer-search" type="text" wire:model.live.debounce.500ms="search" placeholder="{{ __('Cari kode/nama/alias') }}" class="border rounded px-2 py-1 w-56" />
        </div>
        <div class="flex flex-col">
            <label for="officer-unit" class="mb-1 font-medium text-gray-700">{{ __('Unit') }}</label>
            <select id="officer-unit" wire:model.live="workUnitId" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua unit') }}</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="officer-status" class="mb-1 font-medium text-gray-700">{{ __('Status') }}</label>
            <select id="officer-status" wire:model.live="status" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua status') }}</option>
                <option value="ACTIVE">{{ __('Aktif') }}</option>
                <option value="INACTIVE">{{ __('Nonaktif') }}</option>
            </select>
        </div>
        <div class="flex flex-col">
            <label for="officer-perpage" class="mb-1 font-medium text-gray-700">{{ __('Per halaman') }}</label>
            <select id="officer-perpage" wire:model.live="perPage" class="border rounded px-2 py-1">
                @foreach (\App\Livewire\Master\OfficerTable::PER_PAGE_OPTIONS as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 pb-0.5">
            <button type="button" wire:click="resetFilters" class="underline">{{ __('Reset') }}</button>
            <a href="{{ $this->exportUrl() }}" class="underline">{{ __('Ekspor') }}</a>
        </div>
        @if ($canManage)
            <form method="POST" action="{{ route('master.officers.import') }}" enctype="multipart/form-data" class="flex items-end gap-2">
                @csrf
                <div class="flex flex-col">
                    <label for="officer-import" class="mb-1 font-medium text-gray-700">{{ __('Impor .xlsx') }}</label>
                    <input id="officer-import" name="file" type="file" accept=".xlsx,.csv" class="text-xs" required />
                </div>
                <button type="submit" class="underline">{{ __('Impor') }}</button>
            </form>
        @endif
    </div>

    <p class="text-xs text-gray-500" aria-live="polite">
        {{ __('Menampilkan :from–:to dari :total data', ['from' => $officers->firstItem() ?? 0, 'to' => $officers->lastItem() ?? 0, 'total' => $officers->total()]) }}
    </p>

    <div wire:loading.delay class="text-xs text-gray-500">{{ __('Memuat…') }}</div>

    <div class="overflow-x-auto rounded border border-gray-200">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50">
                <tr class="border-b">
                    @foreach (['code' => __('Kode'), 'name' => __('Nama'), 'status' => __('Status')] as $field => $label)
                        <th class="py-2 px-3 whitespace-nowrap">
                            <button type="button" wire:click="sortBy('{{ $field }}')" class="font-semibold hover:underline focus:ring-2 focus:ring-indigo-500 rounded">
                                {{ $label }} @if ($sortField === $field) {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                            </button>
                        </th>
                    @endforeach
                    <th class="py-2 px-3">{{ __('Unit') }}</th>
                    <th class="py-2 px-3">{{ __('Akun') }}</th>
                    <th class="py-2 px-3">{{ __('Alias') }}</th>
                    <th class="py-2 px-3">{{ __('Dibuat') }}</th>
                    <th class="py-2 px-3">{{ __('Aksi') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($officers as $officer)
                    <tr wire:click="selectRow({{ $officer->id }})" class="border-b cursor-pointer transition-colors {{ $selectedId === $officer->id ? 'bg-indigo-50' : 'hover:bg-gray-50' }}">
                        <td class="py-2 px-3 font-mono">{{ $officer->code }}</td>
                        <td class="py-2 px-3">{{ $officer->name }}</td>
                        <td class="py-2 px-3">{{ $officer->status === 'ACTIVE' ? __('Aktif') : __('Nonaktif') }}</td>
                        <td class="py-2 px-3">{{ $officer->workUnit->code ?? '—' }}</td>
                        <td class="py-2 px-3">{{ $officer->user?->name ?? '—' }}</td>
                        <td class="py-2 px-3">{{ $officer->aliases_count }}</td>
                        <td class="py-2 px-3 whitespace-nowrap">{{ $officer->created_at?->format('Y-m-d') }}</td>
                        <td class="py-2 px-3 whitespace-nowrap" wire:click.stop>
                            <a href="{{ route('master.officers.show', $officer) }}" class="underline">{{ __('Lihat') }}</a>
                            <a href="{{ route('master.officers.edit', $officer) }}" class="underline ms-2">{{ __('Ubah') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-4 px-3 text-center text-gray-500">{{ __('Tidak ada data.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $officers->links() }}</div>
</div>
