<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Periksa penerimaan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4 text-sm text-gray-600">{{ __('Manifest: :number', ['number' => $manifest->manifest_number]) }}</p>
                    <form method="POST" action="{{ route('document_transfers.update', $manifest) }}">
                        @csrf
                        @method('PUT')
                        @foreach ($transfer->items as $item)
                            <div class="mb-4 border rounded p-3">
                                <p class="text-sm font-medium">{{ $item->manifestItem->document->title }} (kirim: {{ $item->qty_sent }})</p>
                                <div class="mt-2 flex flex-wrap gap-2 text-sm">
                                    <div>
                                        <label class="block font-medium" for="qty-{{ $item->id }}">{{ __('Qty terima') }}</label>
                                        <input id="qty-{{ $item->id }}" name="items[{{ $item->id }}][qty_received]" type="number" min="0" max="{{ $item->qty_sent }}" value="{{ old('items.'.$item->id.'.qty_received', (string) $item->qty_received) }}" required class="mt-1 border rounded px-2 py-1 w-24" />
                                    </div>
                                    <div>
                                        <label class="block font-medium" for="cond-{{ $item->id }}">{{ __('Kondisi') }}</label>
                                        <select id="cond-{{ $item->id }}" name="items[{{ $item->id }}][condition_received]" required class="mt-1 border rounded px-2 py-1">
                                            @foreach ($conditions as $condition)
                                                <option value="{{ $condition }}" @selected(old('items.'.$item->id.'.condition_received', $item->condition_received) === $condition)>{{ $condition }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-medium" for="status-{{ $item->id }}">{{ __('Status') }}</label>
                                        <select id="status-{{ $item->id }}" name="items[{{ $item->id }}][receipt_status]" required class="mt-1 border rounded px-2 py-1">
                                            @foreach ($receiptStatuses as $status)
                                                <option value="{{ $status }}" @selected(old('items.'.$item->id.'.receipt_status', $item->receipt_status) === $status)>{{ $status }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="w-full">
                                        <label class="block font-medium" for="note-{{ $item->id }}">{{ __('Catatan (wajib bila bukan COMPLETE)') }}</label>
                                        <input id="note-{{ $item->id }}" name="items[{{ $item->id }}][receipt_note]" type="text" value="{{ old('items.'.$item->id.'.receipt_note', $item->receipt_note) }}" class="mt-1 block w-full border rounded px-2 py-1" />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="mb-4">
                            <label for="document_location_id" class="block text-sm font-medium">{{ __('Lokasi penerimaan (opsional)') }}</label>
                            <select id="document_location_id" name="document_location_id" class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="">{{ __('— Tanpa lokasi —') }}</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->code }} — {{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="receipt_note" class="block text-sm font-medium">{{ __('Catatan manifest (wajib bila tolak semua)') }}</label>
                            <textarea id="receipt_note" name="receipt_note" class="mt-1 block w-full border rounded px-3 py-2"></textarea>
                        </div>
                        @if ($errors->any())
                            <ul class="mb-4 text-sm text-red-600">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <button type="submit" class="underline">{{ __('Simpan pemeriksaan') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
