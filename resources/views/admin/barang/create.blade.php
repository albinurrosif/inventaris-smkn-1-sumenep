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
                            <li class="breadcrumb-item"><a href="{{ route('admin.barang.index') }}">Jenis Barang</a></li>
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
                        <form id="createBarangWizardForm" method="POST" action="{{ route('admin.barang.store') }}">
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
                                            <li class="float-start"><a href="{{ route('admin.barang.index') }}"
                                                    class="btn btn-light">Batal</a></li>
                                            <li class="next float-end"><a href="javascript:void(0);"
                                                    class="btn btn-primary">Lanjut <i
                                                        class="bx bx-chevron-right ms-1"></i></a></li>
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
                                            <li class="previous"><a href="javascript:void(0);" class="btn btn-primary"><i
                                                        class="bx bx-chevron-left me-1"></i> Kembali</a></li>
                                            <li class="next float-end"><a href="javascript:void(0);"
                                                    class="btn btn-primary">Lanjut <i
                                                        class="bx bx-chevron-right ms-1"></i></a></li>
                                            <li class="submit-step2" style="display: none;"><button type="submit"
                                                    class="btn btn-success">Simpan Data <i
                                                        class="bx bx-save ms-1"></i></button></li>
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
                                            <li class="previous"><a href="javascript:void(0);" class="btn btn-primary"><i
                                                        class="bx bx-chevron-left me-1"></i> Kembali</a></li>
                                            <li class="finish float-end"><a href="javascript:void(0);"
                                                    class="btn btn-success">Simpan Keseluruhan Data <i
                                                        class="bx bx-save ms-1"></i></a></li>
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
    {{-- Aset JS --}}
   <script src="{{ asset('assets/libs/twitter-bootstrap-wizard/jquery.bootstrap.wizard.min.js') }}"></script>
    <script src="{{ asset('assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    @if (in_array(app()->getLocale(), ['id', 'in']))
        <script src="{{ asset('assets/libs/flatpickr/l10n/id.js') }}"></script>
    @endif
    <script src="{{ asset('assets/libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>

    <script>
        $(document).ready(function() {
            // 1. Inisialisasi Komponen
            var choicesInstances = [];
            $('.choices-select').each(function() {
                var choiceInstance = new Choices(this, {
                    searchEnabled: true,
                    shouldSort: false,
                    removeItemButton: true,
                    allowHTML: false
                });
                choicesInstances.push(choiceInstance);
            });

            flatpickr(".datepicker-input", {
                dateFormat: "Y-m-d",
                maxDate: "today",
                locale: "{{ app()->getLocale() == 'id' ? 'id' : 'default' }}"
            });

            // 2. Logika Wizard - PERBAIKAN SEDERHANA
            var $wizard = $('#barang-wizard');
            var $form = $('#createBarangWizardForm');

            $wizard.bootstrapWizard({
                'nextSelector': '.pager li.next',
                'previousSelector': '.pager li.previous',
                'firstSelector': '.pager li.first',
                'lastSelector': '.pager li.last',

                onTabShow: function(tab, navigation, index) {
                    var useSerial = $('input[name="menggunakan_nomor_seri"]:checked').val() === '1';
                    var totalTabs = useSerial ? 3 : 2;
                    var currentTab = index + 1;
                    var $percent = (currentTab / totalTabs) * 100;

                    $wizard.find('.progress-bar').css({
                        width: $percent + '%'
                    });

                    // Reset dan atur visibility tombol
                    $('.pager li.next').show();
                    $('.pager li.finish').hide();
                    $('.pager li.submit-step2').hide();
                    $('.pager li.previous').toggle(currentTab > 1);

                    // Atur tombol berdasarkan kondisi
                    if (currentTab === totalTabs) {
                        $('.pager li.next').hide();
                        $('.pager li.finish').show();
                    } else if (currentTab === 2 && !useSerial) {
                        $('.pager li.next').hide();
                        $('.pager li.submit-step2').show();
                    }
                },

                onNext: function(tab, navigation, index) {
                    var $valid = true;
                    var $currentTab = $wizard.find('.tab-pane').eq(index);

                    // Validasi field required
                    $currentTab.find(':input[required]').each(function() {
                        if (!this.checkValidity()) {
                            $valid = false;
                            $(this).addClass('is-invalid');
                            if ($(this).is('select')) {
                                $(this).closest('.choices').addClass('is-invalid');
                            }
                        } else {
                            $(this).removeClass('is-invalid');
                            if ($(this).is('select')) {
                                $(this).closest('.choices').removeClass('is-invalid');
                            }
                        }
                    });

                    // Validasi khusus untuk step 2: harus ada ruangan ATAU pemegang personal
                    if (index === 1) {
                        var ruangan = $('#id_ruangan_awal').val();
                        var pemegang = $('#id_pemegang_personal_awal').val();

                        if (!ruangan && !pemegang) {
                            $valid = false;
                            $('#id_ruangan_awal').closest('.choices').addClass('is-invalid');
                            $('#id_pemegang_personal_awal').closest('.choices').addClass('is-invalid');
                            alert('Pilih salah satu: Ruangan atau Pemegang Personal');
                        }
                    }

                    if (!$valid) {
                        return false;
                    }

                    // Generate serial inputs jika menuju step 3
                    if (index === 1 && $('input[name="menggunakan_nomor_seri"]:checked').val() ===
                        '1') {
                        generateSerialNumberInputs();
                    }

                    return true;
                },

                onTabClick: function(tab, navigation, index, clickedIndex) {
                    return false;
                }
            });

            // Handler untuk tombol finish
            $(document).on('click', '.pager li.finish a', function(e) {
                e.preventDefault();

                // Validasi final untuk step 3 jika ada
                var useSerial = $('input[name="menggunakan_nomor_seri"]:checked').val() === '1';
                if (useSerial) {
                    var allValid = true;
                    $('.serial-number-input').each(function() {
                        if (!this.checkValidity()) {
                            allValid = false;
                            $(this).addClass('is-invalid');
                        }
                    });

                    if (!allValid) {
                        alert('Pastikan semua nomor seri sudah diisi dan valid');
                        return false;
                    }
                }

                $form.submit();
            });

            // 3. Event Handlers
            $('input[name="menggunakan_nomor_seri"]').on('change', function() {
                manageStep3Visibility();
            });

            $('#jumlah_unit_awal').on('change', function() {
                if ($('input[name="menggunakan_nomor_seri"]:checked').val() === '1') {
                    generateSerialNumberInputs();
                }
            });

            // Clear validation on input change
            $(document).on('input change', ':input', function() {
                $(this).removeClass('is-invalid');
                if ($(this).is('select')) {
                    $(this).closest('.choices').removeClass('is-invalid');
                }
            });

            // 4. Fungsi Helper
            function manageStep3Visibility() {
                var useSerial = $('input[name="menggunakan_nomor_seri"]:checked').val() === '1';
                var $step3NavItem = $('.step3-nav');
                if (useSerial) {
                    $step3NavItem.show();
                } else {
                    $step3NavItem.hide();
                }
            }

            function generateSerialNumberInputs() {
                var jumlahUnit = parseInt($('#jumlah_unit_awal').val()) || 0;
                var container = $('#serial-number-inputs-container');
                container.empty();

                if (jumlahUnit > 0) {
                    var oldSerials = {!! json_encode(old('serial_numbers', [])) !!};
                    var errors = {!! json_encode($errors->toArray()) !!};

                    for (var i = 0; i < jumlahUnit; i++) {
                        var serialValue = oldSerials[i] || '';
                        var errorKeyLaravel = `serial_numbers.${i}`;
                        var isInvalidLaravel = errors.hasOwnProperty(errorKeyLaravel);
                        var errorMessageLaravel = isInvalidLaravel ? errors[errorKeyLaravel][0] : '';

                        var inputHtml = `
                    <div class="row mb-2 align-items-center">
                        <label for="serial_number_${i}" class="col-md-4 col-form-label text-md-end">
                            Nomor Seri Unit ${i + 1} <span class="text-danger">*</span>
                        </label>
                        <div class="col-md-7">
                            <input id="serial_number_${i}" type="text" 
                                class="form-control serial-number-input ${isInvalidLaravel ? 'is-invalid' : ''}" 
                                name="serial_numbers[${i}]" value="${serialValue}" 
                                placeholder="Masukkan nomor seri unik" required>
                            <div class="serial-invalid-feedback invalid-feedback d-block">${errorMessageLaravel}</div>
                        </div>
                    </div>`;
                        container.append(inputHtml);
                    }
                }
            }

            // Event listener untuk tombol "Sarankan Nomor Seri"
            $('#suggestSerialsButton').on('click', function() {
                var kodeBarangInput = $('#kode_barang').val();
                var jumlahUnit = parseInt($('#jumlah_unit_awal').val());

                if (!kodeBarangInput) {
                    alert('Mohon isi Kode Barang di Langkah 1.');
                    $wizard.bootstrapWizard('show', 0);
                    $('#kode_barang').focus();
                    return;
                }
                if (!jumlahUnit || jumlahUnit <= 0) {
                    alert('Mohon isi Jumlah Unit Awal yang valid di Langkah 2.');
                    $wizard.bootstrapWizard('show', 1);
                    $('#jumlah_unit_awal').focus();
                    return;
                }

                $.ajax({
                    url: "{{ route('admin.barang.suggest-serials-for-new') }}",
                    type: 'GET',
                    data: {
                        kode_barang_input: kodeBarangInput,
                        jumlah_unit: jumlahUnit
                    },
                    success: function(response) {
                        var serialInputs = $('.serial-number-input');
                        if (response && Array.isArray(response) && response.length ===
                            jumlahUnit) {
                            serialInputs.each(function(index) {
                                if (response[index]) {
                                    $(this).val(response[index]);
                                }
                            });
                        }
                    },
                    error: function() {
                        alert('Gagal mendapatkan saran nomor seri dari server.');
                    }
                });
            });

            // Event listener untuk tombol submit khusus di Step 2
            $(document).on('click', '.submit-step2 button', function(e) {
                e.preventDefault();
                $form.submit();
            });

            // Inisialisasi awal
            manageStep3Visibility();

            // Handle error state dari backend
            @if ($errors->any())
                if ('{{ old('menggunakan_nomor_seri') }}' === '1') {
                    generateSerialNumberInputs();
                    @if ($errors->has('serial_numbers.*'))
                        setTimeout(function() {
                            $wizard.bootstrapWizard('show', 2);
                        }, 200);
                    @elseif ($errors->has('jumlah_unit_awal') || $errors->has('id_ruangan_awal') || $errors->has('id_pemegang_personal_awal'))
                        setTimeout(function() {
                            $wizard.bootstrapWizard('show', 1);
                        }, 200);
                    @endif
                }
            @endif
        });
    </script>
@endpush
