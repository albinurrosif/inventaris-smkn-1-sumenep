@extends('layouts.app')

@section('title', 'Detail Laporan Pemeliharaan #' . $pemeliharaan->id)

@push('styles')
    <style>
        .detail-label {
            font-weight: 600;
            color: #555;
        }

        .badge-status {
            font-size: 0.9rem;
            padding: 0.4em 0.7em;
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
                                    href="{{ route($rolePrefix . 'pemeliharaan.index', request()->query()) }}">Pemeliharaan</a>
                            </li>
                            <li class="breadcrumb-item active">Detail Laporan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Kolom Kiri: Detail Unit Barang & Aksi Cepat --}}
            <div class="col-xl-4 col-lg-5">
                {{-- Tampilkan form persetujuan HANYA jika user adalah Admin & status masih Diajukan --}}
                {{-- Gunakan 'process' ability yang lebih spesifik untuk Admin --}}
                @can('process', $pemeliharaan)
                    @if ($pemeliharaan->status_pengajuan === 'Diajukan')
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0 text-white"><i class="fas fa-check-double me-2"></i>Form Persetujuan
                                    (Admin)</h5>
                            </div>
                            <div class="card-body">
                                {{-- Display Success Message --}}
                                @if (session('success'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        {{ session('success') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                    </div>
                                @endif

                                {{-- Display Error Message --}}
                                @if (session('error'))
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        {{ session('error') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                    </div>
                                @endif

                                {{-- Display Validation Errors --}}
                                @if ($errors->any())
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <strong>Terjadi Kesalahan Validasi:</strong>
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"
                                            aria-label="Close"></button>
                                    </div>
                                @endif

                                <form id="form-approval" action="{{ route('admin.pemeliharaan.update', $pemeliharaan->id) }}"
                                    method="POST">
                                    @csrf
                                    @method('PUT')

                                    <div class="mb-3">
                                        <label for="id_operator_pengerjaan" class="form-label">Tugaskan PIC (Penanggung
                                            Jawab)</label>
                                        <select name="id_operator_pengerjaan" id="id_operator_pengerjaan"
                                            class="form-select select2-pic @error('id_operator_pengerjaan') is-invalid @enderror">
                                            <option value="">-- Pilih Operator --</option>
                                            @foreach ($picList as $pic)
                                                <option value="{{ $pic->id }}"
                                                    {{ old('id_operator_pengerjaan') == $pic->id ? 'selected' : '' }}>
                                                    {{ $pic->username }} ({{ $pic->role }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('id_operator_pengerjaan')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">PIC wajib diisi jika laporan disetujui.</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="catatan_persetujuan" class="form-label">Catatan Persetujuan/Penolakan
                                            (Opsional)</label>
                                        <textarea name="catatan_persetujuan" id="catatan_persetujuan"
                                            class="form-control @error('catatan_persetujuan') is-invalid @enderror" rows="3"
                                            placeholder="Contoh: Setuju, segera proses.">{{ old('catatan_persetujuan') }}</textarea>
                                        @error('catatan_persetujuan')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="d-flex justify-content-end gap-2">
                                        <button type="button" id="btn-tolak" class="btn btn-danger">
                                            <i class="fas fa-times me-1"></i> Tolak Laporan
                                        </button>
                                        <button type="button" id="btn-setujui" class="btn btn-success">
                                            <i class="fas fa-check me-1"></i> Setujui & Tugaskan
                                        </button>
                                    </div>

                                    {{-- Hidden input for status --}}
                                    <input type="hidden" name="status_pengajuan" id="status_pengajuan_input">
                                    <input type="hidden" name="tanggal_persetujuan" value="{{ now()->format('Y-m-d') }}">
                                </form>
                            </div>
                        </div>
                    @endif
                @endcan

                <div class="card sticky-top" style="top: 80px;">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-box me-2"></i>Detail Unit Barang</h5>
                    </div>
                    <div class="card-body">
                        @if ($pemeliharaan->barangQrCode)
                            @php $barangQr = $pemeliharaan->barangQrCode; @endphp
                            <p class="mb-2"><span class="detail-label">Kode Inventaris Unit:</span>
                                <a href="{{ route($rolePrefix . 'barang-qr-code.show', $barangQr->id) }}" target="_blank">
                                    <code>{{ $barangQr->kode_inventaris_sekolah }}</code>
                                </a>
                            </p>
                            <p class="mb-2"><span class="detail-label">Nama Barang:</span>
                                {{ optional($barangQr->barang)->nama_barang ?? 'N/A' }}</p>
                            <p class="mb-2"><span class="detail-label">No. Seri:</span>
                                {{ $barangQr->no_seri_pabrik ?: '-' }}</p>
                            <p class="mb-2"><span class="detail-label">Lokasi/Pemegang:</span>
                                {{ optional($barangQr->ruangan)->nama_ruangan ?? (optional($barangQr->pemegangPersonal)->username ? 'Dipegang: ' . $barangQr->pemegangPersonal->username : 'Tidak Diketahui') }}
                            </p>
                            <p class="mb-2"><span class="detail-label">Kondisi:</span>
                                <span
                                    class="badge {{ \App\Models\BarangQrCode::getKondisiColor($barangQr->kondisi) }}">{{ $barangQr->kondisi }}</span>
                            </p>
                            <p class="mb-0"><span class="detail-label">Status:</span>
                                <span
                                    class="badge {{ \App\Models\BarangQrCode::getStatusColor($barangQr->status) }}">{{ $barangQr->status }}</span>
                            </p>
                        @else
                            <p class="text-danger">Data unit barang tidak ditemukan (mungkin terhapus permanen).</p>
                        @endif
                    </div>
                </div>

                {{-- Aksi hanya untuk yang punya hak akses dan jika laporan belum diarsipkan --}}
                @if (!$pemeliharaan->trashed())
                    <div class="card sticky-top" style="top: 400px;">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Aksi</h5>
                        </div>
                        <div class="card-body">
                            @can('update', $pemeliharaan)
                                <a href="{{ route($rolePrefix . 'pemeliharaan.edit', $pemeliharaan->id) }}"
                                    class="btn btn-warning w-100 mb-2">
                                    <i class="fas fa-edit me-2"></i>Edit / Proses Laporan
                                </a>
                            @endcan
                            @can('delete', $pemeliharaan)
                                <button type="button" class="btn btn-danger w-100 btn-delete-pemeliharaan-show"
                                    data-id="{{ $pemeliharaan->id }}"
                                    data-deskripsi="{{ Str::limit($pemeliharaan->catatan_pengajuan, 30) }}">
                                    <i class="fas fa-archive me-2"></i>Arsipkan Laporan Ini
                                </button>
                            @endcan
                        </div>
                    </div>
                @endif

                {{-- Info jika laporan sudah diarsipkan --}}
                @if ($pemeliharaan->trashed())
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title text-dark"><i class="fas fa-archive me-2 text-warning"></i>Laporan
                                Diarsipkan</h5>
                            <p>Laporan ini telah diarsipkan pada
                                {{ $pemeliharaan->deleted_at->isoFormat('DD MMMM YYYY') }}.</p>
                            @can('restore', $pemeliharaan)
                                <form action="{{ route('admin.pemeliharaan.restore', $pemeliharaan->id) }}" method="POST"
                                    class="d-inline form-restore-pemeliharaan-show">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100 btn-restore"><i
                                            class="fas fa-undo me-2"></i>Pulihkan Laporan</button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @endif
            </div>

            {{-- Kolom Kanan: Detail Laporan Pemeliharaan --}}
            <div class="col-xl-8 col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Rincian Laporan & Progres</h5>
                    </div>
                    <div class="card-body">
                        {{-- Bagian Pengajuan --}}
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3"><i class="fas fa-flag text-primary fs-4"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Pengajuan Laporan</h6>
                                <p class="text-muted mb-2">Diajukan oleh
                                    <strong>{{ optional($pemeliharaan->pengaju)->username ?? 'N/A' }}</strong> pada
                                    {{ $pemeliharaan->tanggal_pengajuan->isoFormat('dddd, DD MMMM YYYY') }}
                                </p>
                                <div class="p-3 card-item-detail">
                                    <p class="detail-label mb-1">Deskripsi Kerusakan/Keluhan:</p>
                                    <p class="mb-0">{{ $pemeliharaan->catatan_pengajuan ?: '-' }}</p>
                                </div>
                                @if ($pemeliharaan->foto_kerusakan_path)
                                    <div class="p-3 card-item-detail mt-2">
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
                        <hr>
                        {{-- Bagian Persetujuan --}}
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3"><i class="fas fa-user-check text-success fs-4"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Tahap Persetujuan</h6>
                                <p class="text-muted mb-2">Status: <span
                                        class="badge badge-status bg-{{ \App\Models\Pemeliharaan::statusColor($pemeliharaan->status_pengajuan) }}">{{ $pemeliharaan->status_pengajuan }}
                                    </span>
                                </p>
                                @if ($pemeliharaan->id_user_penyetuju)
                                    <p class="text-muted mb-2">Diproses oleh
                                        <strong>{{ optional($pemeliharaan->penyetuju)->username ?? 'N/A' }}</strong> pada
                                        {{ optional($pemeliharaan->tanggal_persetujuan)->isoFormat('DD MMM YYYY') }}
                                    </p>
                                @endif
                                @if ($pemeliharaan->catatan_persetujuan)
                                    <div class="p-3 card-item-detail">
                                        <p class="detail-label mb-1">Catatan Persetujuan/Penolakan:</p>
                                        <p class="mb-0">{{ $pemeliharaan->catatan_persetujuan }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <hr>
                        {{-- Bagian Pengerjaan --}}
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3"><i class="fas fa-cogs text-warning fs-4"></i></div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Tahap Pengerjaan</h6>
                                <p class="text-muted mb-2">Status: <span
                                        class="badge badge-status bg-{{ \App\Models\Pemeliharaan::statusColor($pemeliharaan->status_pengerjaan) }}">{{ $pemeliharaan->status_pengerjaan }}</span>
                                </p>
                                <p class="text-muted mb-2">Penanggung Jawab (PIC):
                                    <strong>{{ optional($pemeliharaan->operatorPengerjaan)->username ?? 'Belum Ditentukan' }}</strong>
                                </p>
                                @if ($pemeliharaan->deskripsi_pekerjaan)
                                    <div class="p-3 card-item-detail mt-2">
                                        <p class="detail-label mb-1">Pekerjaan yang Dilakukan:</p>
                                        <p class="mb-0">{{ $pemeliharaan->deskripsi_pekerjaan }}</p>
                                    </div>
                                @endif
                                @if ($pemeliharaan->hasil_pemeliharaan)
                                    <div class="p-3 card-item-detail mt-2">
                                        <p class="detail-label mb-1">Hasil Akhir:</p>
                                        <p class="mb-0">{{ $pemeliharaan->hasil_pemeliharaan }}</p>
                                        @if ($pemeliharaan->kondisi_barang_setelah_pemeliharaan)
                                            <p class="mb-0 mt-2">Kondisi barang setelah perbaikan: <span
                                                    class="badge {{ \App\Models\BarangQrCode::getKondisiColor($pemeliharaan->kondisi_barang_setelah_pemeliharaan) }}">{{ $pemeliharaan->kondisi_barang_setelah_pemeliharaan }}</span>
                                            </p>
                                        @endif
                                    </div>
                                @endif
                                @if ($pemeliharaan->biaya > 0)
                                    <p class="mt-2 mb-0"><span class="detail-label">Biaya Perbaikan:</span> Rp
                                        {{ number_format($pemeliharaan->biaya, 0, ',', '.') }}</p>
                                @endif
                                @if ($pemeliharaan->foto_perbaikan_path)
                                    <div class="p-3 card-item-detail mt-2">
                                        <p class="detail-label mb-1 mt-3">Foto Bukti Perbaikan:</p>
                                        <a href="{{ asset('storage/' . $pemeliharaan->foto_perbaikan_path) }}"
                                            target="_blank">
                                            <img src="{{ asset('storage/' . $pemeliharaan->foto_perbaikan_path) }}"
                                                alt="Foto Perbaikan" class="img-fluid rounded"
                                                style="max-height: 200px;">
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="formDeletePemeliharaanShow" method="POST" style="display: none;">@csrf @method('DELETE')</form>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rolePrefix = "{{ $rolePrefix }}";
            const deleteForm = $('#formDeletePemeliharaanShow');

            // Initialize Select2
            $('.select2-pic').select2({
                theme: "bootstrap-5",
                placeholder: 'Pilih Operator sebagai PIC',
            });

            // Handler untuk tombol SETUJUI
            $('#btn-setujui').on('click', function(e) {
                e.preventDefault();

                // Cek apakah PIC sudah dipilih
                if (!$('#id_operator_pengerjaan').val()) {
                    Swal.fire({
                        title: 'Peringatan!',
                        text: 'Harap pilih seorang Operator sebagai PIC sebelum menyetujui.',
                        icon: 'warning',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Setujui Laporan?',
                    text: "Anda akan menyetujui laporan ini dan menugaskannya. Aksi ini akan mengubah status barang.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Setujui!',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#status_pengajuan_input').val('Disetujui');
                        $('#form-approval').submit();
                    }
                });
            });

            // Handler untuk tombol TOLAK
            $('#btn-tolak').on('click', function(e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Tolak Laporan Ini?',
                    text: "Anda yakin ingin menolak laporan pemeliharaan ini?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Tolak!',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#status_pengajuan_input').val('Ditolak');
                        $('#form-approval').submit();
                    }
                });
            });

            // Konfirmasi Arsipkan
            $(document).on('click', '.btn-delete-pemeliharaan-show', function() {
                const pemeliharaanId = $(this).data('id');
                const deskripsi = $(this).data('deskripsi');

                let actionUrl = `{{ route('admin.pemeliharaan.destroy', ['pemeliharaan' => ':id']) }}`;
                actionUrl = actionUrl.replace(':id', pemeliharaanId);

                Swal.fire({
                    title: 'Konfirmasi Arsipkan Laporan',
                    html: `Anda yakin ingin mengarsipkan laporan untuk: <strong>"${deskripsi}"</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Arsipkan!',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteForm.attr('action', actionUrl).submit();
                    }
                });
            });

            // Konfirmasi Pulihkan
            $('.form-restore-pemeliharaan-show').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                Swal.fire({
                    title: 'Konfirmasi Pulihkan Laporan',
                    text: 'Anda yakin ingin memulihkan laporan pemeliharaan ini?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Pulihkan!',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
