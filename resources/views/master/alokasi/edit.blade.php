<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ubah alokasi') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4 text-sm text-gray-600">{{ __('NKS/SLS/Sub-SLS/periode/desa terkunci. Nama dan catatan dapat diubah saat DRAFT/ACTIVE.') }}</p>
                    <form method="POST" action="{{ route('allocations.update', $allocation) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-4">
                            <span class="block text-sm font-medium">{{ __('NKS / Periode / Desa (terkunci)') }}</span>
                            <p class="mt-1 text-sm">{{ $allocation->nks }} — {{ $allocation->period->code }} — {{ $allocation->village->full_code }}</p>
                        </div>
                        <div class="mb-4">
                            <label for="sls_name" class="block text-sm font-medium">{{ __('Nama SLS') }}</label>
                            <input id="sls_name" name="sls_name" type="text" value="{{ old('sls_name', $allocation->sls_name) }}" required class="mt-1 block w-full border rounded px-3 py-2" />
                        </div>
                        <div class="mb-4">
                            <label for="notes" class="block text-sm font-medium">{{ __('Catatan') }}</label>
                            <textarea id="notes" name="notes" class="mt-1 block w-full border rounded px-3 py-2">{{ old('notes', $allocation->notes) }}</textarea>
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
