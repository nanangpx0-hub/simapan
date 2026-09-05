<div class="space-y-4">
    <div class="flex flex-wrap items-end gap-2 text-sm">
        <div class="flex flex-col">
            <label for="alokasi-q" class="mb-1 font-medium text-gray-700">{{ __('Cari NKS/SLS') }}</label>
            <input id="alokasi-q" type="text" wire:model.live.debounce.500ms="q" placeholder="{{ __('Cari NKS/nama SLS') }}" class="border rounded px-2 py-1 w-56" />
        </div>
        <div class="flex flex-col">
            <label for="alokasi-periode" class="mb-1 font-medium text-gray-700">{{ __('Periode') }}</label>
            <select id="alokasi-periode" wire:model.live="surveyPeriodId" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua periode') }}</option>
                @foreach ($periods as $period)
                    <option value="{{ $period->id }}">{{ $period->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="alokasi-status" class="mb-1 font-medium text-gray-700">{{ __('Status') }}</label>
            <select id="alokasi-status" wire:model.live="status" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua status') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}">{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="alokasi-desa" class="mb-1 font-medium text-gray-700">{{ __('Desa') }}</label>
            <select id="alokasi-desa" wire:model.live="villageRegionId" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua desa') }}</option>
                @foreach ($villages as $village)
                    <option value="{{ $village->id }}">{{ $village->full_code }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="alokasi-perpage" class="mb-1 font-medium text-gray-700">{{ __('Per halaman') }}</label>
            <select id="alokasi-perpage" wire:model.live="perPage" class="border rounded px-2 py-1">
                @foreach (\App\Livewire\Master\AllocationTable::PER_PAGE_OPTIONS as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 pb-0.5">
            <button type="button" wire:click="resetFilters" class="underline">{{ __('Reset') }}</button>
            <a href="{{ $this->exportUrl() }}" class="underline">{{ __('Ekspor') }}</a>
        </div>
        @if ($canManage)
            <form method="POST" action="{{ route('allocations.import') }}" enctype="multipart/form-data" class="flex items-end gap-2">
                @csrf
                <div class="flex flex-col">
                    <label for="alokasi-import" class="mb-1 font-medium text-gray-700">{{ __('Impor .xlsx') }}</label>
                    <input id="alokasi-import" name="file" type="file" accept=".xlsx,.csv" class="text-xs" required />
                </div>
                <button type="submit" class="underline">{{ __('Impor') }}</button>
            </form>
        @endif
    </div>

    <p class="text-xs text-gray-500" aria-live="polite">
        {{ __('Menampilkan :from–:to dari :total data', ['from' => $allocations->firstItem() ?? 0, 'to' => $allocations->lastItem() ?? 0, 'total' => $allocations->total()]) }}
    </p>

    <div wire:loading.delay class="text-xs text-gray-500">{{ __('Memuat…') }}</div>

    <div class="overflow-x-auto rounded border border-gray-200">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50">
                <tr class="border-b">
                    @foreach (['nks' => __('NKS'), 'status' => __('Status'), 'created_at' => __('Dibuat')] as $field => $label)
                        <th class="py-2 px-3 whitespace-nowrap">
                            <button type="button" wire:click="sortBy('{{ $field }}')" class="font-semibold hover:underline focus:ring-2 focus:ring-indigo-500 rounded">
                                {{ $label }} @if ($sortField === $field) {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                            </button>
                        </th>
                    @endforeach
                    <th class="py-2 px-3">{{ __('Periode') }}</th>
                    <th class="py-2 px-3">{{ __('Jenis') }}</th>
                    <th class="py-2 px-3">{{ __('Desa') }}</th>
                    <th class="py-2 px-3">{{ __('SLS/Sub') }}</th>
                    @foreach ($roles as $role)
                        <th class="py-2 px-3">{{ $role }}</th>
                    @endforeach
                    <th class="py-2 px-3">{{ __('Aksi') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($allocations as $allocation)
                    <tr wire:click="selectRow({{ $allocation->id }})" class="border-b cursor-pointer transition-colors {{ $selectedId === $allocation->id ? 'bg-indigo-50' : 'hover:bg-gray-50' }}">
                        <td class="py-2 px-3 font-mono">{{ $allocation->nks }}</td>
                        <td class="py-2 px-3">{{ $allocation->status }}</td>
                        <td class="py-2 px-3 whitespace-nowrap">{{ $allocation->created_at?->format('Y-m-d') }}</td>
                        <td class="py-2 px-3">{{ $allocation->period->code }}</td>
                        <td class="py-2 px-3">{{ $allocation->period->surveyType->code }}</td>
                        <td class="py-2 px-3">{{ $allocation->village->full_code }}</td>
                        <td class="py-2 px-3">{{ $allocation->sls_code ?? '—' }}{{ $allocation->sub_sls_code ? '/'.$allocation->sub_sls_code : '' }}</td>
                        @foreach ($roles as $role)
                            <td class="py-2 px-3">{{ $allocation->activeAssignments->firstWhere('assignment_role', $role)?->officer->code ?? '—' }}</td>
                        @endforeach
                        <td class="py-2 px-3 whitespace-nowrap" wire:click.stop>
                            <a href="{{ route('allocations.show', $allocation) }}" class="underline">{{ __('Detail') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="py-4 px-3 text-center text-gray-500">{{ __('Tidak ada data.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $allocations->links() }}</div>
</div>
