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
            <label for="period-search" class="mb-1 font-medium text-gray-700">{{ __('Cari kode/nama') }}</label>
            <input id="period-search" type="text" wire:model.live.debounce.500ms="q" placeholder="{{ __('Cari kode/nama') }}" class="border rounded px-2 py-1 w-56" />
        </div>
        <div class="flex flex-col">
            <label for="period-type" class="mb-1 font-medium text-gray-700">{{ __('Jenis') }}</label>
            <select id="period-type" wire:model.live="surveyTypeId" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua jenis') }}</option>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}">{{ $type->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="period-status" class="mb-1 font-medium text-gray-700">{{ __('Status') }}</label>
            <select id="period-status" wire:model.live="status" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua status') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}">{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="period-year" class="mb-1 font-medium text-gray-700">{{ __('Tahun') }}</label>
            <input id="period-year" type="number" wire:model.live="year" placeholder="{{ __('Tahun') }}" class="border rounded px-2 py-1 w-28" />
        </div>
        <div class="flex flex-col">
            <label for="period-perpage" class="mb-1 font-medium text-gray-700">{{ __('Per halaman') }}</label>
            <select id="period-perpage" wire:model.live="perPage" class="border rounded px-2 py-1">
                @foreach (\App\Livewire\Master\SurveyPeriodTable::PER_PAGE_OPTIONS as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 pb-0.5">
            <button type="button" wire:click="resetFilters" class="underline">{{ __('Reset') }}</button>
            <a href="{{ $this->exportUrl() }}" class="underline">{{ __('Ekspor') }}</a>
        </div>
        @if ($canManage)
            <form method="POST" action="{{ route('master.survey_periods.import') }}" enctype="multipart/form-data" class="flex items-end gap-2">
                @csrf
                <div class="flex flex-col">
                    <label for="period-import" class="mb-1 font-medium text-gray-700">{{ __('Impor .xlsx') }}</label>
                    <input id="period-import" name="file" type="file" accept=".xlsx,.csv" class="text-xs" required />
                </div>
                <button type="submit" class="underline">{{ __('Impor') }}</button>
            </form>
        @endif
    </div>

    <p class="text-xs text-gray-500" aria-live="polite">
        {{ __('Menampilkan :from–:to dari :total data', ['from' => $periods->firstItem() ?? 0, 'to' => $periods->lastItem() ?? 0, 'total' => $periods->total()]) }}
    </p>

    <div wire:loading.delay class="text-xs text-gray-500">{{ __('Memuat…') }}</div>

    <div class="overflow-x-auto rounded border border-gray-200">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50">
                <tr class="border-b">
                    @foreach (['code' => __('Kode'), 'year' => __('Tahun'), 'status' => __('Status')] as $field => $label)
                        <th class="py-2 px-3 whitespace-nowrap">
                            <button type="button" wire:click="sortBy('{{ $field }}')" class="font-semibold hover:underline focus:ring-2 focus:ring-indigo-500 rounded">
                                {{ $label }} @if ($sortField === $field) {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                            </button>
                        </th>
                    @endforeach
                    <th class="py-2 px-3">{{ __('Jenis') }}</th>
                    <th class="py-2 px-3">{{ __('Nama') }}</th>
                    <th class="py-2 px-3">{{ __('Tipe/No') }}</th>
                    <th class="py-2 px-3">{{ __('Periode') }}</th>
                    <th class="py-2 px-3">{{ __('Aksi') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($periods as $period)
                    <tr wire:click="selectRow({{ $period->id }})" class="border-b cursor-pointer transition-colors {{ $selectedId === $period->id ? 'bg-indigo-50' : 'hover:bg-gray-50' }}">
                        <td class="py-2 px-3 font-mono">{{ $period->code }}</td>
                        <td class="py-2 px-3">{{ $period->year }}</td>
                        <td class="py-2 px-3">{{ $period->status }}</td>
                        <td class="py-2 px-3">{{ $period->surveyType->code }}</td>
                        <td class="py-2 px-3">{{ $period->name }}</td>
                        <td class="py-2 px-3 whitespace-nowrap">{{ $period->period_type }}{{ $period->period_number !== null ? ' '.$period->period_number : '' }}</td>
                        <td class="py-2 px-3 whitespace-nowrap">{{ $period->start_date?->format('Y-m-d') }} — {{ $period->end_date?->format('Y-m-d') }}</td>
                        <td class="py-2 px-3 whitespace-nowrap" wire:click.stop>
                            <a href="{{ route('master.survey_periods.show', $period) }}" class="underline">{{ __('Detail') }}</a>
                            <a href="{{ route('master.survey_periods.edit', $period) }}" class="underline ms-2">{{ __('Ubah') }}</a>
                            @if ($canManage)
                                <button type="button" wire:click="delete({{ $period->id }})" wire:confirm="Yakin ingin menghapus periode survei ini?" class="underline ms-2 text-red-600">{{ __('Hapus') }}</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-4 px-3 text-center text-gray-500">{{ __('Tidak ada data.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $periods->links() }}</div>
</div>
