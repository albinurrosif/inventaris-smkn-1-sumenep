@extends('layouts.app')

@section('title', 'Detail Mutasi Barang #' . $mutasiBarang->id)

@push('styles')
    <style>
        .detail-label {
            font-weight: 600;
            color: #555;
            width: 150px;
            /* Lebar tetap untuk label */
        }

        .detail-value {
            color: #212529;
        }

        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 1.25rem;
            border-radius: .25rem;
        }

        .location-flow {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .location-box {
            padding: 0.75rem 1rem;
            border-radius: .25rem;
            text-align: center;
            flex-grow: 1;
        }

        .location-from {
            background-color: #fbeaea;
            border: 1px solid #f4c6c6;
            color: #721c24;
        }

        .location-to {
            background-color: #eaf6ec;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .flow-arrow {
            font-size: 1.5rem;
            color: #6c757d;
        }
    </style>
@endpush

@php
    $rolePrefix = Auth::user()->getRolePrefix();
@endphp

@section('content')
    <div class="container-fluid">
        {{-- Page Title & Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Detail Mutasi #{{ $mutasiBarang->id }}</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route($rolePrefix . 'dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route($rolePrefix . 'mutasi-barang.index') }}">Riwayat
                                    Mutasi</a></li>
                            <li class="breadcrumb-item active">Detail</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-exchange-alt me-2"></i>Detail Perpindahan Aset</h5>
                <a href="{{ route($rolePrefix . 'mutasi-barang.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Riwayat
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    {{-- Kolom Kiri: Informasi Aset --}}
                    <div class="col-lg-6">
                        <h6 class="mb-3">Informasi Aset yang Dipindahkan</h6>
                        <div class="info-box">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="detail-label">Kode Unit</td>
                                    <td>
                                        @if ($mutasiBarang->barangQrCode)
                                            <a
                                                href="{{ route($rolePrefix . 'barang-qr-code.show', $mutasiBarang->barangQrCode->id) }}">
                                                <code>{{ $mutasiBarang->barangQrCode->kode_inventaris_sekolah }}</code>
                                            </a>
                                        @else
                                            <span class="text-danger">Aset Dihapus</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Nama Barang</td>
                                    <td>{{ optional(optional($mutasiBarang->barangQrCode)->barang)->nama_barang ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Merk/Model</td>
                                    <td>{{ optional(optional($mutasiBarang->barangQrCode)->barang)->merk_model ?? '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="detail-label">No. Seri</td>
                                    <td>{{ optional($mutasiBarang->barangQrCode)->no_seri_pabrik ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    {{-- Kolom Kanan: Informasi Mutasi --}}
                    <div class="col-lg-6">
                        <h6 class="mb-3">Informasi Proses Mutasi</h6>
                        <div class="info-box">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="detail-label">Tanggal Mutasi</td>
                                    <td>{{ $mutasiBarang->tanggal_mutasi->isoFormat('dddd, DD MMMM YYYY - HH:mm') }}</td>
                                </tr>
                                <tr>
                                    <td class="detail-label">Admin Pelaksana</td>
                                    <td>{{ optional($mutasiBarang->admin)->username ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="detail-label align-top">Alasan</td>
                                    <td class="align-top">{{ $mutasiBarang->alasan_pemindahan }}</td>
                                </tr>
                                @if ($mutasiBarang->surat_pemindahan_path)
                                    <tr>
                                        <td class="detail-label">Dokumen</td>
                                        <td>
                                            <a href="{{ Storage::url($mutasiBarang->surat_pemindahan_path) }}"
                                                target="_blank" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-file-alt me-1"></i> Lihat Dokumen
                                            </a>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Visualisasi Perpindahan --}}
                <div class="row mt-4">
                    <div class="col-12">
                        <h6 class="mb-3">Alur Perpindahan</h6>
                        <div class="location-flow">
                            <div class="location-box location-from">
                                <div class="fw-bold">DARI</div>
                                <div class="fs-5">{{ optional($mutasiBarang->ruanganAsal)->nama_ruangan ?? 'N/A' }}</div>
                            </div>
                            <div class="flow-arrow">
                                <i class="fas fa-long-arrow-alt-right"></i>
                            </div>
                            <div class="location-box location-to">
                                <div class="fw-bold">KE</div>
                                <div class="fs-5">{{ optional($mutasiBarang->ruanganTujuan)->nama_ruangan ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
