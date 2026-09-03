<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detail sampel DSRT') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4 text-sm"><a href="{{ route('allocations.show', $allocation) }}" class="underline">{{ __('NKS: :nks', ['nks' => $allocation->nks]) }}</a></p>
                    <dl class="text-sm space-y-2">
                        <div><dt class="font-medium inline">{{ __('NUS/NURT:') }}</dt> <dd class="inline">{{ $sample->nus }} / {{ $sample->nurt }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('KK/Bangunan/RT:') }}</dt> <dd class="inline">{{ $sample->family_number ?? '—' }} / {{ $sample->building_number ?? '—' }} / {{ $sample->household_number ?? '—' }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Nama KRT:') }}</dt> <dd class="inline">{{ $sample->krt_name }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Pendidikan:') }}</dt> <dd class="inline">{{ $sample->krt_education_code ?? '—' }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Pencacahan:') }}</dt> <dd class="inline">{{ $sample->enumeration_status }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Status:') }}</dt> <dd class="inline">{{ $sample->record_status }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Alamat:') }}</dt> <dd class="inline">{{ $sample->maskedAddress() }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Contact:') }}</dt> <dd class="inline">{{ $sample->maskedContactPerson() }} / {{ $sample->maskedContactPhone() }}</dd></div>
                        @can('dsrt.manage')
                            <div><dt class="font-medium inline">{{ __('Kontak penuh (terbatas):') }}</dt> <dd class="inline">{{ $sample->contact_person ?? '—' }} / {{ $sample->contact_phone ?? '—' }} / {{ $sample->address ?? '—' }}</dd></div>
                        @endcan
                        <div><dt class="font-medium inline">{{ __('Catatan:') }}</dt> <dd class="inline">{{ $sample->notes ?? '—' }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Dibuat oleh:') }}</dt> <dd class="inline">{{ $sample->creator->name ?? '—' }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Verifikasi:') }}</dt> <dd class="inline">{{ $sample->verifier->name ?? '—' }} / {{ $sample->verified_at ?? '—' }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Arsip:') }}</dt> <dd class="inline">{{ $sample->archiver->name ?? '—' }} / {{ $sample->archived_at ?? '—' }}</dd></div>
                    </dl>
                    <div class="mt-4 flex gap-4 text-sm">
                        <a href="{{ route('allocations.dsrt.edit', [$allocation, $sample]) }}" class="underline">{{ __('Ubah') }}</a>
                        @if ($sample->record_status === 'DRAFT')
                            <form method="POST" action="{{ route('allocations.dsrt.verify', [$allocation, $sample]) }}">
                                @csrf
                                <button type="submit" class="underline">{{ __('Verifikasi') }}</button>
                            </form>
                            <form method="POST" action="{{ route('allocations.dsrt.archive', [$allocation, $sample]) }}">
                                @csrf
                                <button type="submit" class="underline">{{ __('Arsipkan') }}</button>
                            </form>
                        @endif
                        @if ($sample->record_status === 'VERIFIED')
                            <form method="POST" action="{{ route('allocations.dsrt.archive', [$allocation, $sample]) }}">
                                @csrf
                                <button type="submit" class="underline">{{ __('Arsipkan') }}</button>
                            </form>
                        @endif
                    </div>
                    @if ($errors->any())
                        <ul class="mt-4 text-sm text-red-600">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
