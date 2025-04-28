@extends('layouts.app')

@section('title', 'Dashboard Operator')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Dashboard</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Jumlah Barang -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <p class="text-muted mb-2">Jumlah Barang di Ruangan Anda</p>
                            <h4 class="mb-0">{{ $jumlahBarang }}</h4>
                        </div>
                        <div class="col-4 text-end">
                            <i class="mdi mdi-cube-outline font-size-24 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Riwayat Peminjaman (hitung total peminjaman) -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <p class="text-muted mb-2">Riwayat Peminjaman Anda</p>
                            <h4 class="mb-0">{{ $riwayatPeminjaman->count() }}</h4>
                        </div>
                        <div class="col-4 text-end">
                            <i class="mdi mdi-history font-size-24 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
