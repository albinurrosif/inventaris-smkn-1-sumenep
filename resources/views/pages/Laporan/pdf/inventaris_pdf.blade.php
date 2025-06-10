<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Inventaris Barang</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 10px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h4,
        .header p {
            margin: 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }

        .table th {
            background-color: #f2f2f2;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .text-muted {
            color: #6c757d;
        }

        .badge {
            display: inline-block;
            padding: .25em .4em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: .25rem;
        }

        .bg-success {
            color: #fff;
            background-color: #198754;
        }

        .bg-warning {
            color: #000;
            background-color: #ffc107;
        }

        .bg-danger {
            color: #fff;
            background-color: #dc3545;
        }

        .bg-info {
            color: #000;
            background-color: #0dcaf0;
        }

        .bg-primary {
            color: #fff;
            background-color: #0d6efd;
        }

        .bg-secondary {
            color: #fff;
            background-color: #6c757d;
        }
    </style>
</head>

<body>
    {{-- KODE BARU --}}
    <div class="header">
        @if (get_setting('logo_sekolah') && file_exists(public_path('storage/' . get_setting('logo_sekolah'))))
            <img src="{{ public_path('storage/' . get_setting('logo_sekolah')) }}" alt="Logo"
                style="width: 70px; height: auto; position: absolute; left: 0; top: 0;">
        @endif
        <h4 style="margin:0;">LAPORAN INVENTARIS BARANG</h4>
        <h5 style="margin:0;">{{ get_setting('nama_sekolah') }}</h5>
        <p style="margin:0; font-size: 8px;">{{ get_setting('alamat_sekolah') }}</p>
        <p class="text-muted">Dicetak pada: {{ now()->isoFormat('dddd, DD MMMM YYYY, HH:mm:ss') }}</p>
    </div>
    <hr>

    <table class="table">
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>Kode</th>
                <th>Nama Barang</th>
                <th>Lokasi/Pemegang</th>
                <th>Tgl. Perolehan</th>
                <th class="text-end">Harga (Rp)</th>
                <th class="text-center">Kondisi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($inventaris as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->kode_inventaris_sekolah }}</td>
                    <td>
                        {{ optional($item->barang)->nama_barang }}
                        @if ($item->no_seri_pabrik)
                            <br><small class="text-muted">SN: {{ $item->no_seri_pabrik }}</small>
                        @endif
                    </td>
                    <td>
                        @if ($item->ruangan)
                            {{ $item->ruangan->nama_ruangan }}
                        @elseif($item->pemegangPersonal)
                            {{ $item->pemegangPersonal->username }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ \Carbon\Carbon::parse($item->tanggal_perolehan_unit)->isoFormat('DD-MM-YY') }}</td>
                    <td class="text-end">{{ number_format($item->harga_perolehan_unit, 0, ',', '.') }}</td>
                    <td class="text-center">
                        <span
                            class="badge {{ \App\Models\BarangQrCode::getKondisiColor($item->kondisi) }}">{{ $item->kondisi }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data untuk ditampilkan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>
