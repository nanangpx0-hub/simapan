<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Penugasan: :nks', ['nks' => $allocation->nks]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4 text-sm text-gray-600">{{ __('Status alokasi: :status. Penugasan hanya saat DRAFT/ACTIVE/SUSPENDED.', ['status' => $allocation->status]) }}</p>
                    @can('allocation.assign')
                    <form method="POST" action="{{ route('allocations.assignments.store', $allocation) }}">
                        @csrf
                        <div class="mb-4 flex flex-wrap gap-2 text-sm items-end">
                            <div>
                                <label for="assignment_role" class="block font-medium">{{ __('Role') }}</label>
                                <select id="assignment_role" name="assignment_role" required class="mt-1 border rounded px-2 py-1">
                                    <option value="">{{ __('— Pilih —') }}</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role }}" @selected(old('assignment_role') === $role)>{{ $role }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="officer_id" class="block font-medium">{{ __('Petugas aktif') }}</label>
                                <select id="officer_id" name="officer_id" required class="mt-1 border rounded px-2 py-1">
                                    <option value="">{{ __('— Pilih —') }}</option>
                                    @foreach ($officers as $officer)
                                        <option value="{{ $officer->id }}" @selected((string) old('officer_id') === (string) $officer->id)>{{ $officer->code }} — {{ $officer->name }} ({{ $officer->workUnit->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="employment_category" class="block font-medium">{{ __('Kategori') }}</label>
                                <select id="employment_category" name="employment_category" class="mt-1 border rounded px-2 py-1">
                                    <option value="">{{ __('— Belum tahu —') }}</option>
                                    <option value="ORGANIK" @selected(old('employment_category') === 'ORGANIK')>ORGANIK</option>
                                    <option value="MITRA" @selected(old('employment_category') === 'MITRA')>MITRA</option>
                                </select>
                            </div>
                            <button type="submit" class="underline">{{ __('Tugaskan') }}</button>
                        </div>
                        @if ($errors->any())
                            <ul class="mb-4 text-sm text-red-600">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </form>
                    @endcan
                    <h3 class="mt-4 font-semibold">{{ __('Aktif') }}</h3>
                    <ul class="mt-2 text-sm space-y-1">
                        @foreach ($active as $assignment)
                            <li>
                                {{ $assignment->assignment_role }}: {{ $assignment->officer->code }}
                                ({{ $assignment->employment_category ?? '—' }})
                                @can('allocation.assign')
                                    <form method="POST" action="{{ route('allocations.assignments.unassign', [$allocation, $assignment]) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="underline ms-2">{{ __('Nonaktifkan') }}</button>
                                    </form>
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                    <h3 class="mt-4 font-semibold">{{ __('Riwayat') }}</h3>
                    <table class="mt-2 w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Role') }}</th>
                                <th class="py-2">{{ __('Petugas') }}</th>
                                <th class="py-2">{{ __('Kategori') }}</th>
                                <th class="py-2">{{ __('Aktif') }}</th>
                                <th class="py-2">{{ __('Mulai') }}</th>
                                <th class="py-2">{{ __('Selesai') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($history as $assignment)
                                <tr class="border-b">
                                    <td class="py-2">{{ $assignment->assignment_role }}</td>
                                    <td class="py-2">{{ $assignment->officer->code }}</td>
                                    <td class="py-2">{{ $assignment->employment_category ?? '—' }}</td>
                                    <td class="py-2">{{ $assignment->is_active ? __('Ya') : __('Tidak') }}</td>
                                    <td class="py-2">{{ $assignment->started_at }}</td>
                                    <td class="py-2">{{ $assignment->ended_at ?? '—' }}</td>
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
