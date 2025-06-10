<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Pemeliharaan Aset</title>
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
    </style>
</head>

<body>
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

    <table class="table">
        <thead>
            <tr>
                <th class="text-center">ID</th>
                <th>Kode Unit</th>
                <th>Nama Barang</th>
                <th>Kerusakan Dilaporkan</th>
                <th>Tgl. Lapor</th>
                <th>Pelapor</th>
                <th>PIC</th>
                <th>Status</th>
                <th class="text-end">Biaya (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pemeliharaanList as $item)
                <tr>
                    <td class="text-center">#{{ $item->id }}</td>
                    <td>{{ optional($item->barangQrCode)->kode_inventaris_sekolah ?? 'N/A' }}</td>
                    <td>{{ optional(optional($item->barangQrCode)->barang)->nama_barang ?? 'N/A' }}</td>
                    <td>{{ $item->catatan_pengajuan }}</td>
                    <td>{{ $item->tanggal_pengajuan->isoFormat('DD-MM-YY') }}</td>
                    <td>{{ optional($item->pengaju)->username ?? 'N/A' }}</td>
                    <td>{{ optional($item->operatorPengerjaan)->username ?? '-' }}</td>
                    <td>{{ $item->status_pemeliharaan }}</td>
                    <td class="text-end">{{ number_format($item->biaya, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data untuk ditampilkan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
