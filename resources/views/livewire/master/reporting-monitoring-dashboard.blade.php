<div class="space-y-4">
    {{-- Filter --}}
    <div class="flex flex-wrap items-end gap-3 text-sm">
        <div class="flex flex-col">
            <label for="period-select" class="mb-1 font-medium text-gray-700">{{ __('Periode Survei') }}</label>
            <select id="period-select" wire:model="periodId" class="border rounded px-2 py-1">
                @foreach ($periods as $opt)
                    <option value="{{ $opt->id }}">{{ $opt->name }} ({{ $opt->year }})</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="sla-filter" class="mb-1 font-medium text-gray-700">{{ __('Status SLA') }}</label>
            <select id="sla-filter" wire:model="slaFilter" class="border rounded px-2 py-1">
                @foreach (\App\Livewire\Master\ReportingMonitoringDashboard::SLA_FILTERS as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col">
            <label for="recon-filter" class="mb-1 font-medium text-gray-700">{{ __('Status Rekonsiliasi') }}</label>
            <select id="recon-filter" wire:model="reconFilter" class="border rounded px-2 py-1">
                @foreach (\App\Livewire\Master\ReportingMonitoringDashboard::RECON_FILTERS as $opt)
                    <option value="{{ $opt }}">{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex pb-0.5">
            <button type="button" class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 text-white rounded" wire:loading.attr="disabled" wire:click="export">
                <span wire:loading.remove wire:target="export">{{ __('Export Laporan Rekonsiliasi 5 Dokumen') }}</span>
                <span wire:loading wire:target="export">{{ __('Menyiapkan…') }}</span>
            </button>
        </div>
    </div>

    {{-- Metric Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-sm text-gray-500">{{ __('Serah Pemutakhiran Susenas') }}</p>
            <p class="mt-1 text-2xl font-semibold">{{ $metrics['lap1_count'] }} / {{ $metrics['target_nks'] }}</p>
            <p class="text-xs text-gray-400">{{ __('NKS diserahkan / target') }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-sm text-gray-500">{{ __('Serah Sampel Susenas') }}</p>
            <p class="mt-1 text-2xl font-semibold">{{ $metrics['lap1_count'] }} / {{ $metrics['target_nks'] }}</p>
            <p class="text-xs text-gray-400">{{ __('kuesioner diserahkan / target') }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-sm text-gray-500">{{ __('Entri Pemutakhiran Selesai') }}</p>
            <p class="mt-1 text-2xl font-semibold">{{ $metrics['entri_pemutakhiran'] }}%</p>
            <p class="text-xs text-gray-400">{{ __('progres, NKS tersisa') }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-sm text-gray-500">{{ __('Entri Sampel Susenas Selesai') }}</p>
            <p class="mt-1 text-2xl font-semibold">{{ $metrics['entri_sampel'] }}%</p>
            <p class="text-xs text-gray-400">{{ __('kuesioner Kor & KP') }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-sm text-gray-500">{{ __('Entri Sampel Seruti Selesai') }}</p>
            <p class="mt-1 text-2xl font-semibold">{{ $metrics['entri_seruti'] }}%</p>
            <p class="text-xs text-gray-400">{{ __('kuesioner Seruti + Linkage') }}</p>
        </div>
    </div>
{{-- Matrix Reconciliation Table --}}
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-sm font-medium text-gray-700 mb-3">{{ __('Matriks Rekonsiliasi per NKS') }}</p>
        <div class="overflow-x-auto rounded border border-gray-200">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50">
                    <tr class="border-b">
                        @foreach (['NKS', 'Lap 1', 'Lap 3', 'Lap 2', 'Lap 4', 'Lap 5', 'Rekonsiliasi', 'SLA'] as $label)
                            <th class="py-2 px-3">{{ __($label) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-2 px-3 font-mono">{{ $row['nks'] }}</td>
                            <td class="py-2 px-3">{{ $row['lap1'] }}</td>
                            <td class="py-2 px-3">{{ $row['lap3'] }}</td>
                            <td class="py-2 px-3">{{ $row['lap2'] }}</td>
                            <td class="py-2 px-3">{{ $row['lap4'] }}</td>
                            <td class="py-2 px-3">{{ $row['lap5'] }}</td>
                            <td class="py-2 px-3">
                                <span class="px-2 py-0.5 rounded text-xs {{ $row['recon'] === 'DISCREPANCY' ? 'bg-red-100 text-red-700' : ($row['recon'] === 'MATCH' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600') }}">
                                    {{ $row['recon'] }}
                                </span>
                            </td>
                            <td class="py-2 px-3">
                                <span class="px-2 py-0.5 rounded text-xs {{ $row['sla'] === 'OVERDUE' ? 'bg-red-100 text-red-700' : ($row['sla'] === 'WARNING' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                    {{ $row['sla'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-4 px-3 text-center text-gray-500">{{ __('Tidak ada data.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>