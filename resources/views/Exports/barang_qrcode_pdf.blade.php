@extends('layouts.base-pdf') {{-- Pastikan layout PDF Anda sudah ada --}}

@section('title', 'DAFTAR BARANG PER UNIT - SMKN 1 SUMENEP')

@section('styles')
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 7pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 1px solid #777;
            padding: 3px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }

        th {
            background-color: #e9e9e9;
            font-weight: bold;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .header-container {
            text-align: center;
            margin-bottom: 15px;
        }

        .header-container h3 {
            margin-bottom: 2px;
            font-size: 14pt;
        }

        .header-container p.school-name {
            font-size: 10pt;
            margin-bottom: 10px;
        }

        .filter-info {
            font-size: 7pt;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        .group-title {
            font-size: 9pt;
            font-weight: bold;
            margin-top: 10px;
            /* Memberi sedikit jarak jika ada page break */
            background-color: #f0f0f0;
            padding: 6px;
            border: 1px solid #bbb;
            page-break-after: avoid;
            /* Hindari pindah halaman tepat setelah judul grup */
        }

        .signature-table {
            margin-top: 30px;
            border: none;
            width: 100%;
            font-size: 8pt;
            page-break-inside: avoid;
        }

        .signature-table td {
            border: none;
            text-align: center;
        }

        .signature-space {
            height: 50px;
        }

        img.qr-code-in-table {
            width: 35px;
            height: 35px;
            display: block;
            margin: auto;
        }
    </style>
@endsection

@section('content')
    <div class="header-container">
        <h3>DAFTAR INVENTARIS BARANG PER UNIT</h3>
        <p class="school-name">SMKN 1 SUMENEP</p>
    </div>

    <div class="filter-info">
        <strong>Filter Aktif:</strong>
        @if (isset($filterInfo) && is_array($filterInfo))
            @php $activeFilters = []; @endphp
            @if (!empty($filterInfo['ruangan']) && $filterInfo['ruangan'] !== 'Semua Ruangan')
                @php $activeFilters[] = "Ruangan: " . $filterInfo['ruangan']; @endphp
            @endif
            @if (!empty($filterInfo['kategori']) && $filterInfo['kategori'] !== 'Semua Kategori')
                @php $activeFilters[] = "Kategori: " . $filterInfo['kategori']; @endphp
            @endif
            @if (!empty($filterInfo['status']) && $filterInfo['status'] !== 'Semua Status')
                @php $activeFilters[] = "Status Unit: " . $filterInfo['status']; @endphp
            @endif
            @if (!empty($filterInfo['kondisi']) && $filterInfo['kondisi'] !== 'Semua Kondisi')
                @php $activeFilters[] = "Kondisi Unit: " . $filterInfo['kondisi']; @endphp
            @endif
            @if (!empty($filterInfo['tahun_pembuatan']) && $filterInfo['tahun_pembuatan'] !== 'Semua Tahun')
                @php $activeFilters[] = "Thn. Pembuatan Model: " . $filterInfo['tahun_pembuatan']; @endphp
            @endif
            @if (!empty($filterInfo['search']) && $filterInfo['search'] !== '-')
                @php $activeFilters[] = "Pencarian: '" . Str::limit($filterInfo['search'], 30) . "'"; @endphp
            @endif

            @if (count($activeFilters) > 0)
                {{ implode(' | ', $activeFilters) }}
            @else
                Tidak ada filter spesifik yang diterapkan. Menampilkan semua unit.
            @endif
        @else
            Menampilkan semua unit.
        @endif
    </div>

    @php
        $grouped = $qrCodes->groupBy(function ($qrCodeItem) use ($groupByRuangan) {
            if (!$groupByRuangan) {
                return 'Semua Unit Barang';
            }
            if ($qrCodeItem->id_pemegang_personal && $qrCodeItem->pemegangPersonal) {
                return 'Dipegang Personal: ' . $qrCodeItem->pemegangPersonal->username;
            }
            return $qrCodeItem->ruangan ? $qrCodeItem->ruangan->nama_ruangan : 'Belum Ditempatkan / Lainnya';
        });
    @endphp

    @if ($qrCodes->isEmpty())
        <p class="text-center mt-4">Tidak ada data unit barang yang sesuai dengan filter yang diterapkan.</p>
    @else
        @foreach ($grouped as $groupName => $items)
            <div class="group-title" @if ($groupByRuangan && !$loop->first) style="page-break-before: always;" @endif>
                {{ $groupName }} (Total Unit: {{ $items->count() }})
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width:3%">No</th>
                        <th style="width:10%">Kode Inv. Sekolah</th>
                        <th style="width:10%">No. Seri Pabrik</th>
                        <th>Nama Barang (Jenis)</th>
                        <th>Kategori</th>
                        <th>Merk/Model</th>
                        <th style="width:5%">Thn. Model</th>
                        <th style="width:7%">Tgl. Perolehan</th>
                        <th style="width:8%">Harga Unit (Rp)</th>
                        <th style="width:7%">Sumber Dana</th>
                        <th style="width:7%">No. Dok.</th>
                        <th style="width:9%">Lokasi/Pemegang</th>
                        <th style="width:7%">Kondisi</th>
                        <th style="width:7%">Status</th>
                        <th style="width:5%">QR</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $index => $qr)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ $qr->kode_inventaris_sekolah ?? '-' }}</td>
                            <td>{{ $qr->no_seri_pabrik ?? '-' }}</td>
                            <td>{{ $qr->barang->nama_barang ?? '-' }}</td>
                            <td>{{ $qr->barang->kategori->nama_kategori ?? '-' }}</td>
                            <td>{{ $qr->barang->merk_model ?? '-' }}</td>
                            <td class="text-center">{{ $qr->barang->tahun_pembuatan ?? '-' }}</td>
                            <td class="text-center">
                                {{ $qr->tanggal_perolehan_unit ? \Carbon\Carbon::parse($qr->tanggal_perolehan_unit)->isoFormat('DD/MM/YY') : '-' }}
                            </td>
                            <td class="text-right">
                                {{ $qr->harga_perolehan_unit ? number_format($qr->harga_perolehan_unit, 0, ',', '.') : '-' }}
                            </td>
                            <td>{{ $qr->sumber_dana_unit ?? '-' }}</td>
                            <td>{{ $qr->no_dokumen_perolehan_unit ?? '-' }}</td>
                            <td>
                                @if ($qr->id_pemegang_personal && $qr->pemegangPersonal)
                                    {{ Str::limit('P: ' . $qr->pemegangPersonal->username, 15) }}
                                @elseif($qr->ruangan)
                                    {{ Str::limit($qr->ruangan->nama_ruangan, 15) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $qr->kondisi ?? '-' }}</td>
                            <td>{{ $qr->status ?? '-' }}</td>
                            <td class="text-center">
                                @if ($qr->qr_path && file_exists(storage_path('app/public/' . $qr->qr_path)))
                                    <img src="{{ storage_path('app/public/' . $qr->qr_path) }}" alt="QR"
                                        class="qr-code-in-table" width= "35px" height= "35px" display= "block"
                                        margin= "auto">
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="text-center">Tidak ada unit barang dalam kelompok ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endforeach
    @endif

    <table class="signature-table">
        <tr>
            <td style="width: 60%;"></td>
            <td style="width: 40%;">
                Sumenep, {{ \Carbon\Carbon::now()->isoFormat('DD MMMM YYYY') }}<br> {{-- Format tanggal diperbaiki --}}
                Mengetahui,
            </td>
        </tr>
        <tr>
            <td class="signature-space"></td>
            <td class="signature-space"></td>
        </tr>
        <tr>
            <td>
                Petugas Inventaris
                <br><br><br><br>
                (______________________)
            </td>
            <td>
                Kepala Sekolah
                <br><br><br><br>
                (______________________)
            </td>
        </tr>
    </table>
@endsection
