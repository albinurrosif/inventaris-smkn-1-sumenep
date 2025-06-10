<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Peminjaman Barang</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 9px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h4,
        .header p {
            margin: 0;
        }

        .table-main {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .table-main th,
        .table-main td {
            border: 1px solid #333;
            padding: 5px;
        }

        .table-main th {
            background-color: #f2f2f2;
        }

        .table-detail {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .table-detail th,
        .table-detail td {
            border: 1px solid #ccc;
            padding: 4px;
        }

        .table-detail th {
            background-color: #fafafa;
            font-size: 8px;
        }

        .text-center {
            text-align: center;
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

    @forelse ($peminjamanList as $peminjaman)
        <table class="table-main">
            <tr>
                <th style="width:20%;">ID Peminjaman</th>
                <td style="width:30%;"><strong>#{{ $peminjaman->id }}</strong></td>
                <th style="width:20%;">Status</th>
                <td style="width:30%;">{{ $peminjaman->status }}</td>
            </tr>
            <tr>
                <th>Peminjam</th>
                <td>{{ optional($peminjaman->guru)->username }}</td>
                <th>Tgl. Pengajuan</th>
                <td>{{ $peminjaman->tanggal_pengajuan->isoFormat('DD MMM YYYY') }}</td>
            </tr>
            <tr>
                <th>Tujuan</th>
                <td colspan="3">{{ $peminjaman->tujuan_peminjaman }}</td>
            </tr>
            <tr>
                <td colspan="4">
                    <strong>Detail Barang yang Dipinjam:</strong>
                    <table class="table-detail">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode Unit</th>
                                <th>Nama Barang</th>
                                <th>Kondisi Awal</th>
                                <th>Kondisi Akhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($peminjaman->detailPeminjaman as $idx => $detail)
                                <tr>
                                    <td class="text-center">{{ $idx + 1 }}</td>
                                    <td>{{ optional($detail->barangQrCode)->kode_inventaris_sekolah }}</td>
                                    <td>{{ optional(optional($detail->barangQrCode)->barang)->nama_barang }}</td>
                                    <td class="text-center">{{ $detail->kondisi_sebelum }}</td>
                                    <td class="text-center">{{ $detail->kondisi_setelah ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
    @empty
        <p style="text-align: center;">Tidak ada data peminjaman untuk dilaporkan.</p>
    @endforelse

</body>

</html>
