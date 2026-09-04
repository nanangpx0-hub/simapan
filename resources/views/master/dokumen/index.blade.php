<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dokumen Fisik') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <p>{{ __('Daftar dokumen fisik.') }}</p>
                        <a href="{{ route('documents.create') }}" class="underline">{{ __('Tambah dokumen') }}</a>
                    </div>
                    <form method="GET" action="{{ route('documents.index') }}" class="mb-4 flex flex-wrap gap-2 text-sm">
                        <select name="status" class="border rounded px-2 py-1">
                            <option value="">{{ __('Semua status') }}</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                        <select name="document_type_id" class="border rounded px-2 py-1">
                            <option value="">{{ __('Semua jenis') }}</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->id }}" @selected((string) ($filters['document_type_id'] ?? '') === (string) $type->id)>{{ $type->code }}</option>
                            @endforeach
                        </select>
                        <input name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Cari nomor/judul') }}" class="border rounded px-2 py-1" />
                        <button type="submit" class="underline">{{ __('Filter') }}</button>
                    </form>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Nomor') }}</th>
                                <th class="py-2">{{ __('Judul') }}</th>
                                <th class="py-2">{{ __('Jenis') }}</th>
                                <th class="py-2">{{ __('Konteks') }}</th>
                                <th class="py-2">{{ __('Qty') }}</th>
                                <th class="py-2">{{ __('Status') }}</th>
                                <th class="py-2">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($documents as $document)
                                <tr class="border-b">
                                    <td class="py-2">{{ $document->document_number ?? '—' }}</td>
                                    <td class="py-2">{{ $document->title }}</td>
                                    <td class="py-2">{{ $document->type->code }}</td>
                                    <td class="py-2">{{ $document->allocation->nks ?? $document->dsrtSample->nurt ?? '—' }}</td>
                                    <td class="py-2">{{ $document->quantity }}</td>
                                    <td class="py-2">{{ $document->status }}</td>
                                    <td class="py-2">
                                        <a href="{{ route('documents.show', $document) }}" class="underline">{{ __('Detail') }}</a>
                                        <a href="{{ route('documents.edit', $document) }}" class="underline ms-2">{{ __('Ubah') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $documents->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
