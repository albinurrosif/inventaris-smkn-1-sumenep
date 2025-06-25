@extends('layouts.app')

@section('title', 'Lapor Pemeliharaan Baru')

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
                            <li class="breadcrumb-item active">Lapor Baru</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Form Laporan Kerusakan Barang</h5>
            </div>
            <div class="card-body">
                <form action="{{ route(Auth::user()->getRolePrefix() . 'pemeliharaan.store') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="id_barang_qr_code" class="form-label">Unit Barang yang Rusak <span
                                        class="text-danger">*</span></label>
                                <select name="id_barang_qr_code" id="id_barang_qr_code" class="form-select select2-barang"
                                    required>
                                    <option value="">-- Cari Kode atau Nama Barang --</option>
                                    @foreach ($barangList as $barang)
                                        <option value="{{ $barang->id }}"
                                            {{ old('id_barang_qr_code') == $barang->id ? 'selected' : '' }}>
                                            {{ $barang->barang->nama_barang }} ({{ $barang->kode_inventaris_sekolah }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="prioritas" class="form-label">Prioritas <span
                                        class="text-danger">*</span></label>
                                <select name="prioritas" id="prioritas" class="form-select" required>
                                    @foreach ($prioritasList as $key => $value)
                                        <option value="{{ $key }}"
                                            {{ old('prioritas') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="catatan_pengajuan" class="form-label">Deskripsikan Kerusakan <span
                                class="text-danger">*</span></label>
                        <textarea name="catatan_pengajuan" id="catatan_pengajuan" class="form-control" rows="5" required
                            placeholder="Contoh: Proyektor tidak mau menyala, lampu indikator berkedip merah.">{{ old('catatan_pengajuan') }}</textarea>
                    </div>

                    {{-- Anda bisa tambahkan input untuk upload foto di sini jika perlu --}}

                    <div class="text-end">
                        <a href="{{ route(Auth::user()->getRolePrefix() . 'pemeliharaan.index') }}"
                            class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">Kirim Laporan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2-barang').select2({
                theme: "bootstrap-5",
                placeholder: $(this).data('placeholder'),
            });
        });
    </script>
@endpush
