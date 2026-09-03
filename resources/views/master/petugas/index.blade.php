<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Master Petugas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <p>{{ __('Daftar petugas operasional.') }}</p>
                        <a href="{{ route('master.officers.create') }}" class="underline">{{ __('Tambah petugas') }}</a>
                    </div>
                    <form method="GET" action="{{ route('master.officers.index') }}" class="mb-4 flex flex-wrap gap-2 text-sm">
                        <select name="work_unit_id" class="border rounded px-2 py-1">
                            <option value="">{{ __('Semua unit') }}</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected((string) ($filters['work_unit_id'] ?? '') === (string) $unit->id)>{{ $unit->code }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="border rounded px-2 py-1">
                            <option value="">{{ __('Semua status') }}</option>
                            <option value="ACTIVE" @selected(($filters['status'] ?? '') === 'ACTIVE')>{{ __('Aktif') }}</option>
                            <option value="INACTIVE" @selected(($filters['status'] ?? '') === 'INACTIVE')>{{ __('Nonaktif') }}</option>
                        </select>
                        <input name="q" type="text" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Cari kode/nama/alias') }}" class="border rounded px-2 py-1" />
                        <button type="submit" class="underline">{{ __('Filter') }}</button>
                    </form>
                    <table class="w-full text-sm text-left">
                        <thead>
                            <tr class="border-b">
                                <th class="py-2">{{ __('Kode') }}</th>
                                <th class="py-2">{{ __('Nama') }}</th>
                                <th class="py-2">{{ __('Unit') }}</th>
                                <th class="py-2">{{ __('Status') }}</th>
                                <th class="py-2">{{ __('Akun') }}</th>
                                <th class="py-2">{{ __('Alias') }}</th>
                                <th class="py-2">{{ __('Dibuat') }}</th>
                                <th class="py-2">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($officers as $officer)
                                <tr class="border-b">
                                    <td class="py-2">{{ $officer->code }}</td>
                                    <td class="py-2">{{ $officer->name }}</td>
                                    <td class="py-2">{{ $officer->workUnit->code ?? '—' }}</td>
                                    <td class="py-2">{{ $officer->status === 'ACTIVE' ? __('Aktif') : __('Nonaktif') }}</td>
                                    <td class="py-2">
                                        @if ($officer->user)
                                            {{ $officer->user->name }}
                                            @can('master.officer.manage')
                                                ({{ $officer->user->email }})
                                            @endcan
                                        @else
                                            {{ __('—') }}
                                        @endif
                                    </td>
                                    <td class="py-2">{{ $officer->aliases_count }}</td>
                                    <td class="py-2">{{ $officer->created_at }}</td>
                                    <td class="py-2">
                                        <a href="{{ route('master.officers.show', $officer) }}" class="underline">{{ __('Lihat') }}</a>
                                        <a href="{{ route('master.officers.edit', $officer) }}" class="underline ms-2">{{ __('Ubah') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $officers->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
