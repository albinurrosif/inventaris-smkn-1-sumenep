<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Code Unit Barang</title>
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    {{-- Jika Anda menggunakan icons.min.css untuk ikon di tombol print, sertakan juga --}}
    {{-- <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" /> --}}
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
            /* Latar belakang putih untuk pratinjau */
        }

        .print-container {
            padding: 15px;
            max-width: 100%;
            /* Agar sesuai lebar kertas */
            margin: 0 auto;
            /* Tengahkan jika ada batas lebar */
        }

        .header-print {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header-print h4 {
            margin: 0;
            font-size: 1.5em;
        }

        .header-print p {
            margin: 5px 0 0;
            font-size: 0.9em;
        }

        .qr-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            /* Jarak antar label QR */
            justify-content: flex-start;
            /* Mulai dari kiri */
        }

        .qr-item {
            border: 1px solid #ccc;
            padding: 8px;
            width: calc(33.333% - 10px);
            /* 3 item per baris dengan gap */
            box-sizing: border-box;
            text-align: center;
            overflow: hidden;
            /* Mencegah konten meluber */
            page-break-inside: avoid;
            /* Hindari label terpotong antar halaman */
            background-color: #fff;
            /* Latar belakang label putih */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            /* Untuk menata konten di dalam item */
            min-height: 160px;
            /* Tinggi minimum untuk konsistensi */
        }

        .qr-item img.qr-image {
            max-width: 100px;
            /* Ukuran QR code */
            height: auto;
            margin: 5px auto;
            display: block;
        }

        .qr-item .info {
            font-size: 0.75em;
            /* Ukuran font lebih kecil untuk info */
            line-height: 1.3;
            margin-top: 5px;
        }

        .qr-item .info p {
            margin: 2px 0;
            word-wrap: break-word;
            /* Agar teks panjang tidak meluber */
        }

        .qr-item .kode-inventaris {
            font-weight: bold;
            font-size: 0.85em;
            /* Sedikit lebih besar dari info lain */
        }

        .print-button-container {
            text-align: center;
            padding: 20px;
        }

        /* Print-specific styles */
        @media print {
            body {
                margin: 0;
                /* Hapus margin default browser saat print */
                padding: 0;
                background-color: #fff;
                /* Pastikan background putih saat print */
                -webkit-print-color-adjust: exact !important;
                /* Chrome, Safari */
                color-adjust: exact !important;
                /* Firefox, Edge */
            }

            .print-button-container {
                display: none !important;
                /* Sembunyikan tombol print saat mencetak */
            }

            .print-container {
                padding: 5mm;
                /* Sedikit padding untuk margin printer */
                max-width: none;
                /* Gunakan lebar penuh kertas */
                margin: 0;
            }

            .qr-item {
                border: 1px solid #888;
                /* Garis tipis untuk pemotongan */
                /* Jika ingin ukuran tetap saat print (misal untuk label stiker) */
                /* width: 60mm !important;
                height: 40mm !important;
                padding: 5mm !important;
                font-size: 8pt !important; */
            }

            .qr-item img.qr-image {
                max-width: 25mm;
                /* Sesuaikan ukuran QR saat print jika perlu */
                max-height: 25mm;
            }

            .header-print {
                display: none;
                /* Sembunyikan header jika tidak ingin dicetak di setiap halaman QR */
            }
        }
    </style>
</head>

<body>
    <div class="print-container">
        <div class="header-print d-none d-print-block"> {{-- Hanya tampil saat print jika diinginkan, atau hapus d-none --}}
            <h4>QR Code Unit Barang</h4>
            <p>SMKN 1 Sumenep - {{ \Carbon\Carbon::now()->isoFormat('D MMMM YYYY, HH:mm') }}</p>
        </div>

        <div class="print-button-container">
            <button type="button" class="btn btn-primary" onclick="window.print();">
                <i class="fas fa-print me-1"></i> Cetak Halaman Ini
            </button>
            <button type="button" class="btn btn-secondary" onclick="window.close();">
                <i class="fas fa-times me-1"></i> Tutup
            </button>
        </div>

        @if (isset($qrCodes) && $qrCodes->count() > 0)
            <div class="qr-grid">
                @foreach ($qrCodes as $unit)
                    <div class="qr-item">
                        <div>
                            @if ($unit->qr_path && Storage::disk('public')->exists($unit->qr_path))
                                {{-- Jika SVG, bisa disisipkan langsung atau via img --}}
                                <?php
                                // $qrContent = Storage::disk('public')->get($unit->qr_path);
                                // echo $qrContent; // Ini akan menyisipkan SVG langsung, perlu diatur ukurannya via CSS SVG
                                ?>
                                <img src="{{ Storage::url($unit->qr_path) }}"
                                    alt="QR Code {{ $unit->kode_inventaris_sekolah }}" class="qr-image">
                            @else
                                <div class="qr-image"
                                    style="width:100px; height:100px; background-color:#eee; margin:5px auto; display:flex; align-items:center; justify-content:center; font-size:0.7em;">
                                    QR Error</div>
                            @endif
                        </div>
                        <div class="info">
                            <p class="kode-inventaris">{{ $unit->kode_inventaris_sekolah ?? 'N/A' }}</p>
                            <p>{{ Str::limit($unit->barang->nama_barang ?? 'Nama Barang Tidak Ada', 30) }}</p>
                            @if ($unit->no_seri_pabrik)
                                <p>SN: {{ Str::limit($unit->no_seri_pabrik, 25) }}</p>
                            @endif
                            {{-- Tambahkan info lokasi jika perlu dan muat di label --}}
                            {{--
                            @if ($unit->id_pemegang_personal && $unit->pemegangPersonal)
                                <p>Pemegang: {{ Str::limit($unit->pemegangPersonal->username, 20) }}</p>
                            @elseif($unit->id_ruangan && $unit->ruangan)
                                <p>Lokasi: {{ Str::limit($unit->ruangan->nama_ruangan, 20) }}</p>
                            @endif
                            --}}
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-warning text-center">
                Tidak ada unit barang yang dipilih atau ditemukan untuk dicetak QR Code-nya.
            </div>
        @endif
    </div>

    {{-- JAVASCRIPT (jika ada yang spesifik untuk halaman ini, misal untuk tombol) --}}
    {{-- <script src="{{ asset('assets/libs/jquery/jquery.min.js') }}"></script> --}}
    {{-- <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script> --}}
</body>

</html>
