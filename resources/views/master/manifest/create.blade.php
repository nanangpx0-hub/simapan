<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Buat manifest') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('document_manifests.store') }}">
                        @csrf
                        <div class="mb-4 flex gap-2">
                            <div class="w-1/2">
                                <label for="from_work_unit_id" class="block text-sm font-medium">{{ __('Unit pengirim') }}</label>
                                <select id="from_work_unit_id" name="from_work_unit_id" required class="mt-1 block w-full border rounded px-3 py-2">
                                    <option value="">{{ __('— Pilih unit —') }}</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}" @selected((string) old('from_work_unit_id') === (string) $unit->id)>{{ $unit->code }} — {{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-1/2">
                                <label for="to_work_unit_id" class="block text-sm font-medium">{{ __('Unit penerima') }}</label>
                                <select id="to_work_unit_id" name="to_work_unit_id" required class="mt-1 block w-full border rounded px-3 py-2">
                                    <option value="">{{ __('— Pilih unit —') }}</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}" @selected((string) old('to_work_unit_id') === (string) $unit->id)>{{ $unit->code }} — {{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @if ($errors->any())
                            <ul class="mb-4 text-sm text-red-600">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <button type="submit" class="underline">{{ __('Simpan sebagai DRAFT') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
