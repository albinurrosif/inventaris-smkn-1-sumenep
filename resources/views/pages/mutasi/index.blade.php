@extends('layouts.app')

@section('title', 'Riwayat Mutasi Barang')

@php
    $rolePrefix = Auth::user()->getRolePrefix();
@endphp

@section('content')
    <div class="container-fluid">
        {{-- Page Title & Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Riwayat Mutasi Barang</h4>
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
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Kode Unit</th>
                                <th>Nama Barang</th>
                                <th>Dari</th>
                                <th>Ke</th>
                                <th>Admin</th>
                                <th>Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($riwayatMutasi as $mutasi)
                                <tr>
                                    <td>{{ $mutasi->tanggal_mutasi->isoFormat('DD MMM YYYY, HH:mm') }}</td>
                                    <td><code>{{ optional($mutasi->barangQrCode)->kode_inventaris_sekolah }}</code></td>
                                    <td>{{ optional(optional($mutasi->barangQrCode)->barang)->nama_barang }}</td>
                                    <td><span
                                            class="badge bg-soft-danger text-danger">{{ optional($mutasi->ruanganAsal)->nama_ruangan }}</span>
                                    </td>
                                    <td><span
                                            class="badge bg-soft-success text-success">{{ optional($mutasi->ruanganTujuan)->nama_ruangan }}</span>
                                    </td>
                                    <td>{{ optional($mutasi->admin)->username }}</td>
                                    <td title="{{ $mutasi->alasan_pemindahan }}">
                                        {{ Str::limit($mutasi->alasan_pemindahan, 40) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">Belum ada riwayat mutasi barang.</td>
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
