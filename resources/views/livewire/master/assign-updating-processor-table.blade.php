<div class="space-y-4">
    <h3 class="font-semibold">{{ __('Penugasan Pengolah (kolom Pengolah)') }}</h3>

    @if (session('status'))
        <p class="text-sm text-emerald-700">{{ session('status') }}</p>
    @endif

    @error('officerId')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
    @error('selected')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

    <div class="flex flex-wrap items-end gap-2 text-sm">
        <div class="flex flex-col">
            <label for="processor-officer" class="mb-1 font-medium text-gray-700">{{ __('Petugas pengolahan') }}</label>
            <select id="processor-officer" wire:model="officerId" class="border rounded px-2 py-1">
                <option value="">{{ __('— Pilih —') }}</option>
                @foreach ($officers as $officer)
                    <option value="{{ $officer->id }}">{{ $officer->code }} — {{ $officer->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2 pb-0.5">
            <button type="button" wire:click="toggleAll" class="underline">{{ __('Pilih semua diterima') }}</button>
            <button type="button" wire:click="assign" class="underline">{{ __('Tugaskan terpilih') }}</button>
        </div>
    </div>

    <div class="overflow-x-auto rounded border border-gray-200">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50">
                <tr class="border-b">
                    <th class="py-2 px-3">{{ __('Pilih') }}</th>
                    <th class="py-2 px-3">{{ __('NKS') }}</th>
                    <th class="py-2 px-3">{{ __('Ruta') }}</th>
                    <th class="py-2 px-3">{{ __('VSEN26.P') }}</th>
                    <th class="py-2 px-3">{{ __('Peta WS') }}</th>
                    <th class="py-2 px-3">{{ __('Status') }}</th>
                    <th class="py-2 px-3">{{ __('Pengolah') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr class="border-b">
                        <td class="py-2 px-3">
                            @if ($item->delivery_status === 'RECEIVED_BY_PLS')
                                <input type="checkbox" value="{{ $item->id }}" wire:model.live="selected" />
                            @endif
                        </td>
                        <td class="py-2 px-3 font-mono">{{ $item->nks }}</td>
                        <td class="py-2 px-3">{{ $item->household_count_listing }}</td>
                        <td class="py-2 px-3">{{ $item->has_vsen_p ? '✓' : '—' }}</td>
                        <td class="py-2 px-3">{{ $item->has_peta_ws ? '✓' : '—' }}</td>
                        <td class="py-2 px-3">{{ $item->delivery_status }}</td>
                        <td class="py-2 px-3">{{ $item->processor?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-4 px-3 text-center text-gray-500">{{ __('Tidak ada data.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
