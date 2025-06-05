{{-- File: resources/views/guru/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard Guru')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Dashboard Guru</h4>
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
                            <p class="text-muted mb-2">Peminjaman Aktif Anda</p>
                            {{-- Menggunakan variabel dari DashboardController@guru --}}
                            <h4 class="mb-0">{{ $jumlahPeminjamanAktif ?? 0 }}</h4>
                        </div>
                        <div class="col-4 text-end">
                            <i class="mdi mdi-briefcase-check-outline font-size-24 text-success"></i>
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
                            <p class="text-muted mb-2">Pengajuan Menunggu Persetujuan</p>
                            {{-- Menggunakan variabel dari DashboardController@guru --}}
                            <h4 class="mb-0">{{ $jumlahPengajuanMenunggu ?? 0 }}</h4>
                        </div>
                        <div class="col-4 text-end">
                            <i class="mdi mdi-timer-sand font-size-24 text-warning"></i>
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
                            <p class="text-muted mb-2">Total Riwayat Peminjaman</p>
                            {{-- Menggunakan variabel dari DashboardController@guru --}}
                            <h4 class="mb-0">
                                {{ $riwayatPeminjamanGuru->total() ?? ($riwayatPeminjamanGuru->count() ?? 0) }}
                            </h4>
                        </div>
                        <div class="col-4 text-end">
                            <i class="mdi mdi-history font-size-24 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (isset($riwayatPeminjamanGuru) && $riwayatPeminjamanGuru->count() > 0)
        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">5 Riwayat Peminjaman Terakhir Anda</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>ID Peminjaman</th>
                                        <th>Tujuan</th>
                                        <th>Tgl. Pengajuan</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($riwayatPeminjamanGuru as $peminjaman)
                                        <tr>
                                            <td>{{ $peminjaman->id }}</td>
                                            <td>{{ Str::limit($peminjaman->tujuan_peminjaman, 70) }}</td>
                                            <td>{{ $peminjaman->tanggal_pengajuan ? \Carbon\Carbon::parse($peminjaman->tanggal_pengajuan)->isoFormat('DD MMM YYYY') : '-' }}
                                            </td>
                                            <td>
                                                <span
                                                    class="badge
                                                @if ($peminjaman->status == 'Selesai') bg-success
                                                @elseif($peminjaman->status == 'Ditolak') bg-danger
                                                @elseif($peminjaman->status == 'Sedang Dipinjam') bg-info
                                                @elseif($peminjaman->status == 'Menunggu Persetujuan') bg-warning
                                                @else bg-secondary @endif">
                                                    {{ $peminjaman->status }}
                                                </span>
                                            </td>
                                            <td>
                                                {{-- Sesuaikan route untuk detail peminjaman guru --}}
                                                <a href="{{ route('guru.peminjaman.show', $peminjaman->id) }}"
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
