@extends('layouts.app')

@section('title', 'Dashboard Guru')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Dashboard</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Jumlah Peminjaman Guru -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <p class="text-muted mb-2">Peminjaman Anda</p>
                            <h4 class="mb-0">{{ $jumlahPeminjaman }}</h4>
                        </div>
                        <div class="col-4 text-end">
                            <i class="mdi mdi-file-document-box-outline font-size-24 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
