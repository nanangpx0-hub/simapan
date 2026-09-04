<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Penugasan dokumen') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4 text-sm">{{ __('Dokumen: :title (:status)', ['title' => $document->title, 'status' => $document->status]) }}</p>
                    <h3 class="font-semibold">{{ __('Aktif') }}</h3>
                    <ul class="mt-2 text-sm space-y-1">
                        @foreach ($active as $assignment)
                            <li>{{ $assignment->officer->code }} — {{ $assignment->officer->name }}</li>
                        @endforeach
                    </ul>
                    <h3 class="mt-4 font-semibold">{{ __('Tugaskan petugas pengolahan') }}</h3>
                    <form method="POST" action="{{ route('document_processing_assignments.store', $document) }}" class="mt-2 flex flex-wrap gap-2 text-sm items-end">
                        @csrf
                        <div>
                            <label for="officer_id" class="block font-medium">{{ __('Petugas') }}</label>
                            <select id="officer_id" name="officer_id" required class="mt-1 border rounded px-2 py-1">
                                <option value="">{{ __('— Pilih —') }}</option>
                                @foreach ($officers as $officer)
                                    <option value="{{ $officer->id }}">{{ $officer->code }} — {{ $officer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="document_location_id" class="block font-medium">{{ __('Lokasi') }}</label>
                            <select id="document_location_id" name="document_location_id" class="mt-1 border rounded px-2 py-1">
                                <option value="">{{ __('— Tanpa lokasi —') }}</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="condition_code" class="block font-medium">{{ __('Kondisi') }}</label>
                            <select id="condition_code" name="condition_code" required class="mt-1 border rounded px-2 py-1">
                                @foreach ($conditions as $condition)
                                    <option value="{{ $condition }}">{{ $condition }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="note" class="block font-medium">{{ __('Catatan') }}</label>
                            <input id="note" name="note" type="text" class="mt-1 border rounded px-2 py-1" />
                        </div>
                        <button type="submit" class="underline">{{ __('Tugaskan') }}</button>
                    </form>
                    @if ($errors->any())
                        <ul class="mt-4 text-sm text-red-600">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <h3 class="mt-4 font-semibold">{{ __('Riwayat') }}</h3>
                    <table class="mt-2 w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Petugas') }}</th>
                                <th class="py-2">{{ __('Status') }}</th>
                                <th class="py-2">{{ __('Ditugaskan') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $assignment)
                                <tr class="border-b">
                                    <td class="py-2">{{ $assignment->officer->code }}</td>
                                    <td class="py-2">{{ $assignment->status }}</td>
                                    <td class="py-2">{{ $assignment->assigned_at }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $history->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
