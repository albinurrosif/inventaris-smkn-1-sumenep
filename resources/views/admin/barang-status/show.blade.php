@extends('layouts.app')

@section('title', 'Detail Riwayat Status Barang')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Detail Riwayat Status Unit Barang</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('barang-status.index') }}">Histori Status
                                    Barang</a></li>
                            <li class="breadcrumb-item active">Detail</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12 text-end">
                <a href="{{ url()->previous(route('barang-status.index')) }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>

        @include('admin.barang-status._detail')
    </div>
@endsection
