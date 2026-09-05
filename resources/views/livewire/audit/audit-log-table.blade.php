<div class="space-y-4">
    <div class="flex flex-wrap items-end gap-2 text-sm">
        <div class="flex flex-col">
            <label for="audit-from" class="mb-1 font-medium text-gray-700">{{ __('Dari') }}</label>
            <input id="audit-from" type="date" wire:model.live="dateFrom" class="border rounded px-2 py-1" />
        </div>
        <div class="flex flex-col">
            <label for="audit-to" class="mb-1 font-medium text-gray-700">{{ __('Sampai') }}</label>
            <input id="audit-to" type="date" wire:model.live="dateTo" class="border rounded px-2 py-1" />
        </div>
        <div class="flex flex-col">
            <label for="audit-user" class="mb-1 font-medium text-gray-700">{{ __('Pelaku') }}</label>
            <select id="audit-user" wire:model.live="userId" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua pelaku') }}</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="audit-action" class="mb-1 font-medium text-gray-700">{{ __('Aksi') }}</label>
            <select id="audit-action" wire:model.live="action" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua aksi') }}</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}">{{ $action }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="audit-type" class="mb-1 font-medium text-gray-700">{{ __('Model') }}</label>
            <select id="audit-type" wire:model.live="auditableType" class="border rounded px-2 py-1">
                <option value="">{{ __('Semua model') }}</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}">{{ class_basename($type) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="audit-id" class="mb-1 font-medium text-gray-700">{{ __('ID objek') }}</label>
            <input id="audit-id" type="number" wire:model.live.debounce.500ms="auditableId" placeholder="{{ __('ID objek') }}" class="border rounded px-2 py-1 w-24" />
        </div>
        <div class="flex flex-col">
            <label for="audit-uuid" class="mb-1 font-medium text-gray-700">{{ __('Event UUID') }}</label>
            <input id="audit-uuid" type="text" wire:model.live.debounce.500ms="eventUuid" placeholder="{{ __('Event UUID') }}" class="border rounded px-2 py-1" />
        </div>
        <div class="flex flex-col">
            <label for="audit-perpage" class="mb-1 font-medium text-gray-700">{{ __('Per halaman') }}</label>
            <select id="audit-perpage" wire:model.live="perPage" class="border rounded px-2 py-1">
                @foreach (\App\Livewire\Audit\AuditLogTable::PER_PAGE_OPTIONS as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 pb-0.5">
            <button type="button" wire:click="resetFilters" class="underline">{{ __('Reset') }}</button>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 text-xs">
        <span class="text-gray-500">{{ __('Pintas pimpinan:') }}</span>
        <button type="button" wire:click="preset('alokasi')" class="underline">{{ __('Perubahan status alokasi') }}</button>
        <button type="button" wire:click="preset('manifest')" class="underline">{{ __('Serah terima manifest') }}</button>
        <button type="button" wire:click="preset('petugas')" class="underline">{{ __('Pergantian petugas') }}</button>
    </div>

    <p class="text-xs text-gray-500" aria-live="polite">
        {{ __('Menampilkan :from–:to dari :total data', ['from' => $logs->firstItem() ?? 0, 'to' => $logs->lastItem() ?? 0, 'total' => $logs->total()]) }}
    </p>

    <div wire:loading.delay class="text-xs text-gray-500">{{ __('Memuat…') }}</div>

    <div class="overflow-x-auto rounded border border-gray-200">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50">
                <tr class="border-b">
                    <th class="py-2 px-3 whitespace-nowrap">
                        <button type="button" wire:click="sortBy('created_at')" class="font-semibold hover:underline focus:ring-2 focus:ring-indigo-500 rounded">
                            {{ __('Waktu') }} @if ($sortField === 'created_at') {{ $sortDir === 'asc' ? '↑' : '↓' }} @endif
                        </button>
                    </th>
                    <th class="py-2 px-3">{{ __('Pelaku') }}</th>
                    <th class="py-2 px-3">{{ __('Aksi') }}</th>
                    <th class="py-2 px-3">{{ __('Objek') }}</th>
                    <th class="py-2 px-3">{{ __('ID') }}</th>
                    <th class="py-2 px-3">{{ __('Perubahan') }}</th>
                    <th class="py-2 px-3">{{ __('Aksi') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr wire:click="selectRow({{ $log->id }})" class="border-b cursor-pointer transition-colors {{ $selectedId === $log->id ? 'bg-indigo-50' : 'hover:bg-gray-50' }}">
                        <td class="py-2 px-3 whitespace-nowrap">{{ $log->created_at }}</td>
                        <td class="py-2 px-3">{{ $log->actorName() }}</td>
                        <td class="py-2 px-3">{{ $log->action }}</td>
                        <td class="py-2 px-3">{{ $log->objectLabel() }}</td>
                        <td class="py-2 px-3">{{ $log->auditable_id }}</td>
                        <td class="py-2 px-3">{{ implode(', ', $log->changedKeys()) }}</td>
                        <td class="py-2 px-3 whitespace-nowrap" wire:click.stop>
                            <a href="{{ route('audit_logs.show', $log) }}" class="underline">{{ __('Detail') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-4 px-3 text-center text-gray-500">{{ __('Tidak ada data.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</div>
