@extends('layouts.app')

@section('title', 'Riwayat Mutasi Barang')

@php
    $rolePrefix = Auth::user()->getRolePrefix();
@endphp

@push('styles')
    {{-- Menambahkan semua style yang dibutuhkan --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        #tabelMutasi th, #tabelMutasi td {
            vertical-align: middle;
            font-size: 0.85rem;
        }
        .table-hover tbody tr:hover {
            cursor: pointer;
            background-color: #f1f5f7;
        }
        .location-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .select2-container .select2-selection--single { height: 35px; }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { line-height: 1.5; }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow { height: 34px; }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        {{-- Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">@yield('title')</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route($rolePrefix . 'dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Riwayat Mutasi</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Log Perpindahan Aset</h5>
            </div>
            <div class="card-body">
                {{-- FORM FILTER BARU --}}
                <form method="GET" action="{{ route($rolePrefix . 'mutasi-barang.index') }}" class="mb-4">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari barang, kode, alasan..." value="{{ $filters['search'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <select name="jenis_mutasi" class="form-select form-select-sm select2-filter">
                                <option value="">-- Semua Jenis --</option>
                                @foreach($jenisMutasiList as $jenis)
                                <option value="{{ $jenis }}" {{ ($filters['jenis_mutasi'] ?? '') == $jenis ? 'selected' : '' }}>{{ $jenis }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="id_user_pencatat" class="form-select form-select-sm select2-filter">
                                <option value="">-- Semua Admin --</option>
                                @foreach($adminList as $admin)
                                <option value="{{ $admin->id }}" {{ ($filters['id_user_pencatat'] ?? '') == $admin->id ? 'selected' : '' }}>{{ $admin->username }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="{{ $filters['tanggal_mulai'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="tanggal_selesai" class="form-control form-control-sm" value="{{ $filters['tanggal_selesai'] ?? '' }}">
                        </div>
                        <div class="col-md-1 d-flex gap-1">
                            <button class="btn btn-primary btn-sm w-100" type="submit"><i class="fas fa-filter"></i></button>
                            <a href="{{ route($rolePrefix . 'mutasi-barang.index') }}" class="btn btn-outline-secondary btn-sm w-100" title="Reset Filter"><i class="fas fa-times"></i></a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table id="tabelMutasi" class="table table-hover align-middle dt-responsive nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Unit Barang</th>
                                <th>Jenis Mutasi</th>
                                <th>Dari</th>
                                <th>Ke</th>
                                <th>Admin Pelaksana</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($riwayatMutasi as $mutasi)
                                <tr data-href="{{ route($rolePrefix . 'mutasi-barang.show', $mutasi->id) }}">
                                    <td data-sort="{{ $mutasi->tanggal_mutasi->timestamp }}">{{ $mutasi->tanggal_mutasi->isoFormat('DD MMM YY, HH:mm') }}</td>
                                    <td>
                                        @if($mutasi->barangQrCode)
                                        <a href="{{ route($rolePrefix . 'barang-qr-code.show', $mutasi->barangQrCode->id) }}" class="fw-bold" onclick="event.stopPropagation()" title="Lihat detail unit barang">
                                            {{ optional($mutasi->barangQrCode->barang)->nama_barang ?? 'N/A' }}
                                        </a>
                                        <small class="text-muted d-block"><code>{{ $mutasi->barangQrCode->kode_inventaris_sekolah }}</code></small>
                                        @else
                                            <span class="text-danger fst-italic">Aset Dihapus</span>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-light text-dark">{{ $mutasi->jenis_mutasi }}</span></td>
                                    <td>
                                        <div class="location-label">
                                            @if ($mutasi->ruanganAsal)
                                                <i class="fas fa-warehouse text-muted" title="Ruangan"></i>
                                                <span>{{ $mutasi->ruanganAsal->nama_ruangan }}</span>
                                            @elseif ($mutasi->pemegangAsal)
                                                <i class="fas fa-user text-muted" title="Pemegang Personal"></i>
                                                <span>{{ $mutasi->pemegangAsal->username }}</span>
                                            @else
                                                <span class="fst-italic">Sumber Awal</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="location-label">
                                            @if ($mutasi->ruanganTujuan)
                                                <i class="fas fa-warehouse text-success" title="Ruangan"></i>
                                                <span class="text-success fw-bold">{{ $mutasi->ruanganTujuan->nama_ruangan }}</span>
                                            @elseif ($mutasi->pemegangTujuan)
                                                <i class="fas fa-user text-success" title="Pemegang Personal"></i>
                                                <span class="text-success fw-bold">{{ $mutasi->pemegangTujuan->username }}</span>
                                            @else
                                                <span>N/A</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ optional($mutasi->admin)->username }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">Tidak ada riwayat mutasi barang yang cocok dengan filter.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    {{ $riwayatMutasi->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inisialisasi DataTables
            if ($.fn.DataTable.isDataTable('#tabelMutasi')) {
                $('#tabelMutasi').DataTable().destroy();
            }
            $('#tabelMutasi').DataTable({
                responsive: true,
                paging: false,
                searching: false,
                info: false,
                order: [[0, 'desc']], // Default sort by Tanggal
                columnDefs: [
                    { targets: [1, 2, 3, 4, 5], orderable: false } 
                ],
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json' }
            });

            // Inisialisasi Select2
            $('.select2-filter').select2({
                theme: "bootstrap-5",
                width: '100%',
                placeholder: $(this).data('placeholder'),
                allowClear: true
            });

            // Fungsionalitas baris bisa di-klik
            $('#tabelMutasi tbody').on('click', 'tr', function() {
                if ($(this).data('href')) {
                    window.location.href = $(this).data('href');
                }
            });
        });
    </script>
@endpush