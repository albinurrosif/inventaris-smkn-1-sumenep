@extends('layouts.app')

@section('title', 'Riwayat Perubahan Status Unit Barang')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        #tabelLogStatus th,
        #tabelLogStatus td {
            vertical-align: middle;
            font-size: 0.8rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single {
            height: calc(1.5em + .5rem + 2px) !important;
            padding: .25rem .5rem !important;
            font-size: .875rem !important;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + .5rem + 2px) !important;
        }

        /* Custom styles for the modal */
        .modal-detail-badge {
            font-size: 0.85rem;
            padding: 0.35em 0.65em;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Riwayat Perubahan Status Unit Barang</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Histori Status Barang</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter Riwayat</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.barang-status.index') }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label for="id_barang_qr_code_filter" class="form-label">Unit Barang Spesifik</label>
                            <select name="id_barang_qr_code" id="id_barang_qr_code_filter"
                                class="form-select form-select-sm">
                                <option value="">-- Semua Unit --</option>
                                @foreach ($barangQrList as $item)
                                    <option value="{{ $item['id'] }}"
                                        {{ ($idBarangQrCode ?? '') == $item['id'] ? 'selected' : '' }}>
                                        {{ $item['display_name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="search_log_status" class="form-label">Pencarian Umum</label>
                            <input type="text" name="search" id="search_log_status" class="form-control form-control-sm"
                                placeholder="Nama barang, kode, no seri, alasan..." value="{{ $searchTerm ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label for="id_user_pencatat_filter" class="form-label">Dicatat Oleh</label>
                            <select name="id_user_pencatat" id="id_user_pencatat_filter" class="form-select form-select-sm">
                                <option value="">-- Semua User --</option>
                                @foreach ($usersPencatat as $user)
                                    <option value="{{ $user->id }}"
                                        {{ ($userIdPencatat ?? '') == $user->id ? 'selected' : '' }}>
                                        {{ $user->username }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="deskripsi_kejadian_filter" class="form-label">Kejadian</label>
                            <input type="text" name="deskripsi_kejadian" id="deskripsi_kejadian_filter"
                                class="form-control form-control-sm" placeholder="Cth: Mutasi, Kondisi Diubah"
                                value="{{ $kejadianFilter ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label for="tanggal_mulai_filter_status" class="form-label">Tgl Catat Mulai</label>
                            <input type="date" name="tanggal_mulai" id="tanggal_mulai_filter_status"
                                class="form-control form-control-sm" value="{{ $tanggalMulai ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label for="tanggal_selesai_filter_status" class="form-label">Tgl Catat Sampai</label>
                            <input type="date" name="tanggal_selesai" id="tanggal_selesai_filter_status"
                                class="form-control form-control-sm" value="{{ $tanggalSelesai ?? '' }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary btn-sm w-100" type="submit"><i class="fas fa-filter"></i>
                                Filter</button>
                        </div>
                        @if ($searchTerm || $userIdPencatat || $kejadianFilter || $tanggalMulai || $tanggalSelesai || $idBarangQrCode)
                            <div class="col-md-2 d-flex align-items-end">
                                <a href="{{ route('admin.barang-status.index') }}"
                                    class="btn btn-outline-secondary btn-sm w-100" title="Reset Filter">
                                    <i class="fas fa-times"></i> Reset
                                </a>
                            </div>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Daftar Riwayat Perubahan Status</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabelLogStatus" class="table table-sm table-bordered table-hover dt-responsive nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">No</th>
                                <th>Tgl. Catat</th>
                                <th>Kode Unit</th>
                                <th>Nama Barang</th>
                                <th>Kejadian</th>
                                <th>Kondisi (Sblm &#10140; Ssdh)</th>
                                <th>Status Unit (Sblm &#10140; Ssdh)</th>
                                <th>Lokasi (Sblm &#10140; Ssdh)</th>
                                <th>Dicatat Oleh</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logStatus as $index => $log)
                                <tr>
                                    <td class="text-center">{{ $logStatus->firstItem() + $index }}</td>
                                    <td data-sort="{{ $log->tanggal_pencatatan }}">
                                        {{ \Carbon\Carbon::parse($log->tanggal_pencatatan)->isoFormat('DD MMM YY, HH:mm') }}
                                    </td>
                                    <td>
                                        @if ($log->barangQrCode)
                                            <a href="{{ route('barang-qr-code.show', $log->id_barang_qr_code) }}"
                                                target="_blank" data-bs-toggle="tooltip" title="Lihat Detail Unit">
                                                <code>{{ $log->barangQrCode->kode_inventaris_sekolah ?? 'N/A' }}</code>
                                            </a>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>{{ optional(optional($log->barangQrCode)->barang)->nama_barang ?? 'N/A' }}</td>
                                    <td data-bs-toggle="tooltip" title="{{ $log->deskripsi_kejadian }}">
                                        {{ Str::limit($log->deskripsi_kejadian, 40) }}</td>
                                    <td>
                                        @if ($log->kondisi_sebelumnya)
                                            <span class="badge bg-light text-dark">{{ $log->kondisi_sebelumnya }}</span>
                                            &#10140;
                                        @endif
                                        <span class="badge bg-info">{{ $log->kondisi_sesudahnya }}</span>
                                    </td>
                                    <td>
                                        @if ($log->status_ketersediaan_sebelumnya)
                                            <span
                                                class="badge bg-light text-dark">{{ $log->status_ketersediaan_sebelumnya }}</span>
                                            &#10140;
                                        @endif
                                        <span class="badge bg-primary">{{ $log->status_ketersediaan_sesudahnya }}</span>
                                    </td>
                                    <td>
                                        <small class="d-block">
                                            @if ($log->id_ruangan_sebelumnya || $log->id_pemegang_personal_sebelumnya)
                                                Dari:
                                                @if ($log->ruanganSebelumnya)
                                                    <span
                                                        class="badge bg-secondary">{{ $log->ruanganSebelumnya->nama_ruangan }}</span>
                                                @elseif($log->pemegangPersonalSebelumnya)
                                                    <span class="badge bg-warning text-dark">P:
                                                        {{ $log->pemegangPersonalSebelumnya->username }}</span>
                                                @else
                                                    <span class="badge bg-light text-dark">-</span>
                                                @endif
                                            @else
                                                Dari:
                                                <span class="badge bg-light text-dark">-</span>
                                            @endif
                                        </small>

                                        <small class="d-block">
                                            Ke:
                                            @if ($log->ruanganSesudahnya)
                                                <span
                                                    class="badge bg-secondary">{{ $log->ruanganSesudahnya->nama_ruangan }}</span>
                                            @elseif($log->pemegangPersonalSesudahnya)
                                                <span class="badge bg-warning text-dark">P:
                                                    {{ $log->pemegangPersonalSesudahnya->username }}</span>
                                            @else
                                                <span class="badge bg-light text-dark">-</span>
                                            @endif
                                        </small>
                                    </td>
                                    <td>{{ optional($log->userPencatat)->username ?? '-' }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-info px-2 py-1" data-bs-toggle="modal"
                                            data-bs-target="#modalDetailRiwayat"
                                            onclick="loadDetailRiwayat({{ $log->id }})" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">
                                        @if ($searchTerm || $userIdPencatat || $kejadianFilter || $tanggalMulai || $tanggalSelesai || $idBarangQrCode)
                                            Tidak ada riwayat status yang cocok dengan kriteria filter Anda.
                                        @else
                                            Belum ada riwayat perubahan status barang.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this modal at the bottom of your content -->
    <div class="modal fade" id="modalDetailRiwayat" tabindex="-1" aria-labelledby="modalDetailRiwayatLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="modalDetailRiwayatLabel">Detail Riwayat Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalDetailContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat detail riwayat...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            if ($.fn.DataTable.isDataTable('#tabelLogStatus')) {
                $('#tabelLogStatus').DataTable().destroy();
            }
            if ($('#tabelLogStatus tbody tr').length > 0 && !$('#tabelLogStatus tbody tr td[colspan="10"]')
                .length) {
                $('#tabelLogStatus').DataTable({
                    responsive: true,
                    paging: true,
                    searching: false,
                    info: true,
                    ordering: true,
                    order: [
                        [1, 'desc']
                    ],
                    columnDefs: [{
                        targets: [0, 9], // No and Action columns
                        orderable: false,
                        searchable: false
                    }],
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                    },
                });
            }

            // Initialize Select2
            $('#id_barang_qr_code_filter, #id_user_pencatat_filter').select2({
                theme: "bootstrap-5",
                width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' :
                    'style',
                placeholder: $(this).data('placeholder') || "-- Pilih --",
                allowClear: true
            });

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Function to load detail via AJAX
        function loadDetailRiwayat(logId) {
            $('#modalDetailContent').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat detail riwayat...</p>
                </div>
            `);

            $.ajax({
                url: "{{ route('admin.barang-status.show', '') }}/" + logId,
                type: "GET",
                dataType: "html",
                success: function(response) {
                    $('#modalDetailContent').html(response);
                },
                error: function(xhr) {
                    $('#modalDetailContent').html(`
                        <div class="alert alert-danger">
                            Gagal memuat detail riwayat. Silakan coba lagi.
                        </div>
                    `);
                }
            });
        }
    </script>
@endpush
