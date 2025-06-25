@extends('layouts.app') {{-- Sesuaikan dengan nama layout utama Anda --}}

@section('title', 'Hasil Stok Opname - ' . $stokOpname->ruangan->nama_ruangan)

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="page-title-box">
                    <div class="float-end">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route($rolePrefix . 'stok-opname.index') }}">Stok
                                    Opname</a></li>
                            <li class="breadcrumb-item active">Hasil Stok Opname</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Hasil Stok Opname</h4>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Detail Sesi Stok Opname</h4>
                <p class="text-muted mb-0">Ringkasan dari sesi pemeriksaan fisik barang yang telah selesai.</p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Ruangan</label>
                            <p>{{ $stokOpname->ruangan->nama_ruangan }} ({{ $stokOpname->ruangan->kode_ruangan }})</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tanggal Jadwal Opname</label>
                            <p>{{ \Carbon\Carbon::parse($stokOpname->tanggal_opname)->isoFormat('dddd, D MMMM YYYY') }}</p>
                        </div>
                        @if ($stokOpname->tanggal_mulai_pengerjaan)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Waktu Mulai Pengerjaan</label>
                                <p>{{ \Carbon\Carbon::parse($stokOpname->tanggal_mulai_pengerjaan)->isoFormat('dddd, D MMMM YYYY - HH:mm:ss') }}
                                </p>
                            </div>
                        @endif
                        @if ($stokOpname->tanggal_selesai_pengerjaan)
                            <div class="mb-3">
                                <label class="form-label fw-bold">Waktu Selesai Pengerjaan</label>
                                <p>{{ \Carbon\Carbon::parse($stokOpname->tanggal_selesai_pengerjaan)->isoFormat('dddd, D MMMM YYYY - HH:mm:ss') }}
                                </p>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Operator Pelaksana</label>
                            <p>{{ $stokOpname->operator->username ?? 'N/A' }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <p><span
                                    class="badge {{ \App\Models\StokOpname::statusColor($stokOpname->status) }}">{{ $stokOpname->status }}</span>
                            </p>
                        </div>
                    </div>
                    @if ($stokOpname->catatan)
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Catatan Awal Sesi</label>
                                <p class="text-muted fst-italic">{{ $stokOpname->catatan }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- BLOK BARU UNTUK MENAMPILKAN CATATAN PENGERJAAN --}}
                    @if ($stokOpname->catatan_pengerjaan)
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Catatan Pengerjaan / Ringkasan</label>
                                <p class="text-muted">{{ $stokOpname->catatan_pengerjaan }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Ringkasan Hasil</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- Summary Cards --}}
                    <div class="col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div class="align-self-center">
                                        <h6 class="m-0">Total Unit Diperiksa</h6>
                                        <h4 class="mt-2 mb-0">{{ $summary['total_diperiksa'] }}</h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-list-ol fs-2 text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div class="align-self-center">
                                        <h6 class="m-0">Sesuai Sistem</h6>
                                        <h4 class="mt-2 mb-0">{{ $summary['sesuai'] }}</h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fs-2 text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div class="align-self-center">
                                        <h6 class="m-0">Kondisi Berubah</h6>
                                        <h4 class="mt-2 mb-0">{{ $summary['kondisi_berubah'] }}</h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-exclamation-triangle fs-2 text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div class="align-self-center">
                                        <h6 class="m-0">Unit Hilang</h6>
                                        <h4 class="mt-2 mb-0">{{ $summary['hilang'] }}</h4>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-times-circle fs-2 text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Rincian Hasil Pemeriksaan</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Barang</th>
                                <th>Kode Inventaris</th>
                                <th>Kondisi Sistem</th>
                                <th>Kondisi Fisik (Hasil)</th>
                                <th>Status Pemeriksaan</th>
                                <th>Catatan Operator</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stokOpname->detailStokOpname as $index => $detail)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ optional($detail->barangQrCode->barang)->nama_barang ?? 'N/A' }}</td>
                                    <td>{{ $detail->barangQrCode->kode_inventaris_sekolah ?? 'N/A' }}</td>
                                    <td><span
                                            class="badge {{ \App\Models\BarangQrCode::getKondisiColor($detail->kondisi_tercatat) }}">{{ $detail->kondisi_tercatat }}</span>
                                    </td>
                                    <td><span
                                            class="badge {{ \App\Models\BarangQrCode::getKondisiColor($detail->kondisi_fisik) }}">{{ $detail->kondisi_fisik }}</span>
                                    </td>
                                    <td>
                                        @if ($detail->kondisi_tercatat === 'Baru')
                                            {{-- LOGIKA YANG DIPERBAIKI --}}
                                            <span class="badge text-bg-info">TEMUAN BARU</span>
                                        @elseif ($detail->kondisi_fisik === 'Hilang')
                                            <span class="badge text-bg-danger">HILANG</span>
                                        @elseif ($detail->kondisi_fisik === $detail->kondisi_tercatat)
                                            <span class="badge text-bg-success">SESUAI</span>
                                        @else
                                            <span class="badge text-bg-warning">KONDISI BERUBAH</span>
                                        @endif
                                    </td>
                                    <td>{{ $detail->catatan_fisik ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">Tidak ada data detail untuk sesi stok opname ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <a href="{{ route($rolePrefix . 'stok-opname.index') }}" class="btn btn-secondary">Kembali ke Daftar
                        Stok Opname</a>
                </div>
            </div>
        </div>
    </div>
@endsection
