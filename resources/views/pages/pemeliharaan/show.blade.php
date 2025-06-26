@extends('layouts.app')

@section('title', 'Detail Laporan Pemeliharaan #' . $pemeliharaan->id)

@push('styles')
    <style>
        /* CSS untuk Progress Tracker */
        .progress-tracker {
            display: flex;
            justify-content: space-between;
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem 0;
        }

        .progress-step {
            text-align: center;
            position: relative;
            width: 100%;
            transition: all 0.3s ease;
        }

        .progress-step .progress-marker {
            height: 2.5rem;
            width: 2.5rem;
            border-radius: 50%;
            background-color: #e9ecef;
            border: 3px solid #dee2e6;
            margin: 0 auto 0.5rem auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #495057;
            transition: all 0.3s ease;
        }

        .progress-step .progress-label {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 500;
        }

        .progress-step .progress-line {
            position: absolute;
            top: 1.25rem;
            /* Setengah dari tinggi marker */
            left: -50%;
            right: 50%;
            height: 3px;
            background-color: #dee2e6;
            z-index: -1;
            transition: all 0.3s ease;
        }

        .progress-step:first-child .progress-line {
            display: none;
        }

        /* Status Aktif dan Selesai */
        .progress-step.is-complete .progress-marker {
            background-color: #198754;
            /* success */
            border-color: #198754;
            color: white;
        }

        .progress-step.is-complete .progress-label {
            color: #212529;
            font-weight: 600;
        }

        .progress-step.is-complete .progress-line {
            background-color: #198754;
        }

        .progress-step.is-active .progress-marker {
            background-color: #0dcaf0;
            /* info */
            border-color: #0dcaf0;
            color: white;
            transform: scale(1.1);
        }

        .progress-step.is-active .progress-label {
            color: #0dcaf0;
            font-weight: 700;
        }

        /* Status Ditolak */
        .progress-step.is-rejected .progress-marker {
            background-color: #dc3545;
            /* danger */
            border-color: #dc3545;
            color: white;
        }

        .progress-step.is-rejected .progress-label {
            color: #dc3545;
            font-weight: 700;
        }

        .progress-step.is-rejected~.progress-step .progress-line {
            background-color: #dee2e6;
        }

        .detail-label {
            font-weight: 600;
            color: #555;
        }

        .card-item-detail {
            background-color: #f8f9fa;
            border-radius: .25rem;
            border: 1px solid #e9ecef;
        }
    </style>
@endpush

@php
    $rolePrefix = Auth::user()->getRolePrefix();
@endphp

