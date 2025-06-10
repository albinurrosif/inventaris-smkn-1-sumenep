@extends('layouts.app') {{-- Sesuaikan dengan path layout admin Anda --}}

@section('title', 'Log Aktivitas Sistem')

@push('styles')
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <style>
        .data-json {
            /* Ini tidak terpakai di tabel utama, mungkin untuk detail? */
            max-height: 100px;
            overflow-y: auto;
            background-color: #f8f9fa;
            padding: 5px;
            border-radius: 4px;
            font-size: 0.8em;
        }

        <style>.table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            width: 100%;
            max-width: 100%;
            margin-bottom: 1rem;
            background-color: transparent;
        }

        .table th,
        .table td {
            /* Hapus white-space: nowrap */
            vertical-align: middle;
            word-break: break-word;
            /* Memungkinkan pemotongan kata */
        }

        /* Untuk sel tertentu yang perlu truncate text */
        .truncate-text {
            max-width: 200px;
            /* Sesuaikan sesuai kebutuhan */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Responsive untuk layar kecil */
        @media (max-width: 768px) {
            .table-responsive {
                width: 100%;
                margin-bottom: 15px;
                overflow-y: hidden;
                -ms-overflow-style: -ms-autohiding-scrollbar;
                border: 1px solid #ddd;
            }
        }

        #dataTableLog_wrapper .row:first-child {
            margin-bottom: 1rem;
            /* Beri jarak antara search/length DataTables dan tabel */
        }

        #dataTableLog_wrapper .dataTables_filter {
            text-align: right;
            /* Pindahkan search DataTables ke kanan jika mau */
        }

        #dataTableLog_wrapper .dataTables_paginate .pagination {
            justify-content: flex-end;
            /* Pindahkan paginasi DataTables ke kanan */
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">@yield('title')</h1>

            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route(Auth::user()->getRolePrefix() . 'dashboard') }}">Dashboard</a>
                </li>
                <li class="breadcrumb-item active">Log Aktivitas</li>
            </ol>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Filter Log Aktivitas</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.log-aktivitas.index') }}">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <input type="text" name="search" class="form-control"
                                placeholder="Cari aktivitas, deskripsi, IP..." value="{{ $searchTerm ?? '' }}">
                        </div>
                        <div class="col-md-2 mb-3">
                            <select name="id_user" class="form-control">
                                <option value="">Semua Pengguna</option>
                                @foreach ($userList as $user)
                                    <option value="{{ $user->id }}"
                                        {{ isset($userIdFilter) && $userIdFilter == $user->id ? 'selected' : '' }}>
                                        {{ $user->username }} ({{ $user->role }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <select name="model_terkait" class="form-control">
                                <option value="">Semua Model</option>
                                @foreach ($modelList as $displayName => $className)
                                    <option value="{{ $className }}"
                                        {{ isset($modelTerkaitFilter) && $modelTerkaitFilter == $className ? 'selected' : '' }}>
                                        {{ $displayName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <input type="date" name="tanggal_mulai" class="form-control"
                                value="{{ $tanggalMulai ?? '' }}" title="Tanggal Mulai">
                        </div>
                        <div class="col-md-2 mb-3">
                            <input type="date" name="tanggal_selesai" class="form-control"
                                value="{{ $tanggalSelesai ?? '' }}" title="Tanggal Selesai">
                        </div>
                        <div class="col-md-1 mb-3 d-grid">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-1 offset-md-11 mb-3 d-grid">
                            <a href="{{ route('admin.log-aktivitas.index') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Daftar Log</h6>
            </div>
            <div class="card-body">
                @if ($logAktivitasList->isEmpty())
                    <div class="alert alert-info">Tidak ada data log aktivitas yang ditemukan dengan filter saat ini.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover nowrap w-100" id="dataTableLog"
                            width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th>No</th>
                                    <th>Waktu</th>
                                    <th>Pengguna</th>
                                    <th>Aktivitas</th>
                                    <th>Deskripsi Tambahan</th>
                                    <th>Model Terkait</th>
                                    <th>ID Model</th>
                                    <th>IP Address</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($logAktivitasList as $index => $log)
                                    <tr>
                                        <td>{{ $logAktivitasList->firstItem() + $index }}</td>
                                        <td>{{ $log->created_at->isoFormat('DD MMM YYYY, HH:mm:ss') }}</td>
                                        <td>
                                            @if ($log->user)
                                                {{ $log->user->username }} ({{ $log->user->role }})
                                            @else
                                                Sistem/Tidak Diketahui
                                            @endif
                                        </td>
                                        <td>{{ $log->aktivitas }}</td>
                                        <td>
                                            @php
                                                $deskripsiLengkap = '';
                                                if (property_exists($log, 'deskripsi') && !empty($log->deskripsi)) {
                                                    $deskripsiLengkap = $log->deskripsi;
                                                } else {
                                                    $namaEntitas = '';
                                                    $dataUntukCek = $log->data_baru ?? $log->data_lama;
                                                    if (is_array($dataUntukCek)) {
                                                        if (isset($dataUntukCek['nama_barang'])) {
                                                            $namaEntitas = $dataUntukCek['nama_barang'];
                                                        } elseif (isset($dataUntukCek['nama_kategori'])) {
                                                            $namaEntitas = $dataUntukCek['nama_kategori'];
                                                        } elseif (isset($dataUntukCek['name'])) {
                                                            $namaEntitas = $dataUntukCek['name'];
                                                        } elseif (isset($dataUntukCek['username'])) {
                                                            $namaEntitas = $dataUntukCek['username'];
                                                        } elseif (isset($dataUntukCek['judul'])) {
                                                            $namaEntitas = $dataUntukCek['judul'];
                                                        }
                                                        // Tambahkan field umum lainnya yang mungkin menyimpan nama/identifier utama
                                                    }

                                                    $deskripsiLengkap = Str::ucfirst($log->aktivitas);
                                                    if ($log->model_terkait) {
                                                        $deskripsiLengkap .= ' ' . class_basename($log->model_terkait);
                                                        if ($log->id_model_terkait) {
                                                            $deskripsiLengkap .= ' #' . $log->id_model_terkait;
                                                        }
                                                    }
                                                    if (!empty($namaEntitas)) {
                                                        $deskripsiLengkap .=
                                                            ' ("' . Str::limit($namaEntitas, 50) . '")';
                                                    }
                                                }
                                            @endphp
                                            {{ $deskripsiLengkap ?: '-' }}
                                        </td>
                                        <td>{{ $log->model_terkait ? class_basename($log->model_terkait) : '-' }}</td>
                                        <td>{{ $log->id_model_terkait ?: '-' }}</td>
                                        <td>{{ $log->ip_address }}</td>
                                        <td>
                                            <a href="{{ route('admin.log-aktivitas.show', $log->id) }}"
                                                class="btn btn-sm btn-info" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- DataTables JS --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> {{-- Pastikan jQuery sudah ada --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inisialisasi DataTables
            // Perhatikan: Paginasi, searching, dan ordering DataTables di sini
            // hanya akan berlaku untuk data yang ditampilkan di halaman saat ini oleh Laravel.
            // Untuk fungsionalitas penuh DataTables pada seluruh dataset,
            // Anda memerlukan implementasi server-side processing.
            $('#dataTableLog').DataTable({
                "paging": true, // Aktifkan paginasi DataTables (untuk data di halaman ini)
                "lengthChange": true, // Tampilkan opsi ubah jumlah entri per halaman
                "searching": false, // Aktifkan fitur pencarian DataTables (untuk data di halaman ini)
                "ordering": true, // Aktifkan pengurutan kolom
                "info": true, // Tampilkan informasi jumlah entri
                "responsive": true, // Aktifkan responsivitas DataTables
                "language": { // Opsi untuk melokalisasi DataTables ke Bahasa Indonesia
                    "sEmptyTable": "Tidak ada data yang tersedia pada tabel ini",
                    "sProcessing": "Sedang memproses...",
                    "sLengthMenu": "Tampilkan _MENU_ entri",
                    "sZeroRecords": "Tidak ditemukan data yang sesuai",
                    "sInfo": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                    "sInfoEmpty": "Menampilkan 0 sampai 0 dari 0 entri",
                    "sInfoFiltered": "(disaring dari _MAX_ entri keseluruhan)",
                    "sInfoPostFix": "",
                    "sSearch": "Cari:",
                    "sUrl": "",
                    "oPaginate": {
                        "sFirst": "Pertama",
                        "sPrevious": "Sebelumnya",
                        "sNext": "Selanjutnya",
                        "sLast": "Terakhir"
                    }
                },
                "columnDefs": [{
                    "targets": [0], // Kolom No
                    "orderable": false // Nonaktifkan pengurutan untuk kolom No
                }]

            });
        });
    </script>
@endpush
