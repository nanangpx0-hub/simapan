<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detail petugas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <dl class="text-sm space-y-2">
                        <div><dt class="font-medium inline">{{ __('Kode:') }}</dt> <dd class="inline">{{ $officer->code }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Nama:') }}</dt> <dd class="inline">{{ $officer->name }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Unit kerja:') }}</dt> <dd class="inline">{{ $officer->workUnit->code ?? '—' }} — {{ $officer->workUnit->name ?? '—' }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Status:') }}</dt> <dd class="inline">{{ $officer->status === 'ACTIVE' ? __('Aktif') : __('Nonaktif') }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Periode aktif:') }}</dt> <dd class="inline">{{ $officer->active_from?->format('Y-m-d') ?? '—' }} — {{ $officer->active_until?->format('Y-m-d') ?? '—' }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Akun user:') }}</dt> <dd class="inline">{{ $officer->user->name ?? '—' }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Telepon:') }}</dt> <dd class="inline">{{ $officer->maskedPhone() }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Email:') }}</dt> <dd class="inline">{{ $officer->maskedEmail() }}</dd></div>
                        @can('master.officer.manage')
                            <div><dt class="font-medium inline">{{ __('Kontak penuh (terbatas):') }}</dt> <dd class="inline">{{ $officer->phone ?? '—' }} / {{ $officer->email ?? '—' }}</dd></div>
                        @endcan
                    </dl>
                    <h3 class="mt-6 font-semibold">{{ __('Alias (:count)', ['count' => $officer->aliases->count()]) }}</h3>
                    <ul class="mt-2 text-sm space-y-1">
                        @foreach ($officer->aliases as $alias)
                            <li>{{ $alias->alias_name }}</li>
                        @endforeach
                    </ul>
                    <div class="mt-4 flex gap-4 text-sm">
                        <a href="{{ route('master.officers.edit', $officer) }}" class="underline">{{ __('Ubah') }}</a>
                        <a href="{{ route('master.officers.aliases.index', $officer) }}" class="underline">{{ __('Kelola alias') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
