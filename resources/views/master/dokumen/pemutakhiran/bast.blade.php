<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ __('BAST Pemutakhiran :nomor', ['nomor' => $manifest->manifest_number]) }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 0; }
        p.center { text-align: center; }
        table.grid { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.grid th, table.grid td { border: 1px solid #111; padding: 4px 6px; }
        table.sign { width: 100%; margin-top: 32px; }
        table.sign td { width: 50%; text-align: center; vertical-align: top; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <h1>{{ __('BERITA ACARA SERAH TERIMA') }}</h1>
    <p class="center">{{ __('Dokumen Pemutakhiran Susenas (VSEN.P)') }}<br>{{ $manifest->manifest_number }}</p>

    <p>{{ __('Pada hari ini, Tim Statistik Sosial menyerahkan dokumen pemutakhiran kepada Tim Pengolahan dan Layanan Statistik dengan rincian sebagai berikut:') }}</p>

    <table class="grid">
        <thead>
            <tr>
                <th>{{ __('No') }}</th>
                <th>{{ __('Kecamatan') }}</th>
                <th>{{ __('Desa') }}</th>
                <th>{{ __('NKS') }}</th>
                <th>{{ __('Jumlah Rumah Tangga') }}</th>
                <th>{{ __('PCL') }}</th>
                <th>{{ __('PML') }}</th>
                <th>{{ __('Pengolah') }}</th>
                <th>{{ __('VSEN26.P') }}</th>
                <th>{{ __('Peta WS') }}</th>
                <th>{{ __('Status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($manifest->updatingItems as $i => $item)
                @php
                    $pcl = $item->allocation?->activeAssignments->firstWhere('assignment_role', 'FIELD_OFFICER')?->officer?->name ?? '—';
                    $pml = $item->allocation?->activeAssignments->firstWhere('assignment_role', 'FIELD_SUPERVISOR')?->officer?->name ?? '—';
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->kecamatan_code }}</td>
                    <td>{{ $item->desa_code }}</td>
                    <td>{{ $item->nks }}</td>
                    <td>{{ $item->household_count_listing }}</td>
                    <td>{{ $pcl }}</td>
                    <td>{{ $pml }}</td>
                    <td>{{ $item->processor?->name ?? '—' }}</td>
                    <td>{{ $item->has_vsen_p ? '✓' : '—' }}</td>
                    <td>{{ $item->has_peta_ws ? '✓' : '—' }}</td>
                    <td>{{ $item->delivery_status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="sign">
        <tr>
            <td>
                {{ __('Pihak Penyerah,') }}<br>{{ __('Tim Statistik Sosial') }}<br><br><br><br><br>
                (________________________)<br>{{ __('Nama / Tanggal') }}
            </td>
            <td>
                {{ __('Pihak Penerima,') }}<br>{{ __('Tim PLS / IPDS') }}<br><br><br><br><br>
                (________________________)<br>{{ __('Nama / Tanggal') }}
            </td>
        </tr>
    </table>

    <p class="no-print"><button type="button" onclick="window.print()">{{ __('Cetak') }}</button></p>
</body>
</html>
