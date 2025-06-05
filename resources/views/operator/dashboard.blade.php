{{-- File: resources/views/operator/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard Operator')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Dashboard Operator</h4>
                {{-- Tambahkan breadcrumb jika perlu --}}
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4 col-md-6">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <p class="text-muted mb-2">Jenis Barang di Ruangan Anda</p>
                            {{-- Menggunakan variabel dari DashboardController@operator --}}
                            <h4 class="mb-0">{{ $jumlahJenisBarangDiRuanganOperator ?? 0 }}</h4>
                        </div>
                        <div class="col-4 text-end">
                            <i class="mdi mdi-archive-search-outline font-size-24 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <p class="text-muted mb-2">Total Unit Fisik di Ruangan Anda</p>
                            {{-- Menggunakan variabel dari DashboardController@operator --}}
                            <h4 class="mb-0">{{ $jumlahUnitBarangDiRuanganOperator ?? 0 }}</h4>
                        </div>
                        <div class="col-4 text-end">
                            <i class="mdi mdi-cube-outline font-size-24 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <p class="text-muted mb-2">Peminjaman Menunggu Persetujuan</p>
                            {{-- Menggunakan variabel dari DashboardController@operator --}}
                            <h4 class="mb-0">{{ $peminjamanMenungguOperator->count() ?? 0 }}</h4>
                        </div>
                        <div class="col-4 text-end">
                            <i class="mdi mdi-file-sign font-size-24 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (isset($ruanganDikelola) && $ruanganDikelola->count() > 0)
        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Ruangan yang Anda Kelola</h5>
                    </div>
                    <div class="card-body">
                        <ul>
                            @foreach ($ruanganDikelola as $ruangan)
                                <li>{{ $ruangan->nama_ruangan }} ({{ $ruangan->kode_ruangan }})</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (isset($peminjamanMenungguOperator) && $peminjamanMenungguOperator->count() > 0)
        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">5 Pengajuan Peminjaman Terbaru Menunggu Persetujuan Anda</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>ID Peminjaman</th>
                                        <th>Peminjam (Guru)</th>
                                        <th>Tujuan</th>
                                        <th>Tgl. Pengajuan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($peminjamanMenungguOperator as $peminjaman)
                                        <tr>
                                            <td>{{ $peminjaman->id }}</td>
                                            <td>{{ $peminjaman->guru->username ?? 'N/A' }}</td>
                                            <td>{{ Str::limit($peminjaman->tujuan_peminjaman, 50) }}</td>
                                            <td>{{ $peminjaman->tanggal_pengajuan ? \Carbon\Carbon::parse($peminjaman->tanggal_pengajuan)->isoFormat('DD MMM YYYY, HH:mm') : '-' }}
                                            </td>
                                            <td>
                                                <a href="{{ route('operator.peminjaman.show', $peminjaman->id) }}"
                                                    class="btn btn-sm btn-info">Detail</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
