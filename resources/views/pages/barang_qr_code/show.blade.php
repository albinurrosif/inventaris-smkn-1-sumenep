@extends('layouts.app')

@section('title', 'Detail Unit Barang: ' . ($qrCode->kode_inventaris_sekolah ?? 'N/A'))

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Detail Unit Barang (KIB)</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            @if ($qrCode->barang)
                                <li class="breadcrumb-item">
                                    @can('view', $qrCode->barang)
                                        <a
                                            href="{{ route($rolePrefix . 'barang.show', $qrCode->barang->id) }}">{{ Str::limit($qrCode->barang->nama_barang, 20) }}</a>
                                    @else
                                        {{ Str::limit($qrCode->barang->nama_barang, 20) }}
                                    @endcan
                                </li>
                            @endif
                            <li class="breadcrumb-item active">{{ $qrCode->kode_inventaris_sekolah ?? 'N/A' }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Kolom Kiri (Detail Aset & Riwayat) --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-fingerprint me-2"></i>1. Identifikasi Aset</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <p class="mb-2"><strong>Kode Inventaris Sekolah:</strong> <br><span
                                        class="text-primary fw-bold fs-5">{{ $qrCode->kode_inventaris_sekolah ?? 'N/A' }}</span>
                                </p>
                                <p class="mb-2"><strong>Nomor Seri Pabrik:</strong>
                                    <br>{{ $qrCode->no_seri_pabrik ?? '-' }}
                                </p>
                                <p class="mb-2"><strong>Nama Barang (Jenis):</strong>
                                    <br>{{ $qrCode->barang?->nama_barang ?? '-' }}
                                </p>
                                <p class="mb-0"><strong>Kategori Barang:</strong>
                                    <br>{{ $qrCode->barang?->kategori?->nama_kategori ?? '-' }}
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Merk/Model:</strong> <br>{{ $qrCode->barang?->merk_model ?? '-' }}
                                </p>
                                <p class="mb-2"><strong>Spesifikasi Umum:</strong>
                                    <br>{{ collect([$qrCode->barang?->ukuran, $qrCode->barang?->bahan])->filter()->implode(', ') ?:'-' }}
                                </p>
                                <p class="mb-0"><strong>Tahun Pembuatan (Model):</strong>
                                    <br>{{ $qrCode->barang?->tahun_pembuatan ?? '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-file-invoice-dollar me-2"></i>2. Data Perolehan Unit
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <p class="mb-2"><strong>Tanggal Perolehan:</strong>
                                    <br>{{ $qrCode->tanggal_perolehan_unit ? \Carbon\Carbon::parse($qrCode->tanggal_perolehan_unit)->isoFormat('DD MMMM YYYY') : '-' }}
                                </p>
                                <p class="mb-0"><strong>Harga Perolehan Unit:</strong> <br>Rp
                                    {{ $qrCode->harga_perolehan_unit ? number_format($qrCode->harga_perolehan_unit, 2, ',', '.') : '0,00' }}
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Sumber Dana:</strong> <br>{{ $qrCode->sumber_dana_unit ?? '-' }}
                                </p>
                                <p class="mb-0"><strong>No. Dokumen Perolehan:</strong>
                                    <br>{{ $qrCode->no_dokumen_perolehan_unit ?? '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-map-marker-alt me-2"></i>3. Lokasi & Kondisi Terkini
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <p class="mb-2"><strong>Lokasi/Pemegang:</strong><br>
                                    @if ($qrCode->id_pemegang_personal && $qrCode->pemegangPersonal)
                                        <span class="badge bg-primary fs-6"><i class="fas fa-user me-1"></i> Dipegang oleh:
                                            {{ $qrCode->pemegangPersonal->username }}</span>
                                    @elseif($qrCode->id_ruangan && $qrCode->ruangan)
                                        <span class="badge bg-info fs-6"><i class="fas fa-map-marker-alt me-1"></i>
                                            {{ $qrCode->ruangan->nama_ruangan }}
                                            ({{ $qrCode->ruangan->kode_ruangan }})</span>
                                    @else
                                        <span class="badge bg-secondary fs-6">Belum Ditempatkan</span>
                                    @endif
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Kondisi Terkini:</strong><br>
                                    <span @class([
                                        'badge fs-6',
                                        'bg-success' => $qrCode->kondisi == \App\Models\BarangQrCode::KONDISI_BAIK,
                                        'bg-warning text-dark' =>
                                            $qrCode->kondisi == \App\Models\BarangQrCode::KONDISI_KURANG_BAIK,
                                        'bg-danger' =>
                                            $qrCode->kondisi == \App\Models\BarangQrCode::KONDISI_RUSAK_BERAT,
                                        'bg-dark' => $qrCode->kondisi == \App\Models\BarangQrCode::KONDISI_HILANG,
                                        'bg-secondary' => !in_array(
                                            $qrCode->kondisi,
                                            \App\Models\BarangQrCode::getValidKondisi()),
                                    ])>
                                        {{ $qrCode->kondisi }}
                                    </span>
                                </p>
                                <p class="mb-0"><strong>Status Ketersediaan:</strong><br>
                                    <span @class([
                                        'badge fs-6',
                                        'bg-success' =>
                                            $qrCode->status == \App\Models\BarangQrCode::STATUS_TERSEDIA,
                                        'bg-info text-dark' =>
                                            $qrCode->status == \App\Models\BarangQrCode::STATUS_DIPINJAM,
                                        'bg-warning text-dark' =>
                                            $qrCode->status == \App\Models\BarangQrCode::STATUS_DALAM_PEMELIHARAAN,
                                        'bg-secondary' => !in_array(
                                            $qrCode->status,
                                            \App\Models\BarangQrCode::getValidStatus()),
                                    ])>
                                        {{ $qrCode->status }}
                                    </span>
                                    @if (
                                        $qrCode->arsip &&
                                            in_array($qrCode->arsip->status_arsip, [
                                                \App\Models\ArsipBarang::STATUS_ARSIP_DIAJUKAN,
                                                \App\Models\ArsipBarang::STATUS_ARSIP_DISETUJUI,
                                            ]))
                                        <small class="d-block text-danger fst-italic">(Sedang dalam proses
                                            pengarsipan)</small>
                                    @elseif($qrCode->arsip && $qrCode->arsip->status_arsip == \App\Models\ArsipBarang::STATUS_ARSIP_DISETUJUI_PERMANEN)
                                        <small class="d-block text-dark fst-italic">(Telah Diarsipkan Permanen)</small>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan (QR Code & Aksi) --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-qrcode me-2"></i>QR Code Unit</h5>
                    </div>
                    <div class="card-body text-center">
                        @if ($qrCode->qr_path && Storage::disk('public')->exists($qrCode->qr_path))
                            <img src="{{ asset('storage/' . $qrCode->qr_path) }}"
                                alt="QR Code {{ $qrCode->kode_inventaris_sekolah }}" class="img-fluid mb-2"
                                style="max-width: 200px; border: 1px solid #ddd; padding: 5px;">
                        @else
                            <p class="text-muted">QR Code belum tersedia atau gagal dimuat.</p>
                        @endif
                        @can('downloadQr', $qrCode)
                            <a href="{{ route($rolePrefix . 'barang-qr-code.download', $qrCode) }}"
                                class="btn btn-primary btn-sm w-100 mt-2">
                                <i class="fas fa-download me-1"></i> Download QR Code
                            </a>
                        @endcan
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-cogs me-2"></i>Aksi Unit</h5>
                    </div>
                    <div class="card-body d-grid gap-2">
                        {{-- Tombol Edit Info Unit --}}
                        @can('update', $qrCode)
                            <button type="button" class="btn btn-warning text-dark w-100 btn-edit-unit-trigger"
                                data-bs-toggle="modal" data-bs-target="#modalEditUnitBarang"
                                data-unit='@json($qrCode->load('barang'))' title="Edit Atribut Dasar Unit">
                                <i class="fas fa-edit me-1"></i> Edit Info Unit
                            </button>
                        @endcan

                        {{-- Jika barang diarsipkan, hanya tampilkan tombol Pulihkan --}}
                        @if ($qrCode->trashed())
                            @if ($qrCode->arsip)
                                {{-- Hanya Admin yang bisa me-restore dari arsip --}}
                                @can('restore', $qrCode)
                                    <form action="{{ route('admin.arsip-barang.restore', $qrCode->arsip->id) }}" method="POST"
                                        class="form-restore-arsip">
                                        @csrf
                                        <button type="submit" class="btn btn-success w-100 btn-restore"
                                            data-bs-toggle="tooltip"
                                            title="Pulihkan Unit Barang {{ $qrCode->kode_inventaris_sekolah }}">
                                            <i class="fas fa-undo me-1"></i> Pulihkan dari Arsip
                                        </button>
                                    </form>
                                @endcan
                            @endif
                        @else
                            {{-- Tampilkan tombol aksi jika tidak diarsipkan --}}

                            {{-- Hanya tampilkan aksi perpindahan jika barang TIDAK SEDANG DIPINJAM --}}
                            @if ($qrCode->status !== \App\Models\BarangQrCode::STATUS_DIPINJAM)

                                @if ($qrCode->id_pemegang_personal)
                                    {{-- Aksi jika dipegang personal --}}
                                    {{-- Kembalikan ke Ruangan: Hanya untuk Operator yang bisa mengembalikannya --}}
                                    @can('returnPersonal', $qrCode)
                                        <button type="button" class="btn btn-success w-100 btn-return-personal-trigger"
                                            data-bs-toggle="modal" data-bs-target="#modalReturnPersonal"
                                            data-unit-kode="{{ $qrCode->kode_inventaris_sekolah ?? 'N/A' }}"
                                            data-unit-pemegang="{{ $qrCode->pemegangPersonal->username ?? 'N/A' }}"
                                            data-url-action="{{ route($rolePrefix . 'barang-qr-code.return-personal', $qrCode->id) }}">
                                            <i class="fas fa-undo me-1"></i> Kembalikan ke Ruangan
                                        </button>
                                    @endcan
                                    {{-- Transfer Personal: Hanya untuk pemegang saat ini --}}
                                    @can('transferPersonal', $qrCode)
                                        <button type="button" class="btn btn-primary w-100 btn-transfer-personal-trigger"
                                            data-bs-toggle="modal" data-bs-target="#modalTransferPersonal"
                                            data-unit-kode="{{ $qrCode->kode_inventaris_sekolah ?? 'N/A' }}"
                                            data-unit-pemegang-lama="{{ $qrCode->pemegangPersonal->username ?? 'N/A' }}"
                                            data-url-action="{{ route($rolePrefix . 'barang-qr-code.transfer-personal', $qrCode->id) }}">
                                            <i class="fas fa-exchange-alt me-1"></i> Transfer Personal
                                        </button>
                                    @endcan
                                @else
                                    {{-- Aksi jika di ruangan atau mengambang --}}
                                    {{-- Mutasi/Tempatkan: Hanya untuk Operator --}}
                                    @can('mutasi', $qrCode)
                                        <button type="button" class="btn btn-primary w-100 btn-mutasi-unit-trigger"
                                            data-bs-toggle="modal" data-bs-target="#modalMutasiUnit"
                                            data-unit-id="{{ $qrCode->id }}"
                                            data-unit-kode="{{ $qrCode->kode_inventaris_sekolah }}"
                                            data-ruangan-asal-id="{{ $qrCode->id_ruangan }}"
                                            data-ruangan-asal-nama="{{ $qrCode->ruangan?->nama_ruangan ?? 'Belum Ditempatkan' }}"
                                            data-url-action="{{ route($rolePrefix . 'barang-qr-code.mutasi', $qrCode->id) }}">
                                            <i class="fas fa-truck me-1"></i>
                                            {{ $qrCode->id_ruangan ? 'Pindahkan' : 'Tempatkan di Ruangan' }}
                                        </button>
                                    @endcan
                                    {{-- Serahkan ke Personal: Hanya untuk Operator jika status Tersedia --}}
                                    @can('assignPersonal', $qrCode)
                                        @if ($qrCode->status === \App\Models\BarangQrCode::STATUS_TERSEDIA)
                                            <button type="button" class="btn btn-info w-100 btn-assign-personal-trigger"
                                                data-bs-toggle="modal" data-bs-target="#modalAssignPersonal"
                                                data-unit-id="{{ $qrCode->id }}"
                                                data-unit-kode="{{ $qrCode->kode_inventaris_sekolah ?? 'N/A' }}"
                                                data-url-action="{{ route($rolePrefix . 'barang-qr-code.assign-personal', $qrCode->id) }}">
                                                <i class="fas fa-user-plus me-1"></i> Serahkan ke Personal
                                            </button>
                                        @endif
                                    @endcan
                                @endif

                                {{-- Tombol Ajukan Pemeliharaan --}}
                                @if ($bisaLaporkanKerusakan)
                                    <a href="{{ route($rolePrefix . 'pemeliharaan.create', ['id_barang_qr_code' => $qrCode->id]) }}"
                                        class="btn btn-secondary">
                                        <i class="fas fa-tools me-2"></i>Ajukan Pemeliharaan
                                    </a>
                                @endif

                                {{-- Arsipkan Unit --}}
                                @can('archive', $qrCode)
                                    <button type="button" class="btn btn-danger w-100 btn-arsip-unit-trigger"
                                        data-bs-toggle="modal" data-bs-target="#modalArsipUnit"
                                        data-url="{{ route($rolePrefix . 'barang-qr-code.archive', $qrCode->id) }}"
                                        data-kode="{{ $qrCode->kode_inventaris_sekolah }}">
                                        <i class="fas fa-archive me-1"></i> Arsipkan Unit Ini
                                    </button>
                                @endcan
                            @else
                                <div class="alert alert-info text-center py-2 mb-0">
                                    <i class="fas fa-info-circle me-1"></i> Barang sedang dipinjam.
                                </div>
                            @endif
                        @endif

                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>4. Riwayat Unit</h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="accordionRiwayatUnit">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingBarangStatuses">
                                    <button class="accordion-button @if ($qrCode->barangStatuses->isEmpty()) collapsed @endif"
                                        type="button" data-bs-toggle="collapse" data-bs-target="#collapseBarangStatuses"
                                        aria-expanded="{{ $qrCode->barangStatuses->isNotEmpty() ? 'true' : 'false' }}"
                                        aria-controls="collapseBarangStatuses">
                                        Log Perubahan Status, Kondisi & Lokasi ({{ $qrCode->barangStatuses->count() }})
                                    </button>
                                </h2>
                                <div id="collapseBarangStatuses"
                                    class="accordion-collapse collapse @if ($qrCode->barangStatuses->isNotEmpty()) show @endif"
                                    aria-labelledby="headingBarangStatuses" data-bs-parent="#accordionRiwayatUnit">
                                    <div class="accordion-body">
                                        @if ($qrCode->barangStatuses->count() > 0)
                                            <ul class="list-group list-group-flush">
                                                @foreach ($qrCode->barangStatuses->sortByDesc('tanggal_pencatatan') as $log)
                                                    <li class="list-group-item">
                                                        <strong>{{ $log->tanggal_pencatatan ? \Carbon\Carbon::parse($log->tanggal_pencatatan)->isoFormat('DD MMM YYYY, HH:mm') : 'N/A' }}:</strong>
                                                        {{ $log->deskripsi_kejadian ?? 'Perubahan tercatat' }}.
                                                        <br><small class="text-muted">
                                                            Kondisi: {{ $log->kondisi_sebelumnya ?? '-' }} &rarr;
                                                            {{ $log->kondisi_sesudahnya ?? '-' }} |
                                                            Status: {{ $log->status_ketersediaan_sebelumnya ?? '-' }}
                                                            &rarr; {{ $log->status_ketersediaan_sesudahnya ?? '-' }} <br>
                                                            Lokasi Sblm:
                                                            {{ $log->ruanganSebelumnya?->nama_ruangan ?? ($log->pemegangPersonalSebelumnya?->username ?? '-') }}
                                                            |
                                                            Lokasi Skrg:
                                                            {{ $log->ruanganSesudahnya?->nama_ruangan ?? ($log->pemegangPersonalSesudahnya?->username ?? '-') }}
                                                            (Dicatat oleh: {{ $log->userPencatat?->username ?? 'Sistem' }})
                                                        </small>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p>Tidak ada log perubahan status, kondisi, atau lokasi untuk unit ini.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingMutasi">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseMutasi" aria-expanded="false"
                                        aria-controls="collapseMutasi">
                                        Riwayat Mutasi/Perpindahan Antar Ruangan ({{ $qrCode->mutasiDetails->count() }})
                                    </button>
                                </h2>
                                <div id="collapseMutasi" class="accordion-collapse collapse"
                                    aria-labelledby="headingMutasi" data-bs-parent="#accordionRiwayatUnit">
                                    <div class="accordion-body">
                                        @if ($qrCode->mutasiDetails->count() > 0)
                                            <ul class="list-group list-group-flush">
                                                @foreach ($qrCode->mutasiDetails->sortByDesc('tanggal_mutasi') as $mutasi)
                                                    <li class="list-group-item">
                                                        <strong>{{ $mutasi->tanggal_mutasi ? \Carbon\Carbon::parse($mutasi->tanggal_mutasi)->isoFormat('DD MMM YYYY, HH:mm') : 'N/A' }}:</strong>
                                                        Dipindahkan dari
                                                        <strong>{{ $mutasi->ruanganAsal?->nama_ruangan ?? 'N/A' }}</strong>
                                                        ke
                                                        <strong>{{ $mutasi->ruanganTujuan?->nama_ruangan ?? 'N/A' }}</strong>.
                                                        Alasan: {{ $mutasi->alasan_pemindahan ?? '-' }}.
                                                        (Oleh: {{ $mutasi->admin?->username ?? 'Sistem' }})
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p>Tidak ada riwayat mutasi antar ruangan untuk unit ini.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingPeminjaman">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapsePeminjaman" aria-expanded="false"
                                        aria-controls="collapsePeminjaman">
                                        Riwayat Peminjaman ({{ $qrCode->peminjamanDetails->count() }})
                                    </button>
                                </h2>
                                <div id="collapsePeminjaman" class="accordion-collapse collapse"
                                    aria-labelledby="headingPeminjaman" data-bs-parent="#accordionRiwayatUnit">
                                    <div class="accordion-body">
                                        @if ($qrCode->peminjamanDetails->count() > 0)
                                            <ul class="list-group list-group-flush">
                                                @foreach ($qrCode->peminjamanDetails->sortByDesc('created_at') as $detailPeminjaman)
                                                    <li class="list-group-item">
                                                        <strong>{{ $detailPeminjaman->peminjaman?->tanggal_pengajuan ? \Carbon\Carbon::parse($detailPeminjaman->peminjaman->tanggal_pengajuan)->isoFormat('DD MMM YYYY') : 'Tgl Tidak Ada' }}</strong>
                                                        (Peminjaman ID: <a
                                                            href="{{ route($rolePrefix . 'peminjaman.show', $detailPeminjaman->id_peminjaman) }}">{{ $detailPeminjaman->id_peminjaman }}</a>)
                                                        : Diajukan oleh
                                                        {{ $detailPeminjaman->peminjaman?->guru?->username ?? '-' }}.
                                                        Tujuan:
                                                        {{ Str::limit($detailPeminjaman->peminjaman?->tujuan_peminjaman, 50) }}.
                                                        Status Unit: <span
                                                            class="fw-bold">{{ $detailPeminjaman->status_unit }}</span>.
                                                        Diambil:
                                                        {{ $detailPeminjaman->tanggal_diambil ? \Carbon\Carbon::parse($detailPeminjaman->tanggal_diambil)->isoFormat('DD MMM YY, HH:mm') : '-' }}.
                                                        Dikembalikan:
                                                        {{ $detailPeminjaman->tanggal_dikembalikan ? \Carbon\Carbon::parse($detailPeminjaman->tanggal_dikembalikan)->isoFormat('DD MMM YY, HH:mm') : '-' }}.
                                                        Kondisi Setelah: {{ $detailPeminjaman->kondisi_setelah ?? '-' }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p>Tidak ada riwayat peminjaman untuk unit ini.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingPemeliharaan">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapsePemeliharaan" aria-expanded="false"
                                        aria-controls="collapsePemeliharaan">
                                        Riwayat Pemeliharaan ({{ $qrCode->pemeliharaanRecords->count() }})
                                    </button>
                                </h2>
                                <div id="collapsePemeliharaan" class="accordion-collapse collapse"
                                    aria-labelledby="headingPemeliharaan" data-bs-parent="#accordionRiwayatUnit">
                                    <div class="accordion-body">
                                        @if ($qrCode->pemeliharaanRecords->count() > 0)
                                            <ul class="list-group list-group-flush">
                                                @foreach ($qrCode->pemeliharaanRecords->sortByDesc('tanggal_pengajuan') as $pemeliharaan)
                                                    <li class="list-group-item">
                                                        <strong>{{ $pemeliharaan->tanggal_pengajuan ? \Carbon\Carbon::parse($pemeliharaan->tanggal_pengajuan)->isoFormat('DD MMM YYYY') : 'N/A' }}:</strong>
                                                        {{ $pemeliharaan->catatan_pengajuan ?? 'Pemeliharaan diajukan' }}.
                                                        Status Pengajuan: {{ $pemeliharaan->status_pengajuan }}.
                                                        Status Pengerjaan: {{ $pemeliharaan->status_pengerjaan }}.
                                                        Hasil: {{ $pemeliharaan->hasil_pemeliharaan ?? '-' }}.
                                                        Biaya: Rp
                                                        {{ number_format($pemeliharaan->biaya ?? 0, 0, ',', '.') }}.
                                                        (Pengaju: {{ $pemeliharaan->pengaju?->username ?? '-' }})
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p>Tidak ada riwayat pemeliharaan untuk unit ini.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- resources/views/admin/layouts/app.blade.php (atau halaman relevan) --}}
    <div class="modal fade" id="actionModal" tabindex="-1" role="dialog" aria-labelledby="actionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document"> {{-- modal-lg untuk form yang mungkin lebih lebar --}}
            <div class="modal-content">
                {{-- Konten akan dimuat di sini via AJAX --}}
                <div class="modal-body text-center">
                    <i class="fas fa-spinner fa-spin fa-3x"></i>
                    <p>Memuat formulir...</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal untuk Edit Unit --}}
    @include('pages.barang_qr_code.partials.modal_edit_unit', [
        'qrCode' => $qrCode, // $qrCode dari controller show
        'kondisiOptionsAll' => $kondisiOptionsAll,
        'statusOptionsAll' => $statusOptionsAll,
    ])
    {{-- Modal untuk Arsip Unit --}}
    @include('pages.barang_qr_code.partials.modal_arsip_unit', [
        'jenisPenghapusanOptions' => $jenisPenghapusanOptions,
    ])
    {{-- Modal untuk Mutasi Unit --}}
    @include('pages.barang_qr_code.partials.modal_mutasi_unit', [
        'ruanganListAll' => $ruanganListAll,
    ])

    {{--  modal_assign_personal, modal_return_personal dan modal_transfer_personal --}}
    @include('pages.barang_qr_code.partials.modal_assign_personal', [
        'usersForAssignForm' => $eligibleUsersForAssign, // Data dari controller show
        'barangQrCodeInstance' => $qrCode, // Untuk referensi jika perlu di dalam partial (opsional)
    ])
    @include('pages.barang_qr_code.partials.modal_return_personal', [
        'ruangansForReturnForm' => $ruanganListAll,
        'barangQrCodeInstance' => $qrCode,
    ])
    @include('pages.barang_qr_code.partials.modal_transfer_personal', [
        'usersForTransferForm' => $eligibleUsersForTransfer,
        'barangQrCodeInstance' => $qrCode,
    ])

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // JavaScript untuk memicu modal edit unit
            const editUnitTriggerButtons = document.querySelectorAll('.btn-edit-unit-trigger');
            const modalEditUnitElement = document.getElementById('modalEditUnitBarang');
            let modalEditUnitInstance = null;
            if (modalEditUnitElement) {
                modalEditUnitInstance = new bootstrap.Modal(modalEditUnitElement);
            }

            editUnitTriggerButtons.forEach(button => {
                button.addEventListener('click', function() {
                    if (!modalEditUnitInstance) {
                        console.error('Modal Edit Unit instance not found');
                        return;
                    }

                    const dataUnitString = this.getAttribute('data-unit');
                    if (!dataUnitString) {
                        console.error('Atribut data-unit tidak ditemukan.');
                        return;
                    }
                    let unitData;
                    try {
                        unitData = JSON.parse(dataUnitString);
                    } catch (error) {
                        console.error('Gagal parse JSON dari data-unit:', error);
                        return;
                    }

                    const form = document.getElementById('formEditUnitBarangAction');
                    if (!form) {
                        console.error('Form #formEditUnitBarangAction tidak ditemukan.');
                        return;
                    }

                    form.action =
                        `{{ url('barang-qr-code') }}/${unitData.id}`;

                    // Mengisi field modal
                    if (modalEditUnitElement.querySelector('#editUnitId')) modalEditUnitElement
                        .querySelector('#editUnitId').value = unitData.id;
                    if (modalEditUnitElement.querySelector('#editUnitKodeInventarisDisplay'))
                        modalEditUnitElement.querySelector('#editUnitKodeInventarisDisplay')
                        .textContent = unitData.kode_inventaris_sekolah ?? '-';

                    // Pastikan unitData.barang ada sebelum mengakses propertinya
                    if (unitData.barang) {
                        if (modalEditUnitElement.querySelector('#editUnitJenisBarangDisplay'))
                            modalEditUnitElement.querySelector('#editUnitJenisBarangDisplay')
                            .textContent =
                            `${unitData.barang.nama_barang} (${unitData.barang.kode_barang ?? '-'})`;
                        if (modalEditUnitElement.querySelector('#editUnitMerkModelDisplay'))
                            modalEditUnitElement.querySelector('#editUnitMerkModelDisplay')
                            .textContent = unitData.barang.merk_model ?? '-';
                    } else {
                        if (modalEditUnitElement.querySelector('#editUnitJenisBarangDisplay'))
                            modalEditUnitElement.querySelector('#editUnitJenisBarangDisplay')
                            .textContent = '-';
                        if (modalEditUnitElement.querySelector('#editUnitMerkModelDisplay'))
                            modalEditUnitElement.querySelector('#editUnitMerkModelDisplay')
                            .textContent = '-';
                    }

                    if (modalEditUnitElement.querySelector('#editUnitKodeInventarisSekolah'))
                        modalEditUnitElement.querySelector('#editUnitKodeInventarisSekolah').value =
                        unitData.kode_inventaris_sekolah ?? '';
                    if (modalEditUnitElement.querySelector('#editUnitNoSeriPabrik'))
                        modalEditUnitElement.querySelector('#editUnitNoSeriPabrik').value = unitData
                        .no_seri_pabrik ?? '';
                    if (modalEditUnitElement.querySelector('#editUnitDeskripsi'))
                        modalEditUnitElement.querySelector('#editUnitDeskripsi').value = unitData
                        .deskripsi_unit ?? '';
                    if (modalEditUnitElement.querySelector('#editUnitHargaPerolehan'))
                        modalEditUnitElement.querySelector('#editUnitHargaPerolehan').value =
                        unitData.harga_perolehan_unit ?? '';
                    if (unitData.tanggal_perolehan_unit) {
                        let tglPerolehan = new Date(unitData.tanggal_perolehan_unit);
                        if (modalEditUnitElement.querySelector('#editUnitTanggalPerolehan'))
                            modalEditUnitElement.querySelector('#editUnitTanggalPerolehan').value =
                            tglPerolehan.toISOString().split('T')[0];
                    } else {
                        if (modalEditUnitElement.querySelector('#editUnitTanggalPerolehan'))
                            modalEditUnitElement.querySelector('#editUnitTanggalPerolehan').value =
                            '';
                    }
                    if (modalEditUnitElement.querySelector('#editUnitSumberDana'))
                        modalEditUnitElement.querySelector('#editUnitSumberDana').value = unitData
                        .sumber_dana_unit ?? '';
                    if (modalEditUnitElement.querySelector('#editUnitNoDokumenPerolehan'))
                        modalEditUnitElement.querySelector('#editUnitNoDokumenPerolehan').value =
                        unitData.no_dokumen_perolehan_unit ?? '';
                    if (modalEditUnitElement.querySelector('#editUnitKondisi')) modalEditUnitElement
                        .querySelector('#editUnitKondisi').value = unitData.kondisi ?? '';
                    if (modalEditUnitElement.querySelector('#editUnitStatus')) modalEditUnitElement
                        .querySelector('#editUnitStatus').value = unitData.status ?? '';

                    const infoNoSeriPabrik = modalEditUnitElement.querySelector(
                        '#infoNoSeriPabrikEditUnit');
                    const inputNoSeriPabrik = modalEditUnitElement.querySelector(
                        '#editUnitNoSeriPabrik');

                    // Pastikan unitData.barang ada sebelum mengakses menggunakan_nomor_seri
                    if (inputNoSeriPabrik && unitData.barang && typeof unitData.barang
                        .menggunakan_nomor_seri !== 'undefined') {
                        inputNoSeriPabrik.disabled = !unitData.barang.menggunakan_nomor_seri;
                        if (infoNoSeriPabrik) infoNoSeriPabrik.textContent = !unitData.barang
                            .menggunakan_nomor_seri ?
                            'Jenis barang ini tidak menggunakan nomor seri.' : '';
                    } else if (inputNoSeriPabrik) {
                        // Jika unitData.barang tidak ada, nonaktifkan input nomor seri
                        inputNoSeriPabrik.disabled = true;
                        if (infoNoSeriPabrik) infoNoSeriPabrik.textContent =
                            'Informasi jenis barang tidak tersedia.';
                    }


                    const editUnitIdRuanganEl = modalEditUnitElement.querySelector(
                        '#editUnitIdRuangan');
                    if (editUnitIdRuanganEl) {
                        editUnitIdRuanganEl.value = unitData.id_ruangan ?? '';
                        editUnitIdRuanganEl.disabled = true;
                    }
                    const editUnitIdPemegangPersonalEl = modalEditUnitElement.querySelector(
                        '#editUnitIdPemegangPersonal');
                    if (editUnitIdPemegangPersonalEl) {
                        editUnitIdPemegangPersonalEl.value = unitData.id_pemegang_personal ?? '';
                        editUnitIdPemegangPersonalEl.disabled = true;
                    }
                    modalEditUnitInstance.show();
                });
            });

            // JavaScript untuk memicu modal arsip unit
            const arsipUnitTriggerButtons = document.querySelectorAll('.btn-arsip-unit-trigger');
            const modalArsipUnitElement = document.getElementById('modalArsipUnit');
            let modalArsipInstance = null;
            if (modalArsipUnitElement) {
                modalArsipInstance = new bootstrap.Modal(modalArsipUnitElement);
            }

            arsipUnitTriggerButtons.forEach(button => {
                button.addEventListener('click', function() {
                    if (!modalArsipInstance) {
                        console.error('Modal Arsip instance not found');
                        return;
                    }

                    const unitKode = this.getAttribute('data-kode');
                    const formUrl = this.getAttribute('data-url');
                    const form = document.getElementById('formArsipUnitAction');

                    if (!form) {
                        console.error('Form #formArsipUnitAction tidak ditemukan');
                        return;
                    }

                    form.action = formUrl;
                    if (modalArsipUnitElement.querySelector('#arsipUnitKodeDisplay'))
                        modalArsipUnitElement.querySelector('#arsipUnitKodeDisplay').textContent =
                        unitKode;

                    const konfirmasiLabel = modalArsipUnitElement.querySelector(
                        'label[for="inputKonfirmasiArsipUnit"]');

                    // Ganti teks konfirmasi modal arsip menjadi nama unit barang
                    const konfirmasiArsipUnitEl = modalArsipUnitElement.querySelector(
                        '#inputKonfirmasiArsipUnit');
                    if (konfirmasiArsipUnitEl) {
                        konfirmasiArsipUnitEl.setAttribute('data-expected-value', unitKode);
                    }
                    if (konfirmasiLabel) {
                        konfirmasiLabel.innerHTML =
                            `Ketik "<strong class="text-danger">${unitKode}</strong>" untuk konfirmasi pengarsipan unit:`;
                    }

                    form.reset();
                    modalArsipInstance.show();
                });
            });

            // Validasi input konfirmasi untuk modal arsip
            const formArsipUnit = document.getElementById('formArsipUnitAction');
            if (formArsipUnit) {
                formArsipUnit.addEventListener('submit', function(event) {
                    const inputKonfirmasi = formArsipUnit.querySelector('#inputKonfirmasiArsipUnit');
                    const expectedValue = inputKonfirmasi.getAttribute('data-expected-value');
                    if (inputKonfirmasi.value !== expectedValue) {
                        event.preventDefault();
                        // Anda bisa menambahkan feedback error di sini jika mau
                        alert(`Konfirmasi salah. Harap ketik "${expectedValue}" dengan benar.`);
                        inputKonfirmasi.focus();
                    }
                });
            }

            // JavaScript untuk memicu modal mutasi unit
            const mutasiUnitTriggerButtons = document.querySelectorAll('.btn-mutasi-unit-trigger');
            const modalMutasiUnitElement = document.getElementById('modalMutasiUnit');
            let modalMutasiInstance = null;
            if (modalMutasiUnitElement) {
                modalMutasiInstance = new bootstrap.Modal(modalMutasiUnitElement);
            }

            mutasiUnitTriggerButtons.forEach(button => {
                button.addEventListener('click', function() {
                    if (!modalMutasiInstance) return;

                    const unitId = this.getAttribute('data-unit-id');
                    const unitKode = this.getAttribute('data-unit-kode');
                    const ruanganAsalId = this.getAttribute('data-ruangan-asal-id');
                    const ruanganAsalNama = this.getAttribute('data-ruangan-asal-nama');

                    const form = document.getElementById('formMutasiUnitAction');
                    if (!form) return;

                    // PERBAIKAN: Menggunakan $rolePrefix untuk membuat URL yang benar
                    const rolePrefix =
                        "{{ $rolePrefix }}"; // Menghasilkan 'admin.' atau 'operator.'

                    // Gunakan template route yang benar dan ganti placeholder-nya
                    let actionUrl =
                        `{{ route('admin.barang-qr-code.mutasi', ['barangQrCode' => ':id']) }}`; // Default ke admin
                    if (rolePrefix === 'operator.') {
                        actionUrl =
                            `{{ route('operator.barang-qr-code.mutasi', ['barangQrCode' => ':id']) }}`;
                    }
                    form.action = actionUrl.replace(':id', unitId);

                    // Mengisi data ke dalam modal (kode Anda sudah benar)
                    if (modalMutasiUnitElement.querySelector('#mutasiUnitKodeDisplay'))
                        modalMutasiUnitElement.querySelector('#mutasiUnitKodeDisplay').textContent =
                        unitKode;
                    if (modalMutasiUnitElement.querySelector('#mutasiRuanganAsalDisplay'))
                        modalMutasiUnitElement.querySelector('#mutasiRuanganAsalDisplay')
                        .textContent = ruanganAsalNama || 'Belum Ditempatkan';

                    const ruanganTujuanSelect = modalMutasiUnitElement.querySelector(
                        '#mutasiIdRuanganTujuan');
                    if (ruanganTujuanSelect) {
                        Array.from(ruanganTujuanSelect.options).forEach(option => {
                            option.style.display = (option.value == ruanganAsalId) ?
                                'none' : '';
                        });
                        if (ruanganTujuanSelect.value == ruanganAsalId) {
                            ruanganTujuanSelect.value = '';
                        }
                    }

                    form.reset(); // Reset form setiap kali modal dibuka
                    modalMutasiInstance.show();
                });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Konfigurasi Umum ---
            // Fungsi untuk membersihkan error validasi pada form
            function clearFormErrors(formElement) {
                $(formElement).find('.is-invalid').removeClass('is-invalid');
                $(formElement).find('.invalid-feedback').remove();
            }
            // Fungsi untuk menampilkan error validasi dari AJAX
            function displayFormErrors(formElement, errors) {
                $.each(errors, function(key, value) {
                    var field = $(formElement).find('[name="' + key + '"], [name="' + key + '[]"]');
                    if (field.length === 0) { // Coba cari berdasarkan id jika name tidak ketemu
                        field = $(formElement).find('#' + key.replace(/\./g,
                            '_')); // Ganti titik dengan underscore untuk ID
                    }
                    if (field.length > 0) {
                        field.addClass('is-invalid');
                        // Hapus pesan error lama jika ada
                        field.closest('.mb-3').find('.invalid-feedback').remove();
                        // Tambah pesan error baru
                        field.closest('.mb-3').append('<div class="invalid-feedback d-block">' + value[0] +
                            '</div>');
                    } else {
                        console.warn('Field untuk error tidak ditemukan:', key);
                    }
                });
                toastr.error('Silakan periksa kembali input Anda.', 'Error Validasi');
            }

            // Fungsi untuk menangani submit form AJAX
            function handleAjaxFormSubmit(formElement, modalInstance) {
                $(formElement).off('submit').on('submit', function(e) {
                    e.preventDefault();
                    var form = $(this);
                    var url = form.attr('action');
                    var method = form.attr('method');
                    var formData = new FormData(this);
                    var submitButton = form.find('button[type="submit"]');
                    var originalButtonText = submitButton.html();

                    submitButton.html('<i class="fas fa-spinner fa-spin"></i> Memproses...').prop(
                        'disabled', true);
                    clearFormErrors(formElement);

                    $.ajax({
                        url: url,
                        type: method,
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function(response) {
                            submitButton.html(originalButtonText).prop('disabled', false);
                            if (response.success) {
                                if (modalInstance) {
                                    modalInstance.hide();
                                }
                                toastr.success(response.message || 'Operasi berhasil!');
                                if (response.redirect_url) {
                                    setTimeout(function() { // Beri waktu untuk notifikasi terlihat
                                        window.location.href = response.redirect_url;
                                    }, 1000);
                                } else {
                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 1000);
                                }
                            } else {
                                toastr.error(response.message || 'Terjadi kesalahan.');
                            }
                        },
                        error: function(xhr) {
                            submitButton.html(originalButtonText).prop('disabled', false);
                            if (xhr.status === 422) { // Error validasi
                                displayFormErrors(formElement, xhr.responseJSON.errors);
                            } else {
                                var errorMsg = 'Terjadi kesalahan server.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMsg = xhr.responseJSON.message;
                                }
                                toastr.error(errorMsg, 'Error Server');
                            }
                        }
                    });
                });
            }

            // --- Modal Serahkan ke Personal ---
            const modalAssignPersonalElement = document.getElementById('modalAssignPersonal');
            let modalAssignPersonalInstance = null;
            if (modalAssignPersonalElement) {
                modalAssignPersonalInstance = new bootstrap.Modal(modalAssignPersonalElement);
                const formAssignPersonal = document.getElementById('formAssignPersonalAction');

                document.querySelectorAll('.btn-assign-personal-trigger').forEach(button => {
                    button.addEventListener('click', function() {
                        const unitKode = this.getAttribute('data-unit-kode');
                        const formActionUrl = this.getAttribute('data-url-action');

                        if (!formAssignPersonal) {
                            console.error('Form #formAssignPersonalAction tidak ditemukan');
                            return;
                        }
                        formAssignPersonal.action = formActionUrl;
                        if (modalAssignPersonalElement.querySelector('#assignUnitKodeDisplay')) {
                            modalAssignPersonalElement.querySelector('#assignUnitKodeDisplay')
                                .textContent = unitKode;
                        }

                        formAssignPersonal.reset();
                        clearFormErrors(formAssignPersonal);
                        $('#assignIdPemegangPersonal').val(null).trigger('change'); // Reset Select2

                        modalAssignPersonalInstance.show();
                    });
                });
                if (formAssignPersonal) handleAjaxFormSubmit(formAssignPersonal, modalAssignPersonalInstance);
            }

            // --- Modal Kembalikan ke Ruangan ---
            const modalReturnPersonalElement = document.getElementById('modalReturnPersonal');
            let modalReturnPersonalInstance = null;
            if (modalReturnPersonalElement) {
                modalReturnPersonalInstance = new bootstrap.Modal(modalReturnPersonalElement);
                const formReturnPersonal = document.getElementById('formReturnPersonalAction');

                document.querySelectorAll('.btn-return-personal-trigger').forEach(button => {
                    button.addEventListener('click', function() {
                        const unitKode = this.getAttribute('data-unit-kode');
                        const pemegangSaatIni = this.getAttribute('data-unit-pemegang');
                        const formActionUrl = this.getAttribute('data-url-action');

                        if (!formReturnPersonal) {
                            console.error('Form #formReturnPersonalAction tidak ditemukan');
                            return;
                        }
                        formReturnPersonal.action = formActionUrl;
                        if (modalReturnPersonalElement.querySelector('#returnUnitKodeDisplay')) {
                            modalReturnPersonalElement.querySelector('#returnUnitKodeDisplay')
                                .textContent = unitKode;
                        }
                        if (modalReturnPersonalElement.querySelector(
                                '#returnUnitPemegangDisplay')) {
                            modalReturnPersonalElement.querySelector('#returnUnitPemegangDisplay')
                                .textContent = pemegangSaatIni || 'N/A';
                        }

                        formReturnPersonal.reset();
                        clearFormErrors(formReturnPersonal);
                        $('#returnIdRuanganTujuan').val(null).trigger('change'); // Reset Select2

                        modalReturnPersonalInstance.show();
                    });
                });
                if (formReturnPersonal) handleAjaxFormSubmit(formReturnPersonal, modalReturnPersonalInstance);
            }

            // --- Modal Transfer Personal ---
            const modalTransferPersonalElement = document.getElementById('modalTransferPersonal');
            let modalTransferPersonalInstance = null;
            if (modalTransferPersonalElement) {
                modalTransferPersonalInstance = new bootstrap.Modal(modalTransferPersonalElement);
                const formTransferPersonal = document.getElementById('formTransferPersonalAction');

                document.querySelectorAll('.btn-transfer-personal-trigger').forEach(button => {
                    button.addEventListener('click', function() {
                        const unitKode = this.getAttribute('data-unit-kode');
                        const pemegangLama = this.getAttribute('data-unit-pemegang-lama');
                        const formActionUrl = this.getAttribute('data-url-action');

                        if (!formTransferPersonal) {
                            console.error('Form #formTransferPersonalAction tidak ditemukan');
                            return;
                        }
                        formTransferPersonal.action = formActionUrl;
                        if (modalTransferPersonalElement.querySelector(
                                '#transferUnitKodeDisplay')) {
                            modalTransferPersonalElement.querySelector('#transferUnitKodeDisplay')
                                .textContent = unitKode;
                        }
                        if (modalTransferPersonalElement.querySelector(
                                '#transferUnitPemegangLamaDisplay')) {
                            modalTransferPersonalElement.querySelector(
                                    '#transferUnitPemegangLamaDisplay').textContent =
                                pemegangLama || 'N/A';
                        }

                        formTransferPersonal.reset();
                        clearFormErrors(formTransferPersonal);
                        $('#transferNewIdPemegangPersonal').val(null).trigger(
                            'change'); // Reset Select2
                        modalTransferPersonalInstance.show();
                    });
                });
                if (formTransferPersonal) handleAjaxFormSubmit(formTransferPersonal, modalTransferPersonalInstance);
            }
            $('#modalAssignPersonal, #modalReturnPersonal, #modalTransferPersonal').on('shown.bs.modal',
                function() {
                    $(this).find('.select2-basic').select2({
                        dropdownParent: $(this), // Penting untuk Select2 dalam modal Bootstrap
                        placeholder: "-- Pilih --",
                        allowClear: true
                    });
                });
        });
    </script>
@endpush
