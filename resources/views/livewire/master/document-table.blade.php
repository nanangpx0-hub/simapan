<div class="space-y-4">
    <div class="flex flex-wrap items-end gap-2 text-sm">
        <div class="flex flex-col">
            <label for="document-search" class="mb-1 font-medium text-gray-700">{{ __('Cari nomor/judul') }}</label>
            <input id="document-search" type="text" wire:model.live.debounce.500ms="q" placeholder="{{ __('Cari nomor/judul') }}" class="border rounded px-2 py-1 w-56" />
        </div>
        <div class="flex flex-col">
            <label for="document-type" class="mb-1 font-medium text-gray-700">{{ __('Jenis') }}</label>
            <select id="document-type" wire:model.live="documentTypeId" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua jenis') }}</option>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}">{{ $type->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="document-status" class="mb-1 font-medium text-gray-700">{{ __('Status') }}</label>
            <select id="document-status" wire:model.live="status" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua status') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}">{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="document-perpage" class="mb-1 font-medium text-gray-700">{{ __('Per halaman') }}</label>
            <select id="document-perpage" wire:model.live="perPage" class="border rounded px-2 py-1">
                @foreach (\App\Livewire\Master\DocumentTable::PER_PAGE_OPTIONS as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 pb-0.5">
            <button type="button" wire:click="resetFilters" class="underline">{{ __('Reset') }}</button>
            <a href="{{ $this->exportUrl() }}" class="underline">{{ __('Ekspor') }}</a>
        </div>
        @if ($canManage)
            <form method="POST" action="{{ route('documents.import') }}" enctype="multipart/form-data" class="flex items-end gap-2">
                @csrf
                <div class="flex flex-col">
                    <label for="document-import" class="mb-1 font-medium text-gray-700">{{ __('Impor .xlsx') }}</label>
                    <input id="document-import" name="file" type="file" accept=".xlsx,.csv" class="text-xs" required />
                </div>
                <button type="submit" class="underline">{{ __('Impor') }}</button>
            </form>
        @endif
    </div>

    <p class="text-xs text-gray-500" aria-live="polite">
        {{ __('Menampilkan :from–:to dari :total data', ['from' => $documents->firstItem() ?? 0, 'to' => $documents->lastItem() ?? 0, 'total' => $documents->total()]) }}
    </p>

    <div wire:loading.delay class="text-xs text-gray-500">{{ __('Memuat…') }}</div>

    <div class="overflow-x-auto rounded border border-gray-200">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50">
                <tr class="border-b">
                    @foreach (['document_number' => __('Nomor'), 'title' => __('Judul'), 'status' => __('Status')] as $field => $label)
                        <th class="py-2 px-3 whitespace-nowrap">
                            <button type="button" wire:click="sortBy('{{ $field }}')" class="font-semibold hover:underline focus:ring-2 focus:ring-indigo-500 rounded">
                                {{ $label }} @if ($sortField === $field) {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                            </button>
                        </th>
                    @endforeach
                    <th class="py-2 px-3">{{ __('Jenis') }}</th>
                    <th class="py-2 px-3">{{ __('Konteks') }}</th>
                    <th class="py-2 px-3">{{ __('Qty') }}</th>
                    <th class="py-2 px-3 whitespace-nowrap">
                        <button type="button" wire:click="sortBy('created_at')" class="font-semibold hover:underline focus:ring-2 focus:ring-indigo-500 rounded">
                            {{ __('Dibuat') }} @if ($sortField === 'created_at') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                        </button>
                    </th>
                    <th class="py-2 px-3">{{ __('Aksi') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($documents as $document)
                    <tr wire:click="selectRow({{ $document->id }})" class="border-b cursor-pointer transition-colors {{ $selectedId === $document->id ? 'bg-indigo-50' : 'hover:bg-gray-50' }}">
                        <td class="py-2 px-3 font-mono">{{ $document->document_number ?? '—' }}</td>
                        <td class="py-2 px-3">{{ $document->title }}</td>
                        <td class="py-2 px-3">{{ $document->status }}</td>
                        <td class="py-2 px-3">{{ $document->type->code ?? '—' }}</td>
                        <td class="py-2 px-3 font-mono">{{ $document->allocation->nks ?? $document->dsrtSample->nurt ?? '—' }}</td>
                        <td class="py-2 px-3">{{ $document->quantity }}</td>
                        <td class="py-2 px-3 whitespace-nowrap">{{ $document->created_at?->format('Y-m-d') }}</td>
                        <td class="py-2 px-3 whitespace-nowrap" wire:click.stop>
                            <a href="{{ route('documents.show', $document) }}" class="underline">{{ __('Detail') }}</a>
                            <a href="{{ route('documents.edit', $document) }}" class="underline ms-2">{{ __('Ubah') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-4 px-3 text-center text-gray-500">{{ __('Tidak ada data.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $documents->links() }}</div>
</div>
