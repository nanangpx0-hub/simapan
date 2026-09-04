<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Serah terima manifest') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <dl class="text-sm space-y-2">
                        <div><dt class="font-medium inline">{{ __('Manifest:') }}</dt> <dd class="inline">{{ $manifest->manifest_number }} ({{ $manifest->status }})</dd></div>
                        <div><dt class="font-medium inline">{{ __('Hasil:') }}</dt> <dd class="inline">{{ $transfer->receipt_result ?? $transfer->transfer_status }}</dd></div>
                    </dl>
                    <table class="mt-4 w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Dokumen') }}</th>
                                <th class="py-2">{{ __('Kirim') }}</th>
                                <th class="py-2">{{ __('Terima') }}</th>
                                <th class="py-2">{{ __('Kondisi') }}</th>
                                <th class="py-2">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transfer->items as $item)
                                <tr class="border-b">
                                    <td class="py-2">{{ $item->manifestItem->document->title }}</td>
                                    <td class="py-2">{{ $item->qty_sent }}</td>
                                    <td class="py-2">{{ $item->qty_received }}</td>
                                    <td class="py-2">{{ $item->condition_received ?? '—' }}</td>
                                    <td class="py-2">{{ $item->receipt_status }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4 text-sm">
                        <a href="{{ route('document_transfers.edit', $manifest) }}" class="underline">{{ __('Periksa penerimaan') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
