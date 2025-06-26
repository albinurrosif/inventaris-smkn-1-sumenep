@extends('layouts.app')

@section('title', 'Laporan Riwayat Mutasi Barang')

@php
    $rolePrefix = Auth::user()->getRolePrefix();
@endphp

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        .location-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        {{-- Page Title & Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">@yield('title')</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route($rolePrefix . 'dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route($rolePrefix . 'laporan.inventaris') }}">Laporan</a></li>
                            <li class="breadcrumb-item active">Mutasi Barang</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-retweet me-2"></i>Laporan Perpindahan Aset</h5>
            </div>
            <div class="card-body">
                {{-- FORM FILTER --}}
                <form method="GET" action="{{ route($rolePrefix . 'laporan.mutasi') }}" class="mb-4">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Pencarian</label>
                            <input type="text" name="search" id="search" class="form-control form-control-sm"
                                placeholder="Cari barang, kode, alasan..." value="{{ $filters['search'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label for="jenis_mutasi" class="form-label">Jenis Mutasi</label>
                            <select name="jenis_mutasi" id="jenis_mutasi" class="form-select form-select-sm select2-filter">
                                <option value="">-- Semua Jenis --</option>
                                @foreach ($jenisMutasiList as $jenis)
                                    <option value="{{ $jenis }}"
                                        {{ ($filters['jenis_mutasi'] ?? '') == $jenis ? 'selected' : '' }}>
                                        {{ $jenis }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="id_user_pencatat" class="form-label">Admin Pelaksana</label>
                            <select name="id_user_pencatat" id="id_user_pencatat"
                                class="form-select form-select-sm select2-filter">
                                <option value="">-- Semua Admin --</option>
                                @foreach ($adminList as $admin)
                                    <option value="{{ $admin->id }}"
                                        {{ ($filters['id_user_pencatat'] ?? '') == $admin->id ? 'selected' : '' }}>
                                        {{ $admin->username }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="tanggal_mulai" class="form-label">Dari Tanggal</label>
                            <input type="date" name="tanggal_mulai" id="tanggal_mulai"
                                class="form-control form-control-sm" value="{{ $filters['tanggal_mulai'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label for="tanggal_selesai" class="form-label">Sampai Tanggal</label>
                            <input type="date" name="tanggal_selesai" id="tanggal_selesai"
                                class="form-control form-control-sm" value="{{ $filters['tanggal_selesai'] ?? '' }}">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button class="btn btn-primary btn-sm w-100" type="submit"><i class="fas fa-filter"></i>
                                Filter</button>
                        </div>
                    </div>
                </form>

                {{-- Tombol Export --}}
                <div class="d-flex justify-content-end mb-3">
                    {{-- Menggunakan request()->query() untuk membawa filter saat export --}}
                    <a href="{{ route($rolePrefix . 'laporan.mutasi.excel', request()->query()) }}"
                        class="btn btn-success btn-sm me-2"><i class="fas fa-file-excel me-1"></i> Export Excel</a>
                    <a href="{{ route($rolePrefix . 'laporan.mutasi.pdf', request()->query()) }}"
                        class="btn btn-danger btn-sm"><i class="fas fa-file-pdf me-1"></i> Export PDF</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Unit Barang</th>
                                <th>Jenis Mutasi</th>
                                <th>Dari</th>
                                <th>Ke</th>
                                <th>Admin</th>
                                <th>Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($riwayatMutasi as $mutasi)
                                <tr>
                                    <td>{{ $mutasi->tanggal_mutasi->isoFormat('DD MMM YY, HH:mm') }}</td>
                                    <td>
                                        @if ($mutasi->barangQrCode)
                                            <span
                                                class="fw-bold">{{ optional($mutasi->barangQrCode->barang)->nama_barang ?? 'N/A' }}</span>
                                            <small
                                                class="text-muted d-block"><code>{{ $mutasi->barangQrCode->kode_inventaris_sekolah }}</code></small>
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
                                                <span
                                                    class="text-success">{{ $mutasi->ruanganTujuan->nama_ruangan }}</span>
                                            @elseif ($mutasi->pemegangTujuan)
                                                <i class="fas fa-user text-success" title="Pemegang Personal"></i>
                                                <span class="text-success">{{ $mutasi->pemegangTujuan->username }}</span>
                                            @else
                                                <span class="fst-italic">Tujuan Akhir</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ optional($mutasi->admin)->username }}</td>
                                    <td title="{{ $mutasi->alasan_pemindahan }}">
                                        {{ Str::limit($mutasi->alasan_pemindahan, 30) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">Tidak ada riwayat mutasi barang yang cocok
                                        dengan filter.</td>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2-filter').select2({
                theme: "bootstrap-5",
                width: '100%',
                placeholder: $(this).data('placeholder'),
                allowClear: true
            });
        });
    </script>
@endpush
