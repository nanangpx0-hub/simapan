<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Detail periode survei') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <dl class="text-sm space-y-2">
                        <div><dt class="font-medium inline">{{ __('Kode:') }}</dt> <dd class="inline">{{ $period->code }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Jenis:') }}</dt> <dd class="inline">{{ $period->surveyType->code }} — {{ $period->surveyType->name }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Nama:') }}</dt> <dd class="inline">{{ $period->name }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Tipe/Nomor/Tahun:') }}</dt> <dd class="inline">{{ $period->period_type }}{{ $period->period_number !== null ? ' '.$period->period_number : '' }} / {{ $period->year }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Tanggal:') }}</dt> <dd class="inline">{{ $period->start_date->format('Y-m-d') }} — {{ $period->end_date->format('Y-m-d') }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Status:') }}</dt> <dd class="inline">{{ $period->status }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Dibuat oleh:') }}</dt> <dd class="inline">{{ $period->creator->name ?? '—' }}</dd></div>
                        <div><dt class="font-medium inline">{{ __('Ditutup oleh/pada:') }}</dt> <dd class="inline">{{ $period->closer->name ?? '—' }} / {{ $period->closed_at ?? '—' }}</dd></div>
                    </dl>
                    <div class="mt-4 flex gap-4 text-sm">
                        <a href="{{ route('master.survey_periods.edit', $period) }}" class="underline">{{ __('Ubah') }}</a>
                        @if ($period->status === 'DRAFT')
                            <form method="POST" action="{{ route('master.survey_periods.activate', $period) }}">
                                @csrf
                                <button type="submit" class="underline">{{ __('Aktifkan') }}</button>
                            </form>
                            <form method="POST" action="{{ route('master.survey_periods.archive', $period) }}">
                                @csrf
                                <button type="submit" class="underline">{{ __('Arsipkan') }}</button>
                            </form>
                        @endif
                        @if ($period->status === 'ACTIVE')
                            <form method="POST" action="{{ route('master.survey_periods.close', $period) }}">
                                @csrf
                                <button type="submit" class="underline">{{ __('Tutup') }}</button>
                            </form>
                        @endif
                        @if ($period->status === 'CLOSED')
                            <form method="POST" action="{{ route('master.survey_periods.archive', $period) }}">
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
