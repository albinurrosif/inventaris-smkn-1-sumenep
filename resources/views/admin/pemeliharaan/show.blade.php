{{-- File: resources/views/admin/pemeliharaan/show.blade.php --}}
@extends('layouts.app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('title', 'Detail Laporan Pemeliharaan')

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

        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
            padding-left: 25px;
            border-left: 2px solid #e9ecef;
        }

        .timeline-item::before {
            content: "";
            position: absolute;
            left: -7px;
            /* Sesuaikan agar pas dengan garis */
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #0d6efd;
            /* Warna titik timeline */
            border: 2px solid #fff;
        }

        .timeline-item:last-child {
            border-left: 2px solid transparent;
            /* Sembunyikan garis untuk item terakhir */
        }

        .card-item-detail {
            background-color: #f8f9fa;
            border-radius: .25rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Detail Pemeliharaan: ID #{{ $pemeliharaan->id }}</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route('admin.pemeliharaan.index', request()->query()) }}">Pemeliharaan</a></li>
                            <li class="breadcrumb-item active">Detail Laporan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            {{-- Kolom Detail Unit Barang --}}
            <div class="col-xl-4 col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Detail Unit Barang</h5>
                    </div>
                    <div class="card-body">
                        @if ($pemeliharaan->barangQrCode)
                            @php $barangQr = $pemeliharaan->barangQrCode; @endphp
                            <p><span class="detail-label">Kode Unit:</span>
                                <a href="{{ route('barang-qr-code.show', $barangQr->id) }}"
                                    target="_blank"><code>{{ $barangQr->kode_inventaris_sekolah }}</code></a>
                            </p>
                            <p><span class="detail-label">Nama Barang:</span> {{ $barangQr->barang->nama_barang ?? 'N/A' }}
                            </p>
                            <p><span class="detail-label">Merk/Model:</span> {{ $barangQr->barang->merk_model ?? '-' }}</p>
                            <p><span class="detail-label">No. Seri:</span> {{ $barangQr->no_seri_pabrik ?: '-' }}</p>
                            <p><span class="detail-label">Lokasi Terkini:</span>
                                {{ $barangQr->ruangan->nama_ruangan ?? ($barangQr->id_pemegang_personal ? 'Pemegang: ' . optional($barangQr->pemegangPersonal)->username : 'Tidak Diketahui') }}
                            </p>
                            <p><span class="detail-label">Kondisi Terkini:</span> <span
                                    class="badge bg-{{ App\Models\BarangQrCode::getKondisiColor($barangQr->kondisi) }}">{{ $barangQr->kondisi }}</span>
                            </p>
                            <p><span class="detail-label">Status Terkini:</span> <span
                                    class="badge bg-{{ App\Models\BarangQrCode::getStatusColor($barangQr->status) }}">{{ $barangQr->status }}</span>
                            </p>
                        @else
                            <p class="text-danger">Data unit barang tidak ditemukan (mungkin terhapus permanen).</p>
                        @endif
                    </div>
                </div>

                @if (Auth::user()->hasRole(App\Models\User::ROLE_ADMIN) && !$pemeliharaan->trashed())
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Aksi Cepat</h5>
                        </div>
                        <div class="card-body">
                            @can('update', $pemeliharaan)
                                <a href="{{ route('admin.pemeliharaan.edit', $pemeliharaan->id) }}"
                                    class="btn btn-warning w-100 mb-2"><i data-feather="edit" class="me-2"></i>Edit Laporan
                                    Pemeliharaan</a>
                            @endcan
                            @can('delete', $pemeliharaan)
                                <button type="button" class="btn btn-danger w-100 btn-delete-pemeliharaan-show"
                                    data-id="{{ $pemeliharaan->id }}"
                                    data-deskripsi="{{ Str::limit($pemeliharaan->catatan_pengajuan, 30) }}">
                                    <i data-feather="archive" class="me-2"></i>Arsipkan Laporan Ini
                                </button>
                            @endcan
                        </div>
                    </div>
                @endif
                @if ($pemeliharaan->trashed() && Auth::user()->hasRole(App\Models\User::ROLE_ADMIN))
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0 text-dark"><i data-feather="alert-triangle"
                                    class="me-2 text-warning"></i>Laporan Diarsipkan</h5>
                        </div>
                        <div class="card-body">
                            <p>Laporan pemeliharaan ini telah diarsipkan pada
                                {{ $pemeliharaan->deleted_at->isoFormat('DD MMMM YYYY, HH:mm') }}.</p>
                            @can('restore', $pemeliharaan)
                                <form action="{{ route('admin.pemeliharaan.restore', $pemeliharaan->id) }}" method="POST"
                                    class="d-inline form-restore-pemeliharaan-show">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100 btn-restore"><i
                                            data-feather="rotate-ccw" class="me-2"></i>Pulihkan Laporan</button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @endif

            </div>

            {{-- Kolom Detail Pemeliharaan --}}
            <div class="col-xl-8 col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Detail Laporan Pemeliharaan</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><span class="detail-label">Tanggal Pengajuan:</span>
                                    {{ $pemeliharaan->tanggal_pengajuan ? $pemeliharaan->tanggal_pengajuan->isoFormat('DD MMMM YYYY, HH:mm') : '-' }}
                                </p>
                                <p><span class="detail-label">Pelapor:</span>
                                    {{ $pemeliharaan->pengaju->username ?? 'N/A' }}</p>
                                <p><span class="detail-label">Prioritas:</span> <span
                                        class="badge fs-07rem bg-{{ App\Models\Pemeliharaan::statusColor($pemeliharaan->prioritas) }}">{{ Str::ucfirst($pemeliharaan->prioritas) }}</span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><span class="detail-label">Status Pengajuan:</span> <span
                                        class="badge badge-status bg-{{ App\Models\Pemeliharaan::statusColor($pemeliharaan->status_pengajuan) }}">{{ $pemeliharaan->status_pengajuan }}</span>
                                </p>
                                @if ($pemeliharaan->id_user_penyetuju)
                                    <p><span class="detail-label">Diproses (Setuju/Tolak) Oleh:</span>
                                        {{ $pemeliharaan->penyetuju->username ?? 'N/A' }}</p>
                                    <p><span class="detail-label">Tanggal Persetujuan/Penolakan:</span>
                                        {{ $pemeliharaan->tanggal_persetujuan ? $pemeliharaan->tanggal_persetujuan->isoFormat('DD MMMM YYYY, HH:mm') : '-' }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="mb-3 p-3 card-item-detail">
                            <p class="detail-label mb-1">Deskripsi Kerusakan/Keluhan (dari Pelapor):</p>
                            <p class="mb-0">{{ $pemeliharaan->catatan_pengajuan ?: '-' }}</p>
                        </div>

                        @if ($pemeliharaan->catatan_persetujuan)
                            <div class="mb-3 p-3 card-item-detail">
                                <p class="detail-label mb-1">Catatan Persetujuan/Penolakan (dari Admin/Penyetuju):</p>
                                <p class="mb-0">{{ $pemeliharaan->catatan_persetujuan }}</p>
                            </div>
                        @endif

                        <hr>
                        <h6 class="mb-3">Informasi Pengerjaan</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><span class="detail-label">PIC Pengerjaan:</span>
                                    {{ $pemeliharaan->operatorPengerjaan->username ?? '-' }}</p>
                                <p><span class="detail-label">Status Pengerjaan:</span> <span
                                        class="badge badge-status bg-{{ App\Models\Pemeliharaan::statusColor($pemeliharaan->status_pengerjaan) }}">{{ $pemeliharaan->status_pengerjaan }}</span>
                                </p>
                                <p><span class="detail-label">Biaya (Rp):</span>
                                    {{ $pemeliharaan->biaya ? number_format($pemeliharaan->biaya, 0, ',', '.') : '-' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><span class="detail-label">Tanggal Mulai Pengerjaan:</span>
                                    {{ $pemeliharaan->tanggal_mulai_pengerjaan ? $pemeliharaan->tanggal_mulai_pengerjaan->isoFormat('DD MMMM YYYY, HH:mm') : '-' }}
                                </p>
                                <p><span class="detail-label">Tanggal Selesai Pengerjaan:</span>
                                    {{ $pemeliharaan->tanggal_selesai_pengerjaan ? $pemeliharaan->tanggal_selesai_pengerjaan->isoFormat('DD MMMM YYYY, HH:mm') : '-' }}
                                </p>
                            </div>
                        </div>
                        @if ($pemeliharaan->deskripsi_pekerjaan)
                            <div class="mb-3 p-3 card-item-detail">
                                <p class="detail-label mb-1">Deskripsi Pekerjaan yang Dilakukan:</p>
                                <p class="mb-0">{{ $pemeliharaan->deskripsi_pekerjaan }}</p>
                            </div>
                        @endif
                        @if ($pemeliharaan->hasil_pemeliharaan)
                            <div class="mb-3 p-3 card-item-detail">
                                <p class="detail-label mb-1">Hasil Pemeliharaan:</p>
                                <p class="mb-0">{{ $pemeliharaan->hasil_pemeliharaan }}</p>
                            </div>
                        @endif
                        @if ($pemeliharaan->kondisi_barang_setelah_pemeliharaan)
                            <div class="mb-3 p-3 card-item-detail">
                                <p class="detail-label mb-1">Kondisi Barang Setelah Pemeliharaan:</p>
                                <p class="mb-0"><span
                                        class="badge bg-{{ App\Models\BarangQrCode::getKondisiColor($pemeliharaan->kondisi_barang_setelah_pemeliharaan) }}">{{ $pemeliharaan->kondisi_barang_setelah_pemeliharaan }}</span>
                                </p>
                            </div>
                        @endif
                        @if ($pemeliharaan->catatan_pengerjaan)
                            <div class="mb-3 p-3 card-item-detail">
                                <p class="detail-label mb-1">Catatan Pengerjaan Tambahan:</p>
                                <p class="mb-0">{{ $pemeliharaan->catatan_pengerjaan }}</p>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
    <form id="formDeletePemeliharaanShow" method="POST" style="display: none;">@csrf @method('DELETE')</form>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi Tooltip Bootstrap
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })

            // Handle Delete Confirmation
            $(document).on('click', '.btn-delete-pemeliharaan-show', function() {
                const pemeliharaanId = $(this).data('id');
                const deskripsi = $(this).data('deskripsi');
                const formDelete = $('#formDeletePemeliharaanShow');

                // Pastikan route name sudah benar dan parameter sesuai
                let actionUrl = `{{ route('admin.pemeliharaan.destroy', ['pemeliharaan' => ':id']) }}`;
                actionUrl = actionUrl.replace(':id', pemeliharaanId);

                Swal.fire({
                    title: 'Konfirmasi Arsipkan Laporan',
                    html: `Anda yakin ingin mengarsipkan laporan pemeliharaan untuk: <strong>"${deskripsi}"</strong> (ID: ${pemeliharaanId})?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Arsipkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (formDelete.length) {
                            formDelete.attr('action', actionUrl).submit();
                        }
                    }
                });
            });

            // Handle Restore Confirmation
            $(document).on('submit', '.form-restore-pemeliharaan-show', function(e) {
                e.preventDefault();
                const form = this;
                const pemeliharaanId = $(form).attr('action').split('/').pop(); // Ekstrak ID dari action
                Swal.fire({
                    title: 'Konfirmasi Pulihkan Laporan',
                    html: `Anda yakin ingin memulihkan laporan pemeliharaan ID: <strong>#${pemeliharaanId}</strong>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Pulihkan!',
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
