@extends('layouts.app') {{-- Sesuaikan dengan layout utama Anda --}}

@section('title', 'Tambah Barang Baru (Wizard)')

@push('styles')
    {{-- CSS untuk Twitter Bootstrap Wizard --}}
    <link rel="stylesheet" href="{{ asset('assets/libs/twitter-bootstrap-wizard/prettify.css') }}">
    {{-- CSS untuk Flatpickr (Datepicker) --}}
    <link rel="stylesheet" href="{{ asset('assets/libs/flatpickr/flatpickr.min.css') }}">
    {{-- Choices.js CSS (jika belum ada di layout utama Anda) --}}
    <link href="{{ asset('assets/libs/choices.js/public/assets/styles/choices.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .twitter-bs-wizard-nav {
            margin-bottom: 20px;
        }

        .step-title {
            display: block;
            margin-top: 5px;
            font-size: 0.85rem;
        }

        .twitter-bs-wizard-nav .nav-link {
            padding: 10px;
            text-align: center;
        }

        .twitter-bs-wizard .progress {
            height: 8px;
            border-radius: 4px;
        }

        .tab-content {
            min-height: 400px;
            /* Beri tinggi minimal agar tidak melompat */
        }

        .pager.wizard {
            padding-top: 15px;
            border-top: 1px solid #eff2f7;
            margin-top: 20px;
        }

        .choices.is-invalid .choices__inner {
            border-color: #dc3545 !important;
        }

        .choices__list--dropdown .choices__item--selectable.is-highlighted {
            background-color: #556ee6 !important;
            /* Warna primary template Anda */
            color: white;
        }

        .serial-invalid-feedback {
            width: 100%;
            margin-top: .25rem;
            font-size: .875em;
            color: #dc3545;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        @php
            $userRole = Auth::check() ? strtolower(Auth::user()->role) : 'admin';
            $dashboardRouteName = $userRole . '.dashboard';
            if (!Route::has($dashboardRouteName)) {
                $dashboardRouteName = 'admin.dashboard'; // Fallback
            }
        @endphp
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Tambah Jenis Barang Baru</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route($dashboardRouteName) }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('barang.index') }}">Jenis Barang</a></li>
                            <li class="breadcrumb-item active">Tambah Baru</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Formulir Penambahan Barang dan Unit Awal</h4>
                    </div>
                    <div class="card-body">
                        <form id="createBarangWizardForm" method="POST" action="{{ route('barang.store') }}">
                            @csrf

                            <div id="barang-wizard" class="twitter-bs-wizard">
                                <ul class="twitter-bs-wizard-nav nav nav-pills nav-justified">
                                    <li class="nav-item">
                                        <a href="#step1-jenis-barang" class="nav-link active" data-bs-toggle="tab"
                                            data-bs-target="#step1-jenis-barang">
                                            <div class="step-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Informasi Jenis Barang"><i class="bx bx-package font-size-20"></i>
                                            </div>
                                            <span class="step-title">1. Jenis Barang</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#step2-unit-awal" class="nav-link" data-bs-toggle="tab"
                                            data-bs-target="#step2-unit-awal">
                                            <div class="step-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Rencana Detail Unit Awal"><i
                                                    class="bx bx-slider-alt font-size-20"></i></div>
                                            <span class="step-title">2. Detail Unit</span>
                                        </a>
                                    </li>
                                    <li class="nav-item step3-nav"> {{-- Tambah class untuk show/hide --}}
                                        <a href="#step3-nomor-seri" class="nav-link" data-bs-toggle="tab"
                                            data-bs-target="#step3-nomor-seri">
                                            <div class="step-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                                title="Input Nomor Seri"><i class="bx bx-barcode-reader font-size-20"></i>
                                            </div>
                                            <span class="step-title">3. Nomor Seri</span>
                                        </a>
                                    </li>
                                </ul>

                                <div id="bar" class="progress mt-4" style="height: 8px;">
                                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated"
                                        role="progressbar" style="width: 0%;"></div>
                                </div>

                                <div class="tab-content twitter-bs-wizard-tab-content mt-4">
                                    {{-- Step 1: Informasi Jenis Barang --}}
                                    <div class="tab-pane active" id="step1-jenis-barang">
                                        <div class="text-center mb-4">
                                            <h5>Langkah 1: Informasi Jenis Barang (Induk)</h5>
                                            <p class="card-title-desc">Isi detail umum untuk jenis barang ini.</p>
                                        </div>

                                        {{-- Nama Barang --}}
                                        <div class="row mb-3">
                                            <label for="nama_barang" class="col-md-4 col-form-label text-md-end">Nama Barang
                                                <span class="text-danger">*</span></label>
                                            <div class="col-md-7">
                                                <input id="nama_barang" type="text"
                                                    class="form-control @error('nama_barang') is-invalid @enderror"
                                                    name="nama_barang" value="{{ old('nama_barang') }}"
                                                    placeholder="Contoh: Laptop ASUS Zenbook 14 OLED" required>
                                                @error('nama_barang')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        {{-- Kode Barang --}}
                                        <div class="row mb-3">
                                            <label for="kode_barang" class="col-md-4 col-form-label text-md-end">Kode Barang
                                                <span class="text-danger">*</span></label>
                                            <div class="col-md-7">
                                                <input id="kode_barang" type="text"
                                                    class="form-control @error('kode_barang') is-invalid @enderror"
                                                    name="kode_barang" value="{{ old('kode_barang') }}"
                                                    placeholder="Contoh: LP-ASUS-ZB14 (unik)" required>
                                                @error('kode_barang')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        {{-- Kategori Barang --}}
                                        <div class="row mb-3">
                                            <label for="id_kategori" class="col-md-4 col-form-label text-md-end">Kategori
                                                Barang <span class="text-danger">*</span></label>
                                            <div class="col-md-7">
                                                <select id="id_kategori"
                                                    class="form-control choices-select @error('id_kategori') is-invalid @enderror"
                                                    name="id_kategori" required>
                                                    <option value="">-- Pilih Kategori --</option>
                                                    @foreach ($kategoriList as $kategori)
                                                        <option value="{{ $kategori->id }}"
                                                            {{ old('id_kategori') == $kategori->id ? 'selected' : '' }}>
                                                            {{ $kategori->nama_kategori }}</option>
                                                    @endforeach
                                                </select>
                                                @error('id_kategori')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        {{-- Menggunakan Nomor Seri --}}
                                        <div class="row mb-3">
                                            <label class="col-md-4 col-form-label text-md-end">Kelola Unit dengan Nomor
                                                Seri? <span class="text-danger">*</span></label>
                                            <div class="col-md-7">
                                                <div class="form-check form-check-inline mt-2">
                                                    <input
                                                        class="form-check-input @error('menggunakan_nomor_seri') is-invalid @enderror"
                                                        type="radio" name="menggunakan_nomor_seri"
                                                        id="menggunakan_nomor_seri_ya" value="1"
                                                        {{ old('menggunakan_nomor_seri', '1') == '1' ? 'checked' : '' }}
                                                        required>
                                                    <label class="form-check-label"
                                                        for="menggunakan_nomor_seri_ya">Ya</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input
                                                        class="form-check-input @error('menggunakan_nomor_seri') is-invalid @enderror"
                                                        type="radio" name="menggunakan_nomor_seri"
                                                        id="menggunakan_nomor_seri_tidak" value="0"
                                                        {{ old('menggunakan_nomor_seri') == '0' ? 'checked' : '' }}
                                                        required>
                                                    <label class="form-check-label"
                                                        for="menggunakan_nomor_seri_tidak">Tidak</label>
                                                </div>
                                                @error('menggunakan_nomor_seri')
                                                    <span class="invalid-feedback d-block"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        {{-- Merk/Model --}}
                                        <div class="row mb-3">
                                            <label for="merk_model" class="col-md-4 col-form-label text-md-end">Merk /
                                                Model</label>
                                            <div class="col-md-7">
                                                <input id="merk_model" type="text"
                                                    class="form-control @error('merk_model') is-invalid @enderror"
                                                    name="merk_model" value="{{ old('merk_model') }}"
                                                    placeholder="Contoh: ASUS Zenbook UX3402ZA">
                                                @error('merk_model')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        {{-- Ukuran --}}
                                        <div class="row mb-3">
                                            <label for="ukuran"
                                                class="col-md-4 col-form-label text-md-end">Ukuran</label>
                                            <div class="col-md-7">
                                                <input id="ukuran" type="text"
                                                    class="form-control @error('ukuran') is-invalid @enderror"
                                                    name="ukuran" value="{{ old('ukuran') }}"
                                                    placeholder="Contoh: 14 inch, 120x60 cm">
                                                @error('ukuran')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        {{-- Bahan --}}
                                        <div class="row mb-3">
                                            <label for="bahan"
                                                class="col-md-4 col-form-label text-md-end">Bahan</label>
                                            <div class="col-md-7">
                                                <input id="bahan" type="text"
                                                    class="form-control @error('bahan') is-invalid @enderror"
                                                    name="bahan" value="{{ old('bahan') }}"
                                                    placeholder="Contoh: Aluminium, Kayu Jati">
                                                @error('bahan')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        {{-- Tahun Pembuatan --}}
                                        <div class="row mb-3">
                                            <label for="tahun_pembuatan" class="col-md-4 col-form-label text-md-end">Tahun
                                                Pembuatan</label>
                                            <div class="col-md-7">
                                                <input id="tahun_pembuatan" type="number"
                                                    class="form-control @error('tahun_pembuatan') is-invalid @enderror"
                                                    name="tahun_pembuatan" value="{{ old('tahun_pembuatan') }}"
                                                    placeholder="Contoh: {{ date('Y') }}" min="1900"
                                                    max="{{ date('Y') + 1 }}">
                                                @error('tahun_pembuatan')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        {{-- Harga Perolehan Induk --}}
                                        <div class="row mb-3">
                                            <label for="harga_perolehan_induk"
                                                class="col-md-4 col-form-label text-md-end">Harga Perolehan Induk
                                                (Rp)</label>
                                            <div class="col-md-7">
                                                <input id="harga_perolehan_induk" type="number" step="0.01"
                                                    class="form-control @error('harga_perolehan_induk') is-invalid @enderror"
                                                    name="harga_perolehan_induk"
                                                    value="{{ old('harga_perolehan_induk') }}"
                                                    placeholder="Harga keseluruhan jika borongan (opsional)"
                                                    min="0">
                                                @error('harga_perolehan_induk')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        {{-- Sumber Perolehan Induk --}}
                                        <div class="row mb-3">
                                            <label for="sumber_perolehan_induk"
                                                class="col-md-4 col-form-label text-md-end">Sumber Perolehan Induk</label>
                                            <div class="col-md-7">
                                                <input id="sumber_perolehan_induk" type="text"
                                                    class="form-control @error('sumber_perolehan_induk') is-invalid @enderror"
                                                    name="sumber_perolehan_induk"
                                                    value="{{ old('sumber_perolehan_induk') }}"
                                                    placeholder="Contoh: Dana BOS, APBN (opsional)">
                                                @error('sumber_perolehan_induk')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>

                                        <ul class="pager wizard twitter-bs-wizard-pager-link">
                                            <li class="float-start">
                                                <a href="{{ route('barang.index') }}" class="btn btn-light">Batal</a>
                                            </li>
                                            <li class="next float-end">
                                                <a href="javascript:void(0);" class="btn btn-primary">Lanjut <i
                                                        class="bx bx-chevron-right ms-1"></i></a>
                                            </li>
                                        </ul>
                                    </div>

                                    {{-- Step 2: Informasi Rencana Unit Awal --}}
                                    <div class="tab-pane" id="step2-unit-awal">
                                        <div class="text-center mb-4">
                                            <h5>Langkah 2: Rencana Detail Unit Awal</h5>
                                            <p class="card-title-desc">Isi detail yang akan berlaku untuk semua unit awal
                                                yang akan ditambahkan.</p>
                                        </div>
                                        {{-- Jumlah Unit Awal --}}
                                        <div class="row mb-3">
                                            <label for="jumlah_unit_awal"
                                                class="col-md-4 col-form-label text-md-end">Jumlah Unit Awal <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-md-7">
                                                <input id="jumlah_unit_awal" type="number"
                                                    class="form-control @error('jumlah_unit_awal') is-invalid @enderror"
                                                    name="jumlah_unit_awal" value="{{ old('jumlah_unit_awal', 1) }}"
                                                    placeholder="Min. 1" required min="1">
                                                @error('jumlah_unit_awal')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        {{-- Lokasi Awal: Ruangan --}}
                                        <div class="row mb-3">
                                            <label for="id_ruangan_awal"
                                                class="col-md-4 col-form-label text-md-end">Penempatan di Ruangan</label>
                                            <div class="col-md-7">
                                                <select id="id_ruangan_awal"
                                                    class="form-control choices-select @error('id_ruangan_awal') is-invalid @enderror"
                                                    name="id_ruangan_awal">
                                                    <option value="">-- Opsional: Pilih Ruangan --</option>
                                                    @foreach ($ruanganList as $ruangan)
                                                        <option value="{{ $ruangan->id }}"
                                                            {{ old('id_ruangan_awal') == $ruangan->id ? 'selected' : '' }}>
                                                            {{ $ruangan->nama_ruangan }} ({{ $ruangan->kode_ruangan }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('id_ruangan_awal')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                                <small class="form-text text-muted">Pilih ruangan ATAU pemegang personal.
                                                    Salah satu harus diisi.</small>
                                            </div>
                                        </div>
                                        {{-- Lokasi Awal: Pemegang Personal --}}
                                        <div class="row mb-3">
                                            <label for="id_pemegang_personal_awal"
                                                class="col-md-4 col-form-label text-md-end">Pemegang Personal</label>
                                            <div class="col-md-7">
                                                <select id="id_pemegang_personal_awal"
                                                    class="form-control choices-select @error('id_pemegang_personal_awal') is-invalid @enderror"
                                                    name="id_pemegang_personal_awal">
                                                    <option value="">-- Opsional: Pilih Pemegang --</option>
                                                    @foreach ($pemegangListAll as $pemegang)
                                                        <option value="{{ $pemegang->id }}"
                                                            {{ old('id_pemegang_personal_awal') == $pemegang->id ? 'selected' : '' }}>
                                                            {{ $pemegang->username }} ({{ $pemegang->role }})</option>
                                                    @endforeach
                                                </select>
                                                @error('id_pemegang_personal_awal')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        {{-- Kondisi Unit Awal --}}
                                        <div class="row mb-3">
                                            <label for="kondisi_unit_awal"
                                                class="col-md-4 col-form-label text-md-end">Kondisi Unit Awal <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-md-7">
                                                <select id="kondisi_unit_awal"
                                                    class="form-control choices-select @error('kondisi_unit_awal') is-invalid @enderror"
                                                    name="kondisi_unit_awal" required>
                                                    <option value="">-- Pilih Kondisi --</option>
                                                    @php $defaultKondisi = old('kondisi_unit_awal', \App\Models\BarangQrCode::KONDISI_BAIK); @endphp
                                                    @foreach ($kondisiOptions as $kondisi)
                                                        <option value="{{ $kondisi }}"
                                                            {{ $defaultKondisi == $kondisi ? 'selected' : '' }}>
                                                            {{ $kondisi }}</option>
                                                    @endforeach
                                                </select>
                                                @error('kondisi_unit_awal')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        {{-- Harga Perolehan Unit Awal --}}
                                        <div class="row mb-3">
                                            <label for="harga_perolehan_unit_awal"
                                                class="col-md-4 col-form-label text-md-end">Harga Perolehan per Unit (Rp)
                                                <span class="text-danger">*</span></label>
                                            <div class="col-md-7">
                                                <input id="harga_perolehan_unit_awal" type="number" step="0.01"
                                                    class="form-control @error('harga_perolehan_unit_awal') is-invalid @enderror"
                                                    name="harga_perolehan_unit_awal"
                                                    value="{{ old('harga_perolehan_unit_awal') }}"
                                                    placeholder="Contoh: 15000000" required min="0">
                                                @error('harga_perolehan_unit_awal')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        {{-- Tanggal Perolehan Unit Awal --}}
                                        <div class="row mb-3">
                                            <label for="tanggal_perolehan_unit_awal"
                                                class="col-md-4 col-form-label text-md-end">Tanggal Perolehan Unit <span
                                                    class="text-danger">*</span></label>
                                            <div class="col-md-7">
                                                <input id="tanggal_perolehan_unit_awal" type="text"
                                                    class="form-control datepicker-input @error('tanggal_perolehan_unit_awal') is-invalid @enderror"
                                                    name="tanggal_perolehan_unit_awal"
                                                    value="{{ old('tanggal_perolehan_unit_awal', date('Y-m-d')) }}"
                                                    required>
                                                @error('tanggal_perolehan_unit_awal')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        {{-- Sumber Dana Unit Awal --}}
                                        <div class="row mb-3">
                                            <label for="sumber_dana_unit_awal"
                                                class="col-md-4 col-form-label text-md-end">Sumber Dana Unit</label>
                                            <div class="col-md-7">
                                                <input id="sumber_dana_unit_awal" type="text"
                                                    class="form-control @error('sumber_dana_unit_awal') is-invalid @enderror"
                                                    name="sumber_dana_unit_awal"
                                                    value="{{ old('sumber_dana_unit_awal') }}"
                                                    placeholder="Contoh: Dana BOS 2024">
                                                @error('sumber_dana_unit_awal')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        {{-- No. Dokumen Unit Awal --}}
                                        <div class="row mb-3">
                                            <label for="no_dokumen_unit_awal"
                                                class="col-md-4 col-form-label text-md-end">No. Dokumen Perolehan
                                                Unit</label>
                                            <div class="col-md-7">
                                                <input id="no_dokumen_unit_awal" type="text"
                                                    class="form-control @error('no_dokumen_unit_awal') is-invalid @enderror"
                                                    name="no_dokumen_unit_awal" value="{{ old('no_dokumen_unit_awal') }}"
                                                    placeholder="Contoh: INV/2024/001">
                                                @error('no_dokumen_unit_awal')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        {{-- Deskripsi Unit Awal --}}
                                        <div class="row mb-3">
                                            <label for="deskripsi_unit_awal"
                                                class="col-md-4 col-form-label text-md-end">Deskripsi Tambahan Unit</label>
                                            <div class="col-md-7">
                                                <textarea id="deskripsi_unit_awal" class="form-control @error('deskripsi_unit_awal') is-invalid @enderror"
                                                    name="deskripsi_unit_awal" rows="3" placeholder="Catatan tambahan mengenai unit awal ini...">{{ old('deskripsi_unit_awal') }}</textarea>
                                                @error('deskripsi_unit_awal')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        <ul class="pager wizard twitter-bs-wizard-pager-link">
                                            <li class="previous">
                                                <a href="javascript: void(0);" class="btn btn-primary"><i
                                                        class="bx bx-chevron-left me-1"></i> Kembali</a>
                                            </li>
                                            <li class="next float-end" style="display: none;"> {{-- Diatur oleh JS --}}
                                                <a href="javascript: void(0);" class="btn btn-primary">Lanjut <i
                                                        class="bx bx-chevron-right ms-1"></i></a>
                                            </li>
                                            <li class="submit-step2 float-end" style="display: none;">
                                                {{-- Diatur oleh JS --}}
                                                <button type="submit" class="btn btn-success">Simpan Data <i
                                                        class="bx bx-save ms-1"></i></button>
                                            </li>
                                        </ul>
                                    </div>

                                    {{-- Step 3: Input Nomor Seri (Hanya jika menggunakan_nomor_seri = true) --}}
                                    <div class="tab-pane" id="step3-nomor-seri">
                                        <div class="text-center mb-4">
                                            <h5>Langkah 3: Input Nomor Seri Unit</h5>
                                            <p class="card-title-desc">Masukkan nomor seri unik untuk setiap unit.</p>
                                        </div>
                                        <div id="serial-number-inputs-container" class="mb-3">
                                            <p class="text-muted text-center">Input nomor seri akan muncul di sini setelah
                                                Anda mengisi "Jumlah Unit Awal" di Langkah 2 dan melanjutkan ke langkah ini.
                                            </p>
                                        </div>
                                        <button type="button" class="btn btn-info btn-sm mt-2 mb-3"
                                            id="suggestSerialsButton"><i class="bx bx-bulb me-1"></i> Sarankan Nomor
                                            Seri</button>

                                        @if ($errors->has('serial_numbers') || $errors->has('serial_numbers.*'))
                                            <div class="alert alert-danger py-2">
                                                @if ($errors->has('serial_numbers'))
                                                    <p class="mb-1">{{ $errors->first('serial_numbers') }}</p>
                                                @endif
                                                @foreach ($errors->get('serial_numbers.*') as $key => $message)
                                                    <p class="mb-1">Nomor Seri Unit
                                                        {{ (int) explode('.', $key)[1] + 1 }}: {{ $message[0] }}</p>
                                                @endforeach
                                            </div>
                                        @endif

                                        <ul class="pager wizard twitter-bs-wizard-pager-link mt-3">
                                            <li class="previous">
                                                <a href="javascript: void(0);" class="btn btn-primary"><i
                                                        class="bx bx-chevron-left me-1"></i> Kembali</a>
                                            </li>
                                            <li class="finish float-end"> <button type="submit"
                                                    class="btn btn-success">Simpan Keseluruhan Data <i
                                                        class="bx bx-save ms-1"></i></button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/libs/twitter-bootstrap-wizard/jquery.bootstrap.wizard.min.js') }}"></script>
    <script src="{{ asset('assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    @if (in_array(app()->getLocale(), ['id', 'in']))
        <script src="{{ asset('assets/libs/flatpickr/l10n/id.js') }}"></script>
    @endif
    <script src="{{ asset('assets/libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            var $wizard = $('#barang-wizard');
            var $form = $('#createBarangWizardForm');
            // var choiceInstances = {}; // Tidak lagi diperlukan jika inisialisasi global

            console.log('Document ready. Initializing wizard and components...');

            function initializeChoicesGlobally(selector) {
                console.log('Choices.js: Initializing globally for selector:', selector);
                document.querySelectorAll(selector).forEach(function(element) {
                    if (element.choicesInstance) { // Hancurkan instance lama jika ada
                        try { element.choicesInstance.destroy(); }
                        catch (e) { console.warn("Choices.js: Error destroying existing instance for:", element.id, e); }
                    }
                    try {
                        element.choicesInstance = new Choices(element, {
                            searchEnabled: true, removeItemButton: element.multiple, shouldSort: false,
                            placeholderValue: element.querySelector('option[value=""]') ? element.querySelector('option[value=""]').textContent : 'Pilih...',
                            classNames: { containerOuter: 'choices form-control', containerInner: 'choices__inner', input: 'choices__input', inputCloned: 'choices__input--cloned', list: 'choices__list', listItems: 'choices__list--multiple', listSingle: 'choices__list--single', listDropdown: 'choices__list--dropdown', item: 'choices__item', itemSelectable: 'choices__item--selectable', itemDisabled: 'choices__item--disabled', itemChoice: 'choices__item--choice', placeholder: 'choices__placeholder', group: 'choices__group', groupHeading: 'choices__heading', button: 'choices__button', activeState: 'is-active', focusState: 'is-focused', openState: 'is-open', disabledState: 'is-disabled', highlightedState: 'is-highlighted'}
                        });
                    } catch (e) { console.error("Choices.js: Error initializing on element:", element.id, e); }
                });
            }
            initializeChoicesGlobally('.choices-select');

            flatpickr(".datepicker-input", {
                dateFormat: "Y-m-d",
                defaultDate: "{{ old('tanggal_perolehan_unit_awal', date('Y-m-d')) }}",
                locale: "{{ app()->getLocale() == 'id' ? 'id' : 'default' }}"
            });
            console.log('Flatpickr initialized.');

            function invalidateField(element, message) {
                var $el = $(element);
                $el.addClass('is-invalid');
                if ($el.hasClass('choices-select')) {
                    var $choicesContainer = $el.closest('div').find('.choices');
                    if ($choicesContainer.length) {
                        $choicesContainer.addClass('is-invalid'); // Add to main wrapper
                        $choicesContainer.find('.choices__inner').css('border-color', '#dc3545');
                    }
                }
                var $feedback = $el.siblings('.invalid-feedback, .serial-invalid-feedback').first();
                if (!$feedback.length) $feedback = $el.closest('.col-md-7').find('.invalid-feedback, .serial-invalid-feedback').first();

                if (message && $feedback.length) { $feedback.text(message).show(); }
                else if (message && $el.hasClass('serial-number-input')){ $el.siblings('.serial-invalid-feedback').text(message).show(); }
                return $el[0]; // Return DOM element for focusing
            }

            function clearFieldError(element) {
                var $el = $(element);
                $el.removeClass('is-invalid');
                if ($el.hasClass('choices-select')) {
                     var $choicesContainer = $el.closest('div').find('.choices');
                     if ($choicesContainer.length) {
                        $choicesContainer.removeClass('is-invalid');
                        $choicesContainer.find('.choices__inner').css('border-color', '');
                    }
                }
                var $feedback = $el.siblings('.invalid-feedback, .serial-invalid-feedback').first();
                 if (!$feedback.length) $feedback = $el.closest('.col-md-7').find('.invalid-feedback, .serial-invalid-feedback').first();

                if ($feedback.length) { $feedback.text('').hide(); }
                else if ($el.hasClass('serial-number-input')){ $el.siblings('.serial-invalid-feedback').text('').hide(); }
            }

            window.validateClientSideStep = function(stepNumber) {
                var isValid = true;
                var firstInvalidElement = null;
                var paneId;

                if (stepNumber === 1) paneId = '#step1-jenis-barang';
                else if (stepNumber === 2) paneId = '#step2-unit-awal';
                else if (stepNumber === 3) paneId = '#step3-nomor-seri';
                else { console.error('validateClientSideStep: Invalid stepNumber:', stepNumber); return false; }

                var $activePane = $(paneId);
                if (!$activePane.length) { console.error(`validateClientSideStep: Pane with ID '${paneId}' not found.`); return false; }
                console.log(`validateClientSideStep: Validating pane '${$activePane.attr('id')}' for step ${stepNumber}`);

                $activePane.find('[required]').each(function() { clearFieldError(this); });

                $activePane.find('[required]').each(function() {
                    var $field = $(this);
                    var fieldName = $field.attr('name') || $field.attr('id');
                    var fieldValue = $field.val();
                    var isInvalid = false;

                    if ($field.is(':radio')) {
                        var radioName = $field.attr('name');
                        if ($(`input[name="${radioName}"]:checked`).length === 0) isInvalid = true;
                        // console.log(`  Radio: ${fieldName}, Checked: ${!isInvalid}`);
                    } else if ($field.is('select')) {
                        if (fieldValue === "" || fieldValue === null) isInvalid = true;
                        // console.log(`  Select: ${fieldName}, Value: '${fieldValue}', Valid: ${!isInvalid}`);
                    } else { // input text, textarea, number
                        if (String(fieldValue).trim() === "") isInvalid = true;
                        // console.log(`  Input/Textarea: ${fieldName}, Value: '${String(fieldValue).trim()}', Valid: ${!isInvalid}`);
                    }

                    if (isInvalid) {
                        console.warn(`  INVALID: ${fieldName} - ${$field.is(':radio') ? 'Not selected' : ($field.is('select') ? 'Not selected' : 'Is empty')}`);
                        var currentInvalid = invalidateField(this, $field.is(':radio') || $field.is('select') ? 'Pilihan ini wajib dibuat.' : 'Field ini wajib diisi.');
                        if (!firstInvalidElement) firstInvalidElement = currentInvalid;
                        isValid = false;
                    }
                });

                if (stepNumber === 2) { // Validasi tambahan untuk Step 2
                    let jumlahUnit = parseInt($('#jumlah_unit_awal').val());
                    if (isNaN(jumlahUnit) || jumlahUnit < 1) {
                        if(!firstInvalidElement) firstInvalidElement = invalidateField('#jumlah_unit_awal', 'Jumlah unit minimal 1.'); else invalidateField('#jumlah_unit_awal', 'Jumlah unit minimal 1.'); isValid = false;
                    }
                    let ruanganAwal = $('#id_ruangan_awal').val();
                    let pemegangAwal = $('#id_pemegang_personal_awal').val();
                    if (!ruanganAwal && !pemegangAwal) {
                        if(!firstInvalidElement) firstInvalidElement = invalidateField('#id_ruangan_awal', 'Pilih ruangan ATAU pemegang.'); else invalidateField('#id_ruangan_awal', 'Pilih ruangan ATAU pemegang.');
                        invalidateField('#id_pemegang_personal_awal', 'Pilih pemegang ATAU ruangan.'); isValid = false;
                    } else if (ruanganAwal && pemegangAwal) {
                        if(!firstInvalidElement) firstInvalidElement = invalidateField('#id_ruangan_awal', 'Tidak boleh bersamaan.'); else invalidateField('#id_ruangan_awal', 'Tidak boleh bersamaan.');
                        invalidateField('#id_pemegang_personal_awal', 'Tidak boleh bersamaan.'); isValid = false;
                    }
                } else if (stepNumber === 3 && $('input[name="menggunakan_nomor_seri"]:checked').val() === '1') { // Validasi Step 3
                    var serials = []; var hasDuplicates = false;
                    var $serialInputs = $activePane.find('.serial-number-input');
                    if ($serialInputs.length === 0 && parseInt($('#jumlah_unit_awal').val()) > 0) {
                        alert('Input nomor seri belum muncul. Pastikan Jumlah Unit Awal di Langkah 2 sudah benar.'); isValid = false;
                    }
                    $serialInputs.filter('[required]').each(function() { // Hanya validasi yang required
                        var val = $(this).val().trim();
                        if (val === "") { if(!firstInvalidElement) firstInvalidElement = invalidateField(this, 'Nomor seri wajib diisi.'); else invalidateField(this, 'Nomor seri wajib diisi.'); isValid = false; }
                        else { if (serials.includes(val)) { hasDuplicates = true; } serials.push(val); }
                    });
                    if (hasDuplicates) {
                        $serialInputs.each(function() {
                            var currentVal = $(this).val().trim();
                            if (currentVal !== "" && serials.filter(s => s === currentVal).length > 1) {
                                if(!firstInvalidElement) firstInvalidElement = invalidateField(this, 'Nomor seri duplikat.'); else invalidateField(this, 'Nomor seri duplikat.'); isValid = false;
                            }
                        });
                    }
                }

                if (!isValid && firstInvalidElement) {
                    console.warn(`validateClientSideStep: Validation failed for step ${stepNumber}. Focusing on:`, $(firstInvalidElement).attr('id') || $(firstInvalidElement).attr('name'));
                    $(firstInvalidElement).focus();
                }
                console.log(`validateClientSideStep: Final isValid for step ${stepNumber}:`, isValid);
                return isValid;
            };

            function updateWizardUI(currentIndex) {
                console.log('updateWizardUI called for index:', currentIndex);
                var useSerial = $('input[name="menggunakan_nomor_seri"]:checked').val() === '1';
                var totalSteps = useSerial ? 3 : 2;

                var $progressBar = $wizard.find('.progress-bar');
                var $percent = totalSteps > 0 ? (((currentIndex + 1) / totalSteps) * 100) : (currentIndex === 0 ? 50 : 0); // Handle progress if index is -1 initially
                 if (currentIndex === -1 && totalSteps === 2) $percent = 0; // Jika hanya 2 step dan index -1, mulai dari 0
                 else if (currentIndex === -1 && totalSteps === 3) $percent = 0; // Jika 3 step dan index -1, mulai dari 0


                $progressBar.css({ width: $percent + '%' }).attr('aria-valuenow', $percent);
                // console.log('updateWizardUI: Progress bar to', $percent + '%. Total steps:', totalSteps, 'Current Index:', currentIndex);

                var $step3NavItem = $wizard.find('ul.twitter-bs-wizard-nav li.step3-nav');
                if (useSerial) { $step3NavItem.show(); } else { $step3NavItem.hide(); }

                var $step2Pager = $('#step2-unit-awal').find('.pager.wizard');
                var $step2NextLi = $step2Pager.find('li.next');
                var $step2SubmitLi = $step2Pager.find('li.submit-step2');

                if (currentIndex === 1) { // Jika di Step 2
                    if (useSerial) { $step2NextLi.show(); $step2SubmitLi.hide(); }
                    else { $step2NextLi.hide(); $step2SubmitLi.show(); }
                } else { // Untuk step lain, pastikan state default (misal, di step 1, tombol next step 2 tidak relevan)
                    $step2NextLi.hide(); $step2SubmitLi.hide();
                }
                 // Selalu tampilkan tombol "Lanjut" di step 1
                if (currentIndex === 0) {
                    $('#step1-jenis-barang').find('li.next').show();
                }
            }

            $wizard.bootstrapWizard({
                tabClass: 'nav nav-pills nav-justified',
                nextSelector: '.next > a',
                previousSelector: '.previous > a',
                finishSelector: '.finish > button', // Digunakan untuk onFinish
                onTabShow: function(tab, navigation, index) {
                    // if (index === -1 && !firstLoadDone) { console.log('onTabShow: Index -1 on first load, handled by show(0)'); firstLoadDone = true; return; }
                    console.log('onTabShow: Tab shown. Current index:', index);
                    updateWizardUI(index);
                    if ($('input[name="menggunakan_nomor_seri"]:checked').val() === '1' && index === 2) {
                        generateSerialNumberInputs();
                    }
                },
                onNext: function(tab, navigation, index) {
                    console.log('onNext called. Leaving step (index):', index);
                    var stepToValidate = index + 1;
                    if (!window.validateClientSideStep(stepToValidate)) {
                        console.error(`onNext: Validation failed for step ${stepToValidate}. Cannot proceed.`);
                        return false;
                    }
                    return true;
                },
                onTabClick: function(tab, navigation, curIndex, clickedIndex) {
                    console.log(`onTabClick: Current index: ${curIndex}, Clicked index: ${clickedIndex}`);
                    if (clickedIndex < curIndex) { return true; }
                    if (clickedIndex > curIndex) {
                        for (let i = curIndex; i < clickedIndex; i++) {
                            let stepToValidate = i + 1;
                            if (!window.validateClientSideStep(stepToValidate)) {
                                console.error(`onTabClick: Validation failed for intermediate step ${stepToValidate}. Jump to ${clickedIndex} prevented.`);
                                $wizard.bootstrapWizard('show', i);
                                return false;
                            }
                        }
                    }
                    if ($('input[name="menggunakan_nomor_seri"]:checked').val() === '0' && clickedIndex === 2) {
                        console.warn('onTabClick: Prevented click to Step 3 as not using serial numbers.');
                        return false;
                    }
                    return true;
                },
                onFinish: function(tab, navigation, index) {
                    console.log('onFinish called at index:', index, ' Attempting to submit form.');
                    // Tombol .finish akan men-trigger event submit form, jadi validasi akhir ada di $form.on('submit')
                    // $form.trigger('submit'); // Ini bisa menyebabkan loop jika onFinish dipanggil dari tombol submit standar
                }
            });
            console.log('Twitter Bootstrap Wizard initialized.');

            // Event handler untuk tombol submit di Step 2 (jika tidak pakai nomor seri)
            // dan tombol finish di Step 3
            // Menggunakan class .btn-submit-form untuk kedua tombol agar bisa ditarget bareng
            $form.find('.btn-submit-form').on('click', function() {
                console.log('Custom submit button (.btn-submit-form or .finish) clicked.');
                $form.trigger('submit'); // Trigger submit standar form
            });


            $('input[name="menggunakan_nomor_seri"]').on('change', function() {
                var isUsingSerial = $(this).val() === '1';
                console.log('Radio "menggunakan_nomor_seri" changed. Is using serial:', isUsingSerial);
                var currentWizardIndex = $wizard.bootstrapWizard('currentIndex');
                updateWizardUI(currentWizardIndex);
                if (!isUsingSerial && currentWizardIndex === 2) {
                    console.log('Navigating back to step 2 (index 1) as step 3 is now hidden.');
                    $wizard.bootstrapWizard('show', 1);
                } else {
                     // Paksa refresh UI untuk tab saat ini
                    var $activeTabLink = $wizard.find('ul.twitter-bs-wizard-nav li').eq(currentWizardIndex).find('a');
                    if ($activeTabLink.length) {
                        var $activePane = $($activeTabLink.attr('href'));
                        if ($activePane.length && typeof $wizard.data('bootstrapWizard').onTabShow === 'function') {
                             $wizard.data('bootstrapWizard').onTabShow($activePane, $wizard.find('ul.twitter-bs-wizard-nav'), currentWizardIndex);
                        }
                    }
                }
            });

            var initialIndex = $wizard.find('ul.twitter-bs-wizard-nav li a.active').parent().index();
            if (initialIndex === -1) initialIndex = 0;
            $wizard.bootstrapWizard('show', initialIndex);
            console.log(`Initial wizard display set to tab index: ${initialIndex}.`);
            // Panggil updateWizardUI setelah wizard benar-benar ditampilkan untuk state awal pager.
             setTimeout(function() {
                console.log("Delayed call to updateWizardUI after initial show.");
                updateWizardUI($wizard.bootstrapWizard('currentIndex'));
            }, 100); // Delay kecil untuk memastikan onTabShow selesai


            function generateSerialNumberInputs() {
                var jumlahUnit = parseInt($('#jumlah_unit_awal').val()) || 0;
                console.log('generateSerialNumberInputs: jumlahUnit =', jumlahUnit);
                var container = $('#serial-number-inputs-container'); container.empty();
                if (jumlahUnit > 0) {
                    var oldSerials = {!! json_encode(old('serial_numbers', [])) !!}; var errors = {!! json_encode($errors->toArray()) !!};
                    for (var i = 0; i < jumlahUnit; i++) {
                        var serialValue = oldSerials[i] || ''; var errorKeyLaravel = `serial_numbers.${i}`; var isInvalidLaravel = errors.hasOwnProperty(errorKeyLaravel); var errorMessageLaravel = isInvalidLaravel ? errors[errorKeyLaravel][0] : '';
                        var inputHtml = `<div class="row mb-2 align-items-center"><label for="serial_number_${i}" class="col-md-4 col-form-label text-md-end">Nomor Seri Unit ${i + 1} <span class="text-danger">*</span></label><div class="col-md-7"><input id="serial_number_${i}" type="text" class="form-control serial-number-input ${isInvalidLaravel ? 'is-invalid' : ''}" name="serial_numbers[${i}]" value="${serialValue}" placeholder="Masukkan nomor seri unik" required><div class="serial-invalid-feedback invalid-feedback">${errorMessageLaravel}</div></div></div>`;
                        container.append(inputHtml);
                    }
                } else { container.html('<p class="text-muted text-center">Isi "Jumlah Unit Awal" di Langkah 2 (minimal 1) dan klik Lanjut.</p>');}
            }
            $('#jumlah_unit_awal').on('input change', function() {
                if ($('input[name="menggunakan_nomor_seri"]:checked').val() === '1' && $wizard.bootstrapWizard('currentIndex') === 2) { generateSerialNumberInputs(); }
            });
            $('#suggestSerialsButton').on('click', function() {
                var kodeBarangInput = $('#kode_barang').val(); var jumlahUnit = parseInt($('#jumlah_unit_awal').val());
                if (!kodeBarangInput) { alert('Mohon isi Kode Barang di Langkah 1.'); $wizard.bootstrapWizard('show', 0); $('#kode_barang').focus(); return; }
                if (!jumlahUnit || jumlahUnit <= 0) { alert('Mohon isi Jumlah Unit Awal yang valid.'); $wizard.bootstrapWizard('show', 1); $('#jumlah_unit_awal').focus(); return; }
                $.ajax({ url: "{{ route('barang.suggest-serials-for-new') }}", type: 'GET', data: { kode_barang_input: kodeBarangInput, jumlah_unit: jumlahUnit },
                    success: function(response) { var serialInputs = $('.serial-number-input'); if (response && Array.isArray(response) && response.length === jumlahUnit) { serialInputs.each(function(index) { if (!$(this).val() && response[index]) { $(this).val(response[index]); clearFieldError(this); } }); } else { generateClientSideSuggestions(kodeBarangInput, jumlahUnit, serialInputs); }},
                    error: function() { alert('Gagal dapat saran dari server.'); generateClientSideSuggestions(kodeBarangInput, jumlahUnit, $('.serial-number-input')); }
                });
            });
            function generateClientSideSuggestions(kodeBarang, jumlah, inputs) {
                var timestamp = Date.now().toString().slice(-5); inputs.each(function(index) { if (!$(this).val()) { $(this).val((kodeBarang.toUpperCase().replace(/[^A-Z0-9\-]/g, '') || 'BRG') + '-RS' + timestamp + '-' + String(index + 1).padStart(3, '0')); } });
            }

            $form.on('submit', function(e) {
                console.log('Form submit event triggered.');
                var useSerial = $('input[name="menggunakan_nomor_seri"]:checked').val() === '1';
                var finalValidationOk = true;

                // Hapus semua error class 'is-invalid' sebelum validasi ulang semua langkah
                $form.find('.is-invalid').removeClass('is-invalid');
                $form.find('.choices.is-invalid').removeClass('is-invalid'); // For choices main container
                $form.find('.choices__inner').css('border-color', ''); // Reset choices border
                $form.find('.invalid-feedback, .serial-invalid-feedback').text('').hide();

                if (!window.validateClientSideStep(1)) {
                    finalValidationOk = false; $wizard.bootstrapWizard('show', 0);
                    console.error('Form submit: Validation failed for Step 1.');
                }
                if (finalValidationOk && !window.validateClientSideStep(2)) {
                    finalValidationOk = false; $wizard.bootstrapWizard('show', 1);
                    console.error('Form submit: Validation failed for Step 2.');
                }
                if (useSerial && finalValidationOk && !window.validateClientSideStep(3)) {
                    finalValidationOk = false; $wizard.bootstrapWizard('show', 2);
                    console.error('Form submit: Validation failed for Step 3.');
                }

                if (!finalValidationOk) {
                    console.error('Form submit: Final validation check failed. Preventing submission.');
                    e.preventDefault();
                } else {
                    console.log('Form submit: All client-side validations passed. Submitting form to backend.');
                    // Biarkan form submit secara normal jika semua validasi client-side lolos
                }
            });

            console.log('All event listeners attached and initial UI setup complete.');
        });
    </script>
@endpush