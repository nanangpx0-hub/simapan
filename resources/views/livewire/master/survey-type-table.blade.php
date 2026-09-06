<div class="space-y-4">
    @if (session('status'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded text-sm">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-wrap items-end gap-2 text-sm">
        <div class="flex flex-col">
            <label for="survey-type-search" class="mb-1 font-medium text-gray-700">{{ __('Cari kode/nama') }}</label>
            <input id="survey-type-search" type="text" wire:model.live.debounce.500ms="q" placeholder="{{ __('Cari kode/nama') }}" class="border rounded px-2 py-1 w-56" />
        </div>
        <div class="flex flex-col">
            <label for="survey-type-status" class="mb-1 font-medium text-gray-700">{{ __('Status') }}</label>
            <select id="survey-type-status" wire:model.live="status" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua status') }}</option>
                <option value="aktif">{{ __('Aktif') }}</option>
                <option value="nonaktif">{{ __('Nonaktif') }}</option>
            </select>
        </div>
        <div class="flex flex-col">
            <label for="survey-type-perpage" class="mb-1 font-medium text-gray-700">{{ __('Per halaman') }}</label>
            <select id="survey-type-perpage" wire:model.live="perPage" class="border rounded px-2 py-1">
                @foreach (\App\Livewire\Master\SurveyTypeTable::PER_PAGE_OPTIONS as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 pb-0.5">
            <button type="button" wire:click="resetFilters" class="underline">{{ __('Reset') }}</button>
            <a href="{{ $this->exportUrl() }}" class="underline">{{ __('Ekspor') }}</a>
        </div>
        @if ($canManage)
            <form method="POST" action="{{ route('master.jenis-survei.import') }}" enctype="multipart/form-data" class="flex items-end gap-2">
                @csrf
                <div class="flex flex-col">
                    <label for="survey-type-import" class="mb-1 font-medium text-gray-700">{{ __('Impor .xlsx') }}</label>
                    <input id="survey-type-import" name="file" type="file" accept=".xlsx,.csv" class="text-xs" required />
                </div>
                <button type="submit" class="underline">{{ __('Impor') }}</button>
            </form>
        @endif
    </div>

    <p class="text-xs text-gray-500" aria-live="polite">
        {{ __('Menampilkan :from–:to dari :total data', ['from' => $types->firstItem() ?? 0, 'to' => $types->lastItem() ?? 0, 'total' => $types->total()]) }}
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
                    <th class="py-2 px-3">{{ __('Deskripsi') }}</th>
                    <th class="py-2 px-3">{{ __('Status') }}</th>
                    <th class="py-2 px-3">{{ __('Aksi') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($types as $type)
                    <tr wire:click="selectRow({{ $type->id }})" class="border-b cursor-pointer transition-colors {{ $selectedId === $type->id ? 'bg-indigo-50' : 'hover:bg-gray-50' }}">
                        <td class="py-2 px-3 font-mono">{{ $type->code }}</td>
                        <td class="py-2 px-3">{{ $type->name }}</td>
                        <td class="py-2 px-3 whitespace-nowrap">{{ $type->created_at?->format('Y-m-d') }}</td>
                        <td class="py-2 px-3">{{ \Illuminate\Support\Str::limit($type->description, 80) }}</td>
                        <td class="py-2 px-3">{{ $type->is_active ? __('Aktif') : __('Nonaktif') }}</td>
                        <td class="py-2 px-3 whitespace-nowrap" wire:click.stop>
                            <a href="{{ route('master.jenis-survei.edit', $type) }}" class="underline">{{ __('Ubah') }}</a>
                            @if ($canManage)
                                <button type="button" wire:click="delete({{ $type->id }})" wire:confirm="Yakin ingin menghapus jenis survei ini?" class="underline ms-2 text-red-600">{{ __('Hapus') }}</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-4 px-3 text-center text-gray-500">{{ __('Tidak ada data.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $types->links() }}</div>
</div>
