<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detail manifest') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <dl class="text-sm space-y-2">
                        <div><dt class="font-medium inline">{{ __('Nomor:') }}</dt> <dd class="inline">{{ $manifest->manifest_number }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Dari/Ke:') }}</dt> <dd class="inline">{{ $manifest->fromUnit->code }} → {{ $manifest->toUnit->code }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Status:') }}</dt> <dd class="inline">{{ $manifest->status }}</dd></div>
                    </dl>
                    <div class="mt-4 flex gap-4 text-sm">
                        <a href="{{ route('document_manifests.edit', $manifest) }}" class="underline">{{ __('Ubah') }}</a>
                        <a href="{{ route('document_transfers.show', $manifest) }}" class="underline">{{ __('Serah terima') }}</a>
                        @if ($manifest->status === 'DRAFT')
                            <form method="POST" action="{{ route('document_manifests.submit', $manifest) }}">
                                @csrf
                                <button type="submit" class="underline">{{ __('Submit') }}</button>
                            </form>
                        @endif
                    </div>
                    @if ($errors->any())
                        <ul class="mt-4 text-sm text-red-600">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <h3 class="mt-6 font-semibold">{{ __('Item (:count/200)', ['count' => $manifest->items->count()]) }}</h3>
                    <table class="mt-2 w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Dokumen') }}</th>
                                <th class="py-2">{{ __('Qty') }}</th>
                                <th class="py-2">{{ __('Kondisi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($manifest->items as $item)
                                <tr class="border-b">
                                    <td class="py-2">{{ $item->document->title }}</td>
                                    <td class="py-2">{{ $item->qty_sent }}</td>
                                    <td class="py-2">{{ $item->condition_sent }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if ($manifest->status === 'DRAFT')
                        <h3 class="mt-4 font-semibold">{{ __('Tambah item') }}</h3>
                        <form method="POST" action="{{ route('document_manifests.items.store', $manifest) }}" class="mt-2 flex flex-wrap gap-2 text-sm items-end">
                            @csrf
                            <div>
                                <label for="document_id" class="block font-medium">{{ __('Dokumen') }}</label>
                                <select id="document_id" name="document_id" required class="mt-1 border rounded px-2 py-1">
                                    <option value="">{{ __('— Pilih —') }}</option>
                                    @foreach ($documents as $document)
                                        <option value="{{ $document->id }}">{{ $document->title }} ({{ $document->status }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="qty_sent" class="block font-medium">{{ __('Qty') }}</label>
                                <input id="qty_sent" name="qty_sent" type="number" min="1" value="1" required class="mt-1 border rounded px-2 py-1 w-24" />
                            </div>
                            <div>
                                <label for="condition_sent" class="block font-medium">{{ __('Kondisi') }}</label>
                                <select id="condition_sent" name="condition_sent" required class="mt-1 border rounded px-2 py-1">
                                    @foreach ($conditions as $condition)
                                        <option value="{{ $condition }}">{{ $condition }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="underline">{{ __('Tambah') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
