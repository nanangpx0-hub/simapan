<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tambah dokumen') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('documents.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label for="document_type_id" class="block text-sm font-medium">{{ __('Jenis dokumen') }}</label>
                            <select id="document_type_id" name="document_type_id" required class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="">{{ __('— Pilih jenis —') }}</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}" @selected((string) old('document_type_id') === (string) $type->id)>{{ $type->code }} — {{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4 flex gap-2">
                            <div class="w-1/2">
                                <label for="allocation_id" class="block text-sm font-medium">{{ __('Alokasi (atau DSRT)') }}</label>
                                <select id="allocation_id" name="allocation_id" class="mt-1 block w-full border rounded px-3 py-2">
                                    <option value="">{{ __('— Tanpa alokasi —') }}</option>
                                    @foreach ($allocations as $allocation)
                                        <option value="{{ $allocation->id }}" @selected((string) old('allocation_id') === (string) $allocation->id)>{{ $allocation->nks }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-1/2">
                                <label for="dsrt_sample_id" class="block text-sm font-medium">{{ __('Sampel DSRT (atau alokasi)') }}</label>
                                <select id="dsrt_sample_id" name="dsrt_sample_id" class="mt-1 block w-full border rounded px-3 py-2">
                                    <option value="">{{ __('— Tanpa sampel —') }}</option>
                                    @foreach ($samples as $sample)
                                        <option value="{{ $sample->id }}" @selected((string) old('dsrt_sample_id') === (string) $sample->id)>{{ $sample->nus }}/{{ $sample->nurt }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="document_number" class="block text-sm font-medium">{{ __('Nomor dokumen (opsional)') }}</label>
                            <input id="document_number" name="document_number" type="text" value="{{ old('document_number') }}" class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="title" class="block text-sm font-medium">{{ __('Judul') }}</label>
                            <input id="title" name="title" type="text" value="{{ old('title') }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4 flex gap-2">
                            <div class="w-1/2">
                                <label for="quantity" class="block text-sm font-medium">{{ __('Jumlah') }}</label>
                                <input id="quantity" name="quantity" type="number" min="1" value="{{ old('quantity', '1') }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                            </div>
                            <div class="w-1/2">
                                <label for="condition_code" class="block text-sm font-medium">{{ __('Kondisi awal') }}</label>
                                <select id="condition_code" name="condition_code" class="mt-1 block w-full border rounded px-3 py-2">
                                    @foreach ($conditions as $condition)
                                        <option value="{{ $condition }}" @selected(old('condition_code', 'GOOD') === $condition)>{{ $condition }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="document_location_id" class="block text-sm font-medium">{{ __('Lokasi awal (opsional)') }}</label>
                            <select id="document_location_id" name="document_location_id" class="mt-1 block w-full border rounded px-3 py-2">
                                <option value="">{{ __('— Tanpa lokasi —') }}</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}" @selected((string) old('document_location_id') === (string) $location->id)>{{ $location->code }} — {{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="notes" class="block text-sm font-medium">{{ __('Catatan') }}</label>
                            <textarea id="notes" name="notes" class="mt-1 block w-full border rounded px-3 py-2">{{ old('notes') }}</textarea>
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
