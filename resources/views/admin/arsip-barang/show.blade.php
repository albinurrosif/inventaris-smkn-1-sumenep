@extends('layouts.app')

@section('title', 'Detail Arsip Unit: ' . ($arsip->barangQrCode->kode_inventaris_sekolah ?? 'N/A'))

@php
    // Definisikan rolePrefix, default ke admin karena ini adalah area admin
    $rolePrefix = 'admin.';
@endphp

@section('content')
    <div class="container-fluid">
        {{-- Page Title & Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Detail Arsip Barang</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.arsip-barang.index') }}">Arsip Barang</a>
                            </li>
                            <li class="breadcrumb-item active">Detail</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Alert Status Arsip --}}
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-archive me-2"></i>
            Unit barang ini telah diarsipkan pada
            <strong>{{ $arsip->tanggal_pengajuan_arsip->isoFormat('DD MMMM YYYY') }}</strong>
            dengan status: <strong>{{ $arsip->status_arsip }}</strong>.
        </div>

        <div class="row">
            {{-- Kolom Kiri (Informasi Pengarsipan & Snapshot Aset) --}}
            <div class="col-lg-7">
                {{-- Informasi Pengarsipan --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informasi Pengarsipan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless">
                                <tbody>
                                    <tr>
                                        <th style="width: 30%;">Jenis Penghapusan</th>
                                        <td>: {{ $arsip->jenis_penghapusan }}</td>
                                    </tr>
                                    <tr>
                                        <th>Alasan Penghapusan</th>
                                        <td>: {{ $arsip->alasan_penghapusan }}</td>
                                    </tr>
                                    <tr>
                                        <th>Diajukan Oleh</th>
                                        <td>: {{ $arsip->userPengaju->username ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Tanggal Pengajuan</th>
                                        <td>: {{ $arsip->tanggal_pengajuan_arsip->isoFormat('dddd, DD MMMM YYYY, HH:mm') }}
                                        </td>
                                    </tr>
                                    @if ($arsip->id_user_penyetuju)
                                        <tr>
                                            <th>Disetujui Oleh</th>
                                            <td>: {{ $arsip->userPenyetuju->username ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Penghapusan Resmi</th>
                                            <td>:
                                                {{ $arsip->tanggal_penghapusan_resmi?->isoFormat('dddd, DD MMMM YYYY, HH:mm') ?? '-' }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($arsip->dipulihkan_oleh)
                                        <tr>
                                            <th>Dipulihkan Oleh</th>
                                            <td>: {{ $arsip->dipulihkanOlehUser->username ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Dipulihkan</th>
                                            <td>:
                                                {{ $arsip->tanggal_dipulihkan?->isoFormat('dddd, DD MMMM YYYY, HH:mm') ?? '-' }}
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Snapshot Data Aset Saat Diarsipkan --}}
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Snapshot Data Aset Saat Diarsipkan</h5>
                    </div>
                    <div class="card-body">
                        @if ($arsip->data_unit_snapshot)
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless">
                                    <tbody>
                                        <tr>
                                            <th style="width: 30%;">Kode Inventaris</th>
                                            <td>: {{ $arsip->data_unit_snapshot['kode_inventaris_sekolah'] ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Kondisi Saat Diarsipkan</th>
                                            <td>: {{ $arsip->data_unit_snapshot['kondisi'] ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Status Saat Diarsipkan</th>
                                            <td>: {{ $arsip->data_unit_snapshot['status'] ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Harga Perolehan Unit</th>
                                            <td>: Rp
                                                {{ number_format($arsip->data_unit_snapshot['harga_perolehan_unit'] ?? 0, 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Tidak ada data snapshot yang tersimpan.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan (Aksi & Dokumen) --}}
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Aksi & Dokumen</h5>
                    </div>
                    <div class="card-body d-grid gap-2">
                        {{-- Aksi Pulihkan Barang --}}
                        @can('restore', $arsip->barangQrCode)
                            @if ($arsip->status_arsip !== \App\Models\ArsipBarang::STATUS_ARSIP_DIPULIHKAN)
                                <form action="{{ route('admin.arsip-barang.restore', $arsip->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100"
                                        onclick="return confirm('Anda yakin ingin memulihkan unit barang ini dari arsip? Barang akan berstatus Aktif dan tidak memiliki lokasi.')">
                                        <i class="fas fa-undo me-1"></i> Pulihkan Barang
                                    </button>
                                </form>
                            @else
                                <p class="text-success text-center"><i class="fas fa-check-circle me-1"></i> Barang ini sudah
                                    dipulihkan.</p>
                            @endif
                        @endcan

                        {{-- Link ke Dokumen --}}
                        @if ($arsip->berita_acara_path)
                            <a href="{{ asset('storage/' . $arsip->berita_acara_path) }}" target="_blank"
                                class="btn btn-outline-info w-100">
                                <i class="fas fa-file-pdf me-1"></i> Lihat Berita Acara
                            </a>
                        @endif
                        @if ($arsip->foto_bukti_path)
                            <a href="{{ asset('storage/' . $arsip->foto_bukti_path) }}" target="_blank"
                                class="btn btn-outline-secondary w-100">
                                <i class="fas fa-image me-1"></i> Lihat Foto Bukti
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
