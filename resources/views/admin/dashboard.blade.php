{{-- File: resources/views/admin/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Dashboard Admin</h4>
                {{-- Tambahkan breadcrumb jika perlu --}}
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <p class="text-muted mb-2">Total Jenis Barang</p>
                            {{-- Menggunakan variabel dari DashboardController@admin --}}
                            <h4 class="mb-0">{{ $jumlahJenisBarang ?? 0 }}</h4>
                        </div>
                        <div class="col-4">
                            <div class="text-end">
                                <i class="mdi mdi-archive-outline font-size-24 text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <p class="text-muted mb-2">Total Unit Barang Fisik</p>
                            {{-- Menggunakan variabel dari DashboardController@admin --}}
                            <h4 class="mb-0">{{ $jumlahUnitBarang ?? 0 }}</h4>
                        </div>
                        <div class="col-4">
                            <div class="text-end">
                                <i class="mdi mdi-cube-send font-size-24 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <p class="text-muted mb-2">Total Pengguna</p>
                            {{-- Menggunakan variabel dari DashboardController@admin --}}
                            <h4 class="mb-0">{{ $jumlahUser ?? 0 }}</h4>
                        </div>
                        <div class="col-4">
                            <div class="text-end">
                                <i class="mdi mdi-account-group-outline font-size-24 text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <p class="text-muted mb-2">Peminjaman Menunggu</p>
                            {{-- Menggunakan variabel dari DashboardController@admin --}}
                            <h4 class="mb-0">{{ $peminjamanMenunggu ?? 0 }}</h4>
                        </div>
                        <div class="col-4">
                            <div class="text-end">
                                <i class="mdi mdi-file-document-edit-outline font-size-24 text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Tambah card lain sesuai kebutuhan (misal: total nilai aset, barang rusak, dll.) --}}
    </div>

    {{-- Tambahkan bagian lain untuk dashboard admin, misalnya: --}}
    {{-- - Grafik --}}
    {{-- - Tabel ringkasan aktivitas terbaru --}}
    {{-- - Shortcut ke menu penting --}}

@endsection
