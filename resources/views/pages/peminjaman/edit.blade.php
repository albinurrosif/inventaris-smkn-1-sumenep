@extends('layouts.app')

@php
    $rolePrefix = Auth::user()->getRolePrefix();
    $isGuru = Auth::user()->hasRole(\App\Models\User::ROLE_GURU);
    $isManageable = in_array($peminjaman->status, [\App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN]);
@endphp

@section('title', 'Edit Peminjaman #' . $peminjaman->id)

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--multiple {
            border-color: #ced4da;
        }

        .select2-container .select2-selection--multiple {
            min-height: calc(1.5em + .75rem + 2px);
        }

        .form-control:disabled,
        .form-control[readonly] {
            background-color: #e9ecef;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        {{-- Page Title & Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Edit Peminjaman #{{ $peminjaman->id }}</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route($rolePrefix . 'dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route($rolePrefix . 'peminjaman.index') }}">Peminjaman</a></li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Formulir Edit Peminjaman</h5>
                <span
                    class="badge fs-6 {{ \App\Models\Peminjaman::statusColor($peminjaman->status) }}">{{ $peminjaman->status }}</span>
            </div>
            <div class="card-body">
                @if ($isGuru && !$isManageable)
                    <div class="alert alert-warning text-dark">
                        <strong>Informasi:</strong> Pengajuan ini tidak dapat diedit lagi karena sudah diproses oleh
                        operator.
                    </div>
                @endif

                <form action="{{ route($rolePrefix . 'peminjaman.update', $peminjaman->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        {{-- Kolom Kiri --}}
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="id_barang_qr_code" class="form-label">Barang yang Dipinjam</label>
                                <select class="form-control" id="id_barang_qr_code" name="id_barang_qr_code[]"
                                    multiple="multiple" {{ $isGuru && $isManageable ? '' : 'disabled' }}>
                                    @foreach ($barangList as $item)
                                        <option value="{{ $item['id'] }}"
                                            {{ in_array($item['id'], old('id_barang_qr_code', $selectedBarangIds)) ? 'selected' : '' }}>
                                            {{ $item['text'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_barang_qr_code')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="tujuan_peminjaman" class="form-label">Tujuan Peminjaman</label>
                                <textarea class="form-control" id="tujuan_peminjaman" name="tujuan_peminjaman" rows="3"
                                    {{ $isGuru && $isManageable ? '' : 'disabled' }}>{{ old('tujuan_peminjaman', $peminjaman->tujuan_peminjaman) }}</textarea>
                                @error('tujuan_peminjaman')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="catatan_peminjam" class="form-label">Catatan Peminjam (Opsional)</label>
                                <textarea class="form-control" id="catatan_peminjam" name="catatan_peminjam" rows="2"
                                    {{ $isGuru && $isManageable ? '' : 'disabled' }}>{{ old('catatan_peminjam', $peminjaman->catatan_peminjam) }}</textarea>
                            </div>
                        </div>

                        {{-- Kolom Kanan --}}
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tanggal_rencana_pinjam" class="form-label">Rencana Tanggal Pinjam</label>
                                    <input type="date" class="form-control" id="tanggal_rencana_pinjam"
                                        name="tanggal_rencana_pinjam"
                                        value="{{ old('tanggal_rencana_pinjam', \Carbon\Carbon::parse($peminjaman->tanggal_rencana_pinjam)->format('Y-m-d')) }}"
                                        {{ $isGuru && $isManageable ? '' : 'disabled' }}>
                                    @error('tanggal_rencana_pinjam')
                                        <div class="text-danger mt-1 small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tanggal_harus_kembali" class="form-label">Tenggat Pengembalian</label>
                                    <input type="date" class="form-control" id="tanggal_harus_kembali"
                                        name="tanggal_harus_kembali"
                                        value="{{ old('tanggal_harus_kembali', \Carbon\Carbon::parse($peminjaman->tanggal_harus_kembali)->format('Y-m-d')) }}"
                                        {{ $isGuru && $isManageable ? '' : 'disabled' }}>
                                    @error('tanggal_harus_kembali')
                                        <div class="text-danger mt-1 small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="id_ruangan_tujuan_peminjaman" class="form-label">Ruangan Tujuan Penggunaan
                                    (Opsional)</label>
                                <select class="form-select" id="id_ruangan_tujuan_peminjaman"
                                    name="id_ruangan_tujuan_peminjaman" {{ $isGuru && $isManageable ? '' : 'disabled' }}>
                                    <option value="">-- Tidak ada ruangan spesifik --</option>
                                    @foreach ($ruanganTujuanList as $ruangan)
                                        <option value="{{ $ruangan->id }}"
                                            {{ old('id_ruangan_tujuan_peminjaman', $peminjaman->id_ruangan_tujuan_peminjaman) == $ruangan->id ? 'selected' : '' }}>
                                            {{ $ruangan->nama_ruangan }} ({{ $ruangan->kode_ruangan }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- HANYA UNTUK ADMIN/OPERATOR --}}
                            @can('manage', $peminjaman)
                                <div class="mb-3">
                                    <label for="catatan_operator" class="form-label">Catatan Operator</label>
                                    <textarea class="form-control" id="catatan_operator" name="catatan_operator" rows="2">{{ old('catatan_operator', $peminjaman->catatan_operator) }}</textarea>
                                </div>
                            @endcan
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <a href="{{ route($rolePrefix . 'peminjaman.show', $peminjaman->id) }}"
                            class="btn btn-secondary me-2">Batal</a>
                        @can('update', $peminjaman)
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Simpan Perubahan
                            </button>
                        @endcan
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#id_barang_qr_code').select2({
                placeholder: "Cari dan pilih barang...",
                allowClear: true,
                // Jika field di-disable, Select2 juga akan otomatis disable
            });
        });
    </script>
@endpush
