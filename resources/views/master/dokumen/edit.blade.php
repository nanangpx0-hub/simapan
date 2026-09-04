<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ubah dokumen') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4 text-sm text-gray-600">{{ __('Hanya dokumen REGISTERED yang dapat diubah.') }}</p>
                    <form method="POST" action="{{ route('documents.update', $document) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <label for="document_type_id" class="block text-sm font-medium">{{ __('Jenis dokumen') }}</label>
                            <select id="document_type_id" name="document_type_id" required class="mt-1 block w-full border rounded px-3 py-2">
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}" @selected((string) old('document_type_id', (string) $document->document_type_id) === (string) $type->id)>{{ $type->code }} — {{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="document_number" class="block text-sm font-medium">{{ __('Nomor dokumen') }}</label>
                            <input id="document_number" name="document_number" type="text" value="{{ old('document_number', $document->document_number) }}" class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="title" class="block text-sm font-medium">{{ __('Judul') }}</label>
                            <input id="title" name="title" type="text" value="{{ old('title', $document->title) }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="quantity" class="block text-sm font-medium">{{ __('Jumlah') }}</label>
                            <input id="quantity" name="quantity" type="number" min="1" value="{{ old('quantity', (string) $document->quantity) }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="notes" class="block text-sm font-medium">{{ __('Catatan') }}</label>
                            <textarea id="notes" name="notes" class="mt-1 block w-full border rounded px-3 py-2">{{ old('notes', $document->notes) }}</textarea>
                        </div>
                        @if ($errors->any())
                            <ul class="mb-4 text-sm text-red-600">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <button type="submit" class="underline">{{ __('Simpan') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
