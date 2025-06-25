@extends('layouts.app')

@section('title', 'Buat Laporan Pemeliharaan Baru')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section('content')
    <div class="container-fluid">
        {{-- Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">@yield('title')</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a
                                    href="{{ route(Auth::user()->getRolePrefix() . 'dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route(Auth::user()->getRolePrefix() . 'pemeliharaan.index') }}">Pemeliharaan</a>
                            </li>
                            <li class="breadcrumb-item active">Buat Laporan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Formulir Laporan Kerusakan Barang</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route(Auth::user()->getRolePrefix() . 'pemeliharaan.store') }}"
                    enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="id_barang_qr_code_select" class="form-label">Pilih Unit Barang yang Akan Dilaporkan
                            <span class="text-danger">*</span></label>
                        <select name="id_barang_qr_code" id="id_barang_qr_code_select" class="form-control" required>
                            <option value="">-- Pilih Unit Barang --</option>
                            {{-- Loop melalui data yang sudah disiapkan controller --}}
                            @foreach ($barangQrOptions as $option)
                                <option value="{{ $option['id'] }}" {{-- Jika ada ID dari request, pilih opsi ini --}}
                                    {{ old('id_barang_qr_code', $barangQrCode->id ?? '') == $option['id'] ? 'selected' : '' }}>
                                    {{ $option['text'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_barang_qr_code')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tanggal_pengajuan" class="form-label">Tanggal Pelaporan <span
                                    class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('tanggal_pengajuan') is-invalid @enderror"
                                id="tanggal_pengajuan" name="tanggal_pengajuan"
                                value="{{ old('tanggal_pengajuan', date('Y-m-d')) }}" required>
                            @error('tanggal_pengajuan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="prioritas" class="form-label">Prioritas Penanganan <span
                                    class="text-danger">*</span></label>
                            <select name="prioritas" id="prioritas"
                                class="form-select @error('prioritas') is-invalid @enderror" required>
                                @foreach ($prioritasOptions as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ old('prioritas', 'sedang') == $key ? 'selected' : '' }}>{{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            @error('prioritas')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="catatan_pengajuan" class="form-label">Jelaskan Kerusakan atau Keluhan <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control @error('catatan_pengajuan') is-invalid @enderror" id="catatan_pengajuan"
                            name="catatan_pengajuan" rows="4"
                            placeholder="Contoh: Proyektor tidak mau menyala, lampu indikator berkedip merah." required>{{ old('catatan_pengajuan') }}</textarea>
                        @error('catatan_pengajuan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="foto_kerusakan" class="form-label">Unggah Foto Kerusakan (Opsional)</label>
                        <input class="form-control @error('foto_kerusakan') is-invalid @enderror" type="file"
                            id="foto_kerusakan" name="foto_kerusakan" accept="image/*">
                        <small class="form-text text-muted">Format: JPG, PNG. Maks: 2MB.</small>
                        @error('foto_kerusakan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="text-end">
                        <a href="{{ route(Auth::user()->getRolePrefix() . 'pemeliharaan.index') }}"
                            class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Kirim
                            Laporan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Memuat library yang dibutuhkan oleh halaman ini --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inisialisasi Select2 standar (bukan AJAX)
            $('#id_barang_qr_code_select').select2({
                theme: "bootstrap-5",
                placeholder: 'Cari dan Pilih Unit Barang...',
            });

            // Inisialisasi untuk dropdown Prioritas
            $('#prioritas').select2({
                theme: "bootstrap-5",
                minimumResultsForSearch: Infinity // Sembunyikan search box
            });
        });
    </script>
@endpush
