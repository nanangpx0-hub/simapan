<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detail dokumen') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <dl class="text-sm space-y-2">
                        <div><dt class="font-medium inline">{{ __('Nomor:') }}</dt> <dd class="inline">{{ $document->document_number ?? '—' }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Judul:') }}</dt> <dd class="inline">{{ $document->title }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Jenis:') }}</dt> <dd class="inline">{{ $document->type->code }} — {{ $document->type->name }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Konteks:') }}</dt> <dd class="inline">{{ $document->allocation->nks ?? $document->dsrtSample->nus.'/'.$document->dsrtSample->nurt }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Jumlah:') }}</dt> <dd class="inline">{{ $document->quantity }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Status:') }}</dt> <dd class="inline">{{ $document->status }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Pemegang:') }}</dt> <dd class="inline">{{ $document->holder?->holder_type }} {{ $document->holder?->workUnit?->code }}{{ $document->holder?->officer?->code }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Lokasi:') }}</dt> <dd class="inline">{{ $document->holder?->location?->code ?? '—' }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Kondisi:') }}</dt> <dd class="inline">{{ $document->holder?->condition_code ?? '—' }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Catatan:') }}</dt> <dd class="inline">{{ $document->notes ?? '—' }}</dd></div>
                    </dl>
                    <div class="mt-4 flex gap-4 text-sm">
                        <a href="{{ route('documents.edit', $document) }}" class="underline">{{ __('Ubah') }}</a>
                        <a href="{{ route('document_processing_assignments.index', $document) }}" class="underline">{{ __('Penugasan') }}</a>
                    </div>
                    <h3 class="mt-6 font-semibold">{{ __('Manifest terkait') }}</h3>
                    <ul class="mt-2 text-sm space-y-1">
                        @foreach ($document->manifestItems as $item)
                            <li>{{ $item->manifest->manifest_number }} ({{ $item->manifest->status }})</li>
                        @endforeach
                    </ul>
                    <h3 class="mt-4 font-semibold">{{ __('Riwayat pemegang') }}</h3>
                    <table class="mt-2 w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Waktu') }}</th>
                                <th class="py-2">{{ __('Gerakan') }}</th>
                                <th class="py-2">{{ __('Ke') }}</th>
                                <th class="py-2">{{ __('Kondisi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($document->holderHistories()->orderByDesc('id')->get() as $history)
                                <tr class="border-b">
                                    <td class="py-2">{{ $history->moved_at }}</td>
                                    <td class="py-2">{{ $history->movement_type }}</td>
                                    <td class="py-2">{{ $history->to_holder_type }}</td>
                                    <td class="py-2">{{ $history->condition_after }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <h3 class="mt-4 font-semibold">{{ __('Penugasan pengolahan') }}</h3>
                    <ul class="mt-2 text-sm space-y-1">
                        @foreach ($document->processingAssignments as $assignment)
                            <li>{{ $assignment->officer->code }} ({{ $assignment->status }})</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
