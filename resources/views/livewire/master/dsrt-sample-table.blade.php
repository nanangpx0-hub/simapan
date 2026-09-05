<div class="space-y-4">
    <div class="flex flex-wrap items-end gap-2 text-sm">
        <div class="flex flex-col">
            <label for="dsrt-search" class="mb-1 font-medium text-gray-700">{{ __('Cari NUS/NURT/nama') }}</label>
            <input id="dsrt-search" type="text" wire:model.live.debounce.500ms="q" placeholder="{{ __('Cari NUS/NURT/nama') }}" class="border rounded px-2 py-1 w-56" />
        </div>
        <div class="flex flex-col">
            <label for="dsrt-record" class="mb-1 font-medium text-gray-700">{{ __('Status') }}</label>
            <select id="dsrt-record" wire:model.live="recordStatus" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua status') }}</option>
                @foreach ($recordStatuses as $status)
                    <option value="{{ $status }}">{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="dsrt-enum" class="mb-1 font-medium text-gray-700">{{ __('Pencacahan') }}</label>
            <select id="dsrt-enum" wire:model.live="enumerationStatus" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua pencacahan') }}</option>
                @foreach ($enumerationStatuses as $status)
                    <option value="{{ $status }}">{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="dsrt-perpage" class="mb-1 font-medium text-gray-700">{{ __('Per halaman') }}</label>
            <select id="dsrt-perpage" wire:model.live="perPage" class="border rounded px-2 py-1">
                @foreach (\App\Livewire\Master\DsrtSampleTable::PER_PAGE_OPTIONS as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 pb-0.5">
            <button type="button" wire:click="resetFilters" class="underline">{{ __('Reset') }}</button>
            <a href="{{ $this->exportUrl() }}" class="underline">{{ __('Ekspor') }}</a>
        </div>
        @if ($canManage)
            <form method="POST" action="{{ route('allocations.dsrt.import', $allocation) }}" enctype="multipart/form-data" class="flex items-end gap-2">
                @csrf
                <div class="flex flex-col">
                    <label for="dsrt-import" class="mb-1 font-medium text-gray-700">{{ __('Impor .xlsx') }}</label>
                    <input id="dsrt-import" name="file" type="file" accept=".xlsx,.csv" class="text-xs" required />
                </div>
                <button type="submit" class="underline">{{ __('Impor') }}</button>
            </form>
        @endif
    </div>

    <p class="text-xs text-gray-500" aria-live="polite">
        {{ __('Menampilkan :from–:to dari :total data', ['from' => $samples->firstItem() ?? 0, 'to' => $samples->lastItem() ?? 0, 'total' => $samples->total()]) }}
    </p>

    <div wire:loading.delay class="text-xs text-gray-500">{{ __('Memuat…') }}</div>

    <div class="overflow-x-auto rounded border border-gray-200">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50">
                <tr class="border-b">
                    @foreach (['nus' => __('NUS'), 'nurt' => __('NURT'), 'krt_name' => __('Nama KRT')] as $field => $label)
                        <th class="py-2 px-3 whitespace-nowrap">
                            <button type="button" wire:click="sortBy('{{ $field }}')" class="font-semibold hover:underline focus:ring-2 focus:ring-indigo-500 rounded">
                                {{ $label }} @if ($sortField === $field) {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                            </button>
                        </th>
                    @endforeach
                    <th class="py-2 px-3 whitespace-nowrap">
                        <button type="button" wire:click="sortBy('record_status')" class="font-semibold hover:underline focus:ring-2 focus:ring-indigo-500 rounded">
                            {{ __('Record') }} @if ($sortField === 'record_status') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                        </button>
                    </th>
                    <th class="py-2 px-3">{{ __('Cacah') }}</th>
                    <th class="py-2 px-3">{{ __('Kontak') }}</th>
                    <th class="py-2 px-3">{{ __('Aksi') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($samples as $sample)
                    <tr wire:click="selectRow({{ $sample->id }})" class="border-b cursor-pointer transition-colors {{ $selectedId === $sample->id ? 'bg-indigo-50' : 'hover:bg-gray-50' }}">
                        <td class="py-2 px-3 font-mono">{{ $sample->nus }}</td>
                        <td class="py-2 px-3 font-mono">{{ $sample->nurt }}</td>
                        <td class="py-2 px-3">{{ $sample->krt_name }}</td>
                        <td class="py-2 px-3">{{ $sample->record_status }}</td>
                        <td class="py-2 px-3">{{ $sample->enumeration_status }}</td>
                        <td class="py-2 px-3">{{ $sample->maskedContactPhone() }}</td>
                        <td class="py-2 px-3 whitespace-nowrap" wire:click.stop>
                            <a href="{{ route('allocations.dsrt.show', [$allocation, $sample]) }}" class="underline">{{ __('Detail') }}</a>
                            <a href="{{ route('allocations.dsrt.edit', [$allocation, $sample]) }}" class="underline ms-2">{{ __('Ubah') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-4 px-3 text-center text-gray-500">{{ __('Tidak ada data.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $samples->links() }}</div>
</div>