@section('content')
    <div class="container-fluid">
        {{-- Page Title & Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Detail Pemeliharaan: ID #{{ $pemeliharaan->id }}</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route($rolePrefix . 'pemeliharaan.index') }}">Pemeliharaan</a></li>
                            <li class="breadcrumb-item active">Detail Laporan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Kolom Kiri: Detail Unit & Aksi Cepat --}}
            <div class="col-xl-4 col-lg-5">

                {{-- GANTI SELURUH CARD "DETAIL UNIT BARANG" ANDA DENGAN INI --}}

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-box me-2"></i>Detail Unit Barang</h5>
                    </div>
                    {{-- GANTI SELURUH CARD "DETAIL UNIT BARANG" DARI <div class="card..."> HINGGA </div> PENUTUPNYA --}}

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-box me-2"></i>Detail Unit Barang</h5>
                        </div>
                        <div class="card-body">
                            @if ($pemeliharaan->barangQrCode)
                                <p class="mb-2"><span class="detail-label">Kode Inventaris:</span>
                                    <a href="{{ route($rolePrefix . 'barang-qr-code.show', $pemeliharaan->barangQrCode->id) }}"
                                        target="_blank">
                                        <code>{{ $pemeliharaan->barangQrCode->kode_inventaris_sekolah }}</code>
                                    </a>
                                </p>
                                <p class="mb-2"><span class="detail-label">Nama Barang:</span>
                                    {{ optional($pemeliharaan->barangQrCode->barang)->nama_barang ?? 'N/A' }}</p>
                                <p class="mb-2"><span class="detail-label">No. Seri:</span>
                                    {{ $pemeliharaan->barangQrCode->no_seri_pabrik ?: '-' }}</p>

                                <hr class="my-3">

                                <h6 class="text-muted">Snapshot Saat Laporan Dibuat</h6>
                                <div class="p-3 card-item-detail mb-3">
                                    <p class="mb-2"><span class="detail-label">Kondisi:</span>
                                        @php
                                            // Logika warna langsung di view untuk snapshot kondisi
                                            $kondisiSaatLaporColor = 'secondary'; // Warna default
                                            if ($pemeliharaan->kondisi_saat_lapor) {
                                                $kondisiSaatLaporColor = match (
                                                    strtolower($pemeliharaan->kondisi_saat_lapor)
                                                ) {
                                                    'baik' => 'success',
                                                    'kurang baik' => 'warning',
                                                    'rusak berat' => 'danger',
                                                    'hilang' => 'dark',
                                                    default => 'secondary',
                                                };
                                            }
                                        @endphp
                                        <span class="badge text-bg-{{ $kondisiSaatLaporColor }}">
                                            {{ $pemeliharaan->kondisi_saat_lapor ?? 'N/A' }}
                                        </span>
                                    </p>
                                    <p class="mb-0"><span class="detail-label">Status Ketersediaan:</span>
                                        @php
                                            // Logika warna langsung di view untuk snapshot status
                                            $statusSaatLaporColor = 'secondary'; // Warna default
                                            if ($pemeliharaan->status_saat_lapor) {
                                                $statusSaatLaporColor = match (
                                                    strtolower($pemeliharaan->status_saat_lapor)
                                                ) {
                                                    'tersedia' => 'success',
                                                    'dipinjam' => 'primary',
                                                    'dalam pemeliharaan' => 'info',
                                                    'diarsipkan/dihapus' => 'dark',
                                                    default => 'secondary',
                                                };
                                            }
                                        @endphp
                                        <span class="badge text-bg-{{ $statusSaatLaporColor }}">
                                            {{ $pemeliharaan->status_saat_lapor ?? 'N/A' }}
                                        </span>
                                    </p>
                                </div>

                                <h6 class="text-muted">Data Live Barang Saat Ini</h6>
                                <div class="p-3 card-item-detail">
                                    <p class="mb-2"><span class="detail-label">Kondisi Terkini:</span>
                                        @php
                                            $kondisiTerkiniColor = 'secondary'; // Warna default
                                            if (optional($pemeliharaan->barangQrCode)->kondisi) {
                                                $kondisiTerkiniColor = match (
                                                    strtolower($pemeliharaan->barangQrCode->kondisi)
                                                ) {
                                                    'baik' => 'success',
                                                    'kurang baik' => 'warning',
                                                    'rusak berat' => 'danger',
                                                    'hilang' => 'dark',
                                                    default => 'secondary',
                                                };
                                            }
                                        @endphp
                                        <span class="badge text-bg-{{ $kondisiTerkiniColor }}">
                                            {{ optional($pemeliharaan->barangQrCode)->kondisi ?? 'N/A' }}
                                        </span>
                                    </p>
                                    <p class="mb-0"><span class="detail-label">Status Terkini:</span>
                                        @php
                                            $statusTerkiniColor = 'secondary'; // Warna default
                                            if (optional($pemeliharaan->barangQrCode)->status) {
                                                $statusTerkiniColor = match (
                                                    strtolower($pemeliharaan->barangQrCode->status)
                                                ) {
                                                    'tersedia' => 'success',
                                                    'dipinjam' => 'primary',
                                                    'dalam pemeliharaan' => 'info',
                                                    'diarsipkan/dihapus' => 'dark',
                                                    default => 'secondary',
                                                };
                                            }
                                        @endphp
                                        <span class="badge text-bg-{{ $statusTerkiniColor }}">
                                            {{ optional($pemeliharaan->barangQrCode)->status ?? 'N/A' }}
                                        </span>
                                    </p>
                                </div>
                            @else
                                <div class="alert alert-danger">
                                    <strong>Data Error:</strong> Unit barang yang terkait dengan laporan ini tidak dapat
                                    ditemukan.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- CARD AKSI DINAMIS --}}
                @if (!$pemeliharaan->trashed())
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Tindakan & Aksi</h5>
                        </div>
                        <div class="card-body">

                            {{-- FORM PERSETUJUAN --}}
                            @if ($pemeliharaan->status === 'Diajukan')
                                @can('approveOrReject', $pemeliharaan)
                                    <form id="form-approval" method="POST">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="id_operator_pengerjaan" class="form-label fw-bold">Tugaskan PIC <span
                                                    class="text-danger">*</span></label>
                                            <select name="id_operator_pengerjaan" id="id_operator_pengerjaan"
                                                class="form-select @error('id_operator_pengerjaan') is-invalid @enderror">
                                                <option value="">-- Pilih Operator --</option>
                                                @foreach ($picList as $pic)
                                                    <option value="{{ $pic->id }}">{{ $pic->username }}</option>
                                                @endforeach
                                            </select>
                                            @error('id_operator_pengerjaan')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="catatan-approval" class="form-label fw-bold">Catatan
                                                Persetujuan/Penolakan</label>
                                            <textarea id="catatan-approval" class="form-control" rows="3" placeholder="Isi catatan jika perlu..."></textarea>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button type="button" id="btn-tolak" class="btn btn-danger w-100">Tolak</button>
                                            <button type="button" id="btn-setujui"
                                                class="btn btn-success w-100">Setujui</button>
                                        </div>
                                    </form>
                                @else
                                    <p class="text-muted fst-italic">Menunggu persetujuan dari Admin.</p>
                                @endcan

                                {{-- FORM MULAI PERBAIKAN --}}
                            @elseif($pemeliharaan->status === 'Disetujui')
                                @can('startWork', $pemeliharaan)
                                    <p class="text-muted mb-2">Laporan telah disetujui. Klik tombol di bawah jika Anda sudah
                                        menerima unit fisik dan siap memulai perbaikan.</p>
                                    <form action="{{ route($rolePrefix . 'pemeliharaan.startWork', $pemeliharaan->id) }}"
                                        method="POST" id="form-start-work">
                                        @csrf
                                        <button class="btn btn-primary w-100" type="submit"><i class="fas fa-play me-1"></i>
                                            Terima Barang & Mulai Perbaikan</button>
                                    </form>
                                @else
                                    <p class="text-muted fst-italic">Laporan disetujui. Menunggu PIC memulai perbaikan.</p>
                                @endcan

                                {{-- FORM SELESAIKAN PEKERJAAN --}}
                            @elseif($pemeliharaan->status === 'Dalam Perbaikan')
                                @can('completeWork', $pemeliharaan)
                                    <form action="{{ route($rolePrefix . 'pemeliharaan.completeWork', $pemeliharaan->id) }}"
                                        method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="deskripsi_pekerjaan" class="form-label fw-bold">Deskripsi Pekerjaan Yang
                                                Dilakukan <span class="text-danger">*</span></label>
                                            <textarea name="deskripsi_pekerjaan" id="deskripsi_pekerjaan"
                                                class="form-control @error('deskripsi_pekerjaan') is-invalid @enderror" rows="3" required>{{ old('deskripsi_pekerjaan') }}</textarea>
                                            @error('deskripsi_pekerjaan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="hasil_pemeliharaan" class="form-label fw-bold">Hasil Akhir <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="hasil_pemeliharaan" id="hasil_pemeliharaan"
                                                class="form-control @error('hasil_pemeliharaan') is-invalid @enderror" required
                                                value="{{ old('hasil_pemeliharaan') }}">
                                            @error('hasil_pemeliharaan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="mb-3">
                                            <label for="kondisi_barang_setelah_pemeliharaan"
                                                class="form-label fw-bold">Kondisi
                                                Barang Setelah Perbaikan <span class="text-danger">*</span></label>
                                            <select name="kondisi_barang_setelah_pemeliharaan"
                                                id="kondisi_barang_setelah_pemeliharaan" class="form-select" required>
                                                @foreach (\App\Models\BarangQrCode::getValidKondisi() as $kondisi)
                                                    <option value="{{ $kondisi }}"
                                                        {{ old('kondisi_barang_setelah_pemeliharaan') == $kondisi ? 'selected' : '' }}>
                                                        {{ $kondisi }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="biaya" class="form-label fw-bold">Biaya Perbaikan (Rp)</label>
                                            <input type="number" name="biaya" id="biaya" class="form-control"
                                                value="{{ old('biaya', 0) }}" min="0">
                                        </div>
                                        <div class="mb-3">
                                            <label for="foto_perbaikan" class="form-label fw-bold">Unggah Foto Setelah
                                                Perbaikan (Opsional)</label>
                                            <input type="file" name="foto_perbaikan" id="foto_perbaikan"
                                                class="form-control @error('foto_perbaikan') is-invalid @enderror"
                                                accept="image/*">
                                            @error('foto_perbaikan')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <button type="submit" class="btn btn-success w-100"><i
                                                class="fas fa-check-double me-1"></i> Selesaikan Pekerjaan</button>
                                    </form>
                                @else
                                    <p class="text-muted fst-italic">Barang sedang dalam proses perbaikan oleh PIC.</p>
                                @endcan
                            @elseif($pemeliharaan->status === 'Selesai')
                                @can('confirmHandover', $pemeliharaan)
                                    <form
                                        action="{{ route($rolePrefix . 'pemeliharaan.confirmHandover', $pemeliharaan->id) }}"
                                        method="POST" id="form-confirm-handover" enctype="multipart/form-data">
                                        @csrf
                                        <p class="text-muted mb-2">Perbaikan telah selesai. Unggah foto bukti serah terima
                                            kepada pelapor untuk menyelesaikan laporan ini.</p>
                                        <div class="mb-3">
                                            <label for="foto_tuntas" class="form-label fw-bold">Bukti Foto Serah Terima <span
                                                    class="text-danger">*</span></label>
                                            <input type="file" name="foto_tuntas"
                                                class="form-control @error('foto_tuntas') is-invalid @enderror" required
                                                accept="image/*">
                                            @error('foto_tuntas')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <button class="btn btn-primary w-100" type="submit"><i
                                                class="fas fa-camera me-1"></i> Unggah Bukti & Tuntaskan</button>
                                    </form>
                                @else
                                    <p class="text-muted fst-italic">Perbaikan selesai. Menunggu konfirmasi serah terima dari
                                        PIC atau Pelapor.</p>
                                @endcan
                                {{-- ======================================================= --}}
                            @else
                                <p class="text-muted">Tidak ada aksi yang tersedia untuk status
                                    '{{ $pemeliharaan->status }}'.</p>
                            @endif

                        </div>
                    </div>
                @endif
            </div>

            {{-- Kolom Kanan: Detail Laporan & Progres --}}
            <div class="col-xl-8 col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Rincian Laporan & Progres</h5>
                            <span
                                class="badge fs-6 bg-{{ $pemeliharaan->status_color }}">{{ $pemeliharaan->status }}</span>
                        </div>
                    </div>
                    <div class="card-body">

                        {{-- PROGRESS TRACKER VISUAL --}}
                        @php
                            $statuses = [
                                \App\Models\Pemeliharaan::STATUS_DIAJUKAN,
                                \App\Models\Pemeliharaan::STATUS_DISETUJUI,
                                \App\Models\Pemeliharaan::STATUS_DALAM_PERBAIKAN,
                                \App\Models\Pemeliharaan::STATUS_SELESAI,
                                \App\Models\Pemeliharaan::STATUS_TUNTAS,
                            ];
                            $currentStatus = $pemeliharaan->status;
                            $isRejected = $currentStatus === \App\Models\Pemeliharaan::STATUS_DITOLAK;

                            $currentStatusIndex = array_search($currentStatus, $statuses);
                            // Jika ditolak, anggap progresnya berhenti setelah tahap 'Diajukan'
                            if ($isRejected) {
                                $currentStatusIndex = 0;
                            }
                        @endphp
                        <ol class="progress-tracker">
                            @foreach ($statuses as $index => $status)
                                @php
                                    $stepClass = '';
                                    if ($index <= $currentStatusIndex) {
                                        $stepClass = 'is-complete';
                                    }
                                    if ($index == $currentStatusIndex && !$isRejected) {
                                        $stepClass = 'is-active';
                                    }
                                    if ($isRejected && $status === \App\Models\Pemeliharaan::STATUS_DISETUJUI) {
                                        $stepClass = 'is-rejected';
                                    }
                                @endphp
                                <li class="progress-step {{ $stepClass }}">
                                    <div class="progress-marker">
                                        @if ($isRejected && $status === \App\Models\Pemeliharaan::STATUS_DISETUJUI)
                                            <i class="fas fa-times"></i>
                                        @elseif($index < $currentStatusIndex || $currentStatus === \App\Models\Pemeliharaan::STATUS_SELESAI)
                                            <i class="fas fa-check"></i>
                                        @else
                                            {{-- Disesuaikan agar ikon lebih relevan per tahap --}}
                                            @if ($status === 'Diajukan')
                                                <i class="fas fa-flag"></i>
                                            @endif
                                            @if ($status === 'Disetujui')
                                                <i class="fas fa-user-check"></i>
                                            @endif
                                            @if ($status === 'Dalam Perbaikan')
                                                <i class="fas fa-cogs"></i>
                                            @endif
                                            @if ($status === 'Selesai')
                                                <i class="fas fa-check-double"></i>
                                            @endif
                                            @if ($status === 'Tuntas')
                                                <i class="fas fa-handshake"></i>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="progress-label">
                                        {{ $isRejected && $status === \App\Models\Pemeliharaan::STATUS_DISETUJUI ? 'Ditolak' : $status }}
                                    </div>
                                    <div class="progress-line"></div>
                                </li>
                            @endforeach
                        </ol>
                        <hr>

                        {{-- RINCIAN PROGRES --}}
                        <div class="mt-4">
                            {{-- Bagian Pengajuan --}}
                            <div class="d-flex mb-4">
                                <div class="flex-shrink-0 text-center me-3" style="width: 2.5rem;">
                                    <i class="fas fa-flag text-primary fs-3"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Pengajuan Laporan</h6>
                                    <p class="text-muted mb-2">Diajukan oleh
                                        <strong>{{ optional($pemeliharaan->pengaju)->username ?? 'N/A' }}</strong> pada
                                        {{ $pemeliharaan->tanggal_pengajuan->isoFormat('dddd, DD MMMM YYYY - HH:mm') }}
                                    </p>
                                    <div class="p-3 card-item-detail">
                                        <p class="detail-label mb-1">Deskripsi Kerusakan/Keluhan:</p>
                                        <p class="mb-0">{{ $pemeliharaan->catatan_pengajuan ?: '-' }}</p>
                                    </div>
                                    @if ($pemeliharaan->foto_kerusakan_path)
                                        <div class="mt-3">
                                            <p class="detail-label mb-1">Foto Bukti Kerusakan:</p>
                                            <a href="{{ asset('storage/' . $pemeliharaan->foto_kerusakan_path) }}"
                                                target="_blank">
                                                <img src="{{ asset('storage/' . $pemeliharaan->foto_kerusakan_path) }}"
                                                    alt="Foto Kerusakan" class="img-fluid rounded"
                                                    style="max-height: 200px;">
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Bagian Persetujuan --}}
                            <div class="d-flex mb-4">
                                <div class="flex-shrink-0 text-center me-3" style="width: 2.5rem;">
                                    <i
                                        class="fas fa-user-check fs-3 {{ $currentStatusIndex >= 1 || $isRejected ? 'text-success' : 'text-muted' }}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Tahap Persetujuan</h6>
                                    @if ($currentStatusIndex >= 1 || $isRejected)
                                        <p class="text-muted mb-2">Diproses oleh
                                            <strong>{{ optional($pemeliharaan->penyetuju)->username ?? 'N/A' }}</strong>
                                            pada
                                            {{ optional($pemeliharaan->tanggal_persetujuan)->isoFormat('DD MMM YYYY, HH:mm') ?? '...' }}
                                        </p>
                                        @if ($pemeliharaan->catatan_persetujuan)
                                            @if ($isRejected)
                                                <div class="p-3 card-item-detail border-danger bg-light">
                                                    <p class="detail-label text-danger mb-1">Alasan Penolakan:</p>
                                                    <p class="mb-0">{{ $pemeliharaan->catatan_persetujuan }}</p>
                                                </div>
                                            @else
                                                <div class="p-3 card-item-detail">
                                                    <p class="detail-label mb-1">Catatan Persetujuan:</p>
                                                    <p class="mb-0">{{ $pemeliharaan->catatan_persetujuan }}</p>
                                                </div>
                                            @endif
                                        @endif
                                    @else
                                        <p class="text-muted mb-0 fst-italic">Menunggu persetujuan.</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Bagian Pengerjaan --}}
                            <div class="d-flex">
                                <div class="flex-shrink-0 text-center me-3" style="width: 2.5rem;">
                                    <i
                                        class="fas fa-cogs fs-3 {{ $currentStatusIndex >= 2 ? 'text-warning' : 'text-muted' }}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Tahap Pengerjaan</h6>
                                    @if ($currentStatusIndex >= 2 && !$isRejected)
                                        <p class="text-muted mb-2">Dikerjakan oleh
                                            <strong>{{ optional($pemeliharaan->operatorPengerjaan)->username ?? 'N/A' }}</strong>
                                        </p>
                                        @if ($pemeliharaan->deskripsi_pekerjaan)
                                            <div class="p-3 card-item-detail">
                                                <p class="detail-label mb-1">Laporan Pekerjaan:</p>
                                                <p class="mb-0">{{ $pemeliharaan->deskripsi_pekerjaan }}</p>
                                                <hr class="my-2">
                                                <p class="mb-2"><span class="detail-label">Hasil:</span>
                                                    {{ $pemeliharaan->hasil_pemeliharaan ?? '-' }}</p>
                                                <p class="mb-2"><span class="detail-label">Kondisi Setelah
                                                        Perbaikan:</span>
                                                    <span
                                                        class="badge bg-{{ \App\Models\BarangQrCode::getKondisiColor($pemeliharaan->kondisi_barang_setelah_pemeliharaan) }}">
                                                        {{ $pemeliharaan->kondisi_barang_setelah_pemeliharaan }}
                                                    </span>
                                                </p>
                                                <p class="mb-0"><span class="detail-label">Biaya:</span> Rp
                                                    {{ number_format($pemeliharaan->biaya, 0, ',', '.') }}</p>
                                            </div>
                                        @endif
                                        @if ($pemeliharaan->foto_perbaikan_path)
                                            <div class="mt-3">
                                                <p class="detail-label mb-1">Foto Bukti Perbaikan:</p>
                                                <a href="{{ asset('storage/' . $pemeliharaan->foto_perbaikan_path) }}"
                                                    target="_blank">
                                                    <img src="{{ asset('storage/' . $pemeliharaan->foto_perbaikan_path) }}"
                                                        alt="Foto Perbaikan" class="img-fluid rounded"
                                                        style="max-height: 200px;">
                                                </a>
                                            </div>
                                        @endif
                                    @else
                                        <p class="text-muted mb-0 fst-italic">Menunggu laporan disetujui & perbaikan
                                            dimulai.</p>
                                    @endif
                                </div>
                            </div>
                            {{-- ======================================================= --}}
                            {{--           TAMBAHAN BARU UNTUK BUKTI TUNTAS            --}}
                            {{-- ======================================================= --}}
                            @if ($pemeliharaan->status === 'Tuntas')
                                <hr>
                                <div class="d-flex">
                                    <div class="flex-shrink-0 text-center me-3" style="width: 2.5rem;">
                                        <i class="fas fa-handshake text-primary fs-3"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Telah Tuntas & Diserahkan</h6>
                                        <p class="text-muted mb-2">Proses ditutup pada
                                            {{ $pemeliharaan->tanggal_tuntas->isoFormat('dddd, DD MMMM YYYY - HH:mm') }}
                                        </p>
                                        @if ($pemeliharaan->foto_tuntas_path)
                                            <div class="p-3 card-item-detail">
                                                <p class="detail-label mb-1">Bukti Foto Serah Terima:</p>
                                                <a href="{{ asset('storage/' . $pemeliharaan->foto_tuntas_path) }}"
                                                    target="_blank">
                                                    <img src="{{ asset('storage/' . $pemeliharaan->foto_tuntas_path) }}"
                                                        class="img-fluid rounded" style="max-height: 200px;"
                                                        alt="Bukti Tuntas">
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            {{-- ======================================================= --}}

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Kita hanya perlu Select2 jika form persetujuan tampil --}}
    @if ($pemeliharaan->status === 'Diajukan' && Auth::user()->hasRole(\App\Models\User::ROLE_ADMIN))
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Cek jika form persetujuan ada di halaman
            if ($('#form-approval').length) {

                // Inisialisasi Select2 untuk PIC
                $('#id_operator_pengerjaan').select2({
                    theme: "bootstrap-5",
                    placeholder: 'Pilih Operator sebagai PIC',
                });

                // Handler untuk tombol SETUJUI
                $('#btn-setujui').on('click', function(e) {
                    e.preventDefault();
                    const form = $('#form-approval');

                    if (!$('#id_operator_pengerjaan').val()) {
                        Swal.fire('Peringatan',
                            'Harap pilih seorang Operator sebagai PIC sebelum menyetujui.', 'warning');
                        return;
                    }

                    Swal.fire({
                        title: 'Setujui Laporan?',
                        text: "Pastikan PIC yang ditugaskan sudah benar.",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Setujui!',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#28a745',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $('#catatan-approval').attr('name', 'catatan_persetujuan');

                            // ==========================================================
                            // KODE INI HANYA AKAN ADA JIKA USER ADALAH ADMIN
                            // ==========================================================
                            @if (Auth::user()->hasRole(\App\Models\User::ROLE_ADMIN))
                                var approveUrl =
                                    "{{ route('admin.pemeliharaan.approve', $pemeliharaan->id) }}";
                                form.attr('action', approveUrl).submit();
                            @endif
                        }
                    });
                });

                // Handler untuk tombol TOLAK
                $('#btn-tolak').on('click', function(e) {
                    e.preventDefault();
                    const form = $('#form-approval');
                    const catatan = $('#catatan-approval').val();

                    if (!catatan) {
                        Swal.fire('Peringatan', 'Harap isi kolom catatan sebagai alasan penolakan.',
                            'warning');
                        return;
                    }

                    Swal.fire({
                        title: 'Tolak Laporan Ini?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Tolak!',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#d33',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $('#catatan-approval').attr('name', 'catatan_persetujuan');

                            // ==========================================================
                            // KODE INI HANYA AKAN ADA JIKA USER ADALAH ADMIN
                            // ==========================================================
                            @if (Auth::user()->hasRole(\App\Models\User::ROLE_ADMIN))
                                var rejectUrl =
                                    "{{ route('admin.pemeliharaan.reject', $pemeliharaan->id) }}";
                                form.attr('action', rejectUrl).submit();
                            @endif
                        }
                    });
                });
            }

            // Konfirmasi untuk form lainnya
            $('#form-start-work').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                Swal.fire({
                    title: 'Mulai Perbaikan?',
                    text: "Status barang akan diubah menjadi 'Dalam Pemeliharaan'.",
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Mulai!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
            // TAMBAHAN BARU UNTUK KONFIRMASI TUNTAS
            $('#form-confirm-handover').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                Swal.fire({
                    title: 'Konfirmasi Serah Terima?',
                    text: "Pastikan Anda sudah mengunggah foto bukti yang benar.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Tuntaskan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

        });
    </script>
@endpush
