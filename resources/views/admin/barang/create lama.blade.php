@extends('layouts.app')

@php
    // Menentukan mode berdasarkan variabel yang mungkin dikirim dari controller
    // Untuk 'create' murni, $barang tidak akan ada.
    // Untuk 'edit' biasa, $barang ada dan $editMode = true.
    // Untuk 'wizard edit step 1', $barang (dari sesi/temp) ada dan $wizardStep = 1.
    $barangInstance = $barang ?? null; // Gunakan $barangInstance untuk merujuk ke objek barang jika ada
    $isEdit = isset($editMode) && $editMode === true; // Mode edit standar (bukan wizard)
    $isWizardCreate = !isset($barangInstance) && !isset($editMode); // Mode create baru
    $isWizardEditStep1 = isset($wizardStep) && $wizardStep === 1 && isset($barangInstance); // Mode edit step 1 dari wizard

    $formAction = route('barang.store'); // Default untuk create baru
    $formMethod = 'POST';

    if ($isWizardEditStep1) {
        // Jika kembali ke step 1 dari step 2 untuk mengedit data sesi/temp
        // Anda mungkin perlu route khusus untuk update data sesi/temp ini
        // atau biarkan controller create menangani request PUT dengan data sesi.
        // Untuk sekarang, kita asumsikan akan ada route 'barang.update-step1'
        // Jika tidak, ini bisa tetap POST ke barang.store dengan flag khusus.
        // $formAction = route('barang.update-step1', $barangInstance->id); // Contoh jika ada route khusus
        // Untuk kesederhanaan, kita bisa tetap POST ke barang.store dan controller yang handle
        // Atau, jika ini adalah edit dari barang yang sudah dibuat di DB (saat kembali dari step 2)
        // maka actionnya adalah PUT ke barang.update
        // Logika ini perlu disesuaikan dengan bagaimana Anda menangani "kembali ke step 1"
        // Untuk saat ini, jika $isWizardEditStep1, kita anggap ini adalah bagian dari proses 'store' yang diulang/diedit
        // Jadi, action tetap ke barang.store, tapi controller store perlu tahu ini bukan create murni.
        // Atau, lebih baik, link "Kembali ke Step 1" di Step 2 mengarah ke barang.create dengan parameter
        // yang menandakan "isi ulang dari sesi".
        // Untuk form ini, jika $isWizardEditStep1, anggap $barangInstance sudah ada (dari sesi/temp)
        // dan kita akan submit ke route yang sama dengan create, controller akan handle.
        // Jika $barangInstance sudah punya ID (sudah di-create di DB pada store awal), maka ini jadi PUT.
        if ($barangInstance && $barangInstance->exists) {
            // Jika barang sudah ada di DB (misal kembali dari step 2)
            $formAction = route('barang.update', $barangInstance->id); // Atau route update-step1 khusus
            $formMethod = 'PUT';
        }
    } elseif ($isEdit) {
        // Mode edit standar
        $formAction = route('barang.update', $barangInstance->id);
        $formMethod = 'PUT';
    }

    $pageTitle = 'Tambah Jenis Barang Baru';
    $breadcrumbTitle = 'Tambah Barang (Step 1)';
    if ($isWizardEditStep1) {
        $pageTitle = 'Edit Data Barang - Step 1';
        $breadcrumbTitle = 'Edit Barang (Step 1)';
    } elseif ($isEdit) {
        $pageTitle = 'Edit Jenis Barang';
        $breadcrumbTitle = 'Edit Barang';
    }

@endphp

@section('title', $pageTitle)

@section('styles')
    <style>
        .form-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            padding-bottom: 0.5rem;
            margin-top: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .form-subsection-title {
            font-size: 0.95rem;
            font-weight: 500;
            color: #495057;
            margin-top: 1.25rem;
            margin-bottom: 1rem;
        }

        .twitter-bs-wizard-nav .nav-link.active .step-icon,
        .twitter-bs-wizard-nav .nav-link.active .step-title {
            color: var(--bs-primary, #556ee6);
        }

        .twitter-bs-wizard-nav .nav-link:not(.active) {
            color: #adb5bd;
            cursor: default;
        }

        .twitter-bs-wizard-nav .nav-link:not(.active) .step-icon,
        .twitter-bs-wizard-nav .nav-link:not(.active) .step-title {
            color: #adb5bd;
        }

        .twitter-bs-wizard-nav .nav-link .step-icon {
            font-size: 1.5rem;
        }

        .twitter-bs-wizard-nav .nav-link .step-title {
            display: block;
            margin-top: .25rem;
            font-size: .875rem;
        }

        .swal2-html-container ul {
            text-align: left !important;
            padding-left: 1.5rem !important;
        }
    </style>
@endsection

@section('content')
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let errorMessages = '<ul class="list-unstyled" style="margin-bottom:0;">';
                @foreach ($errors->all() as $error)
                    errorMessages +=
                        `<li><i class="mdi mdi-alert-circle-outline me-1 text-danger"></i>{{ $error }}</li>`;
                @endforeach
                errorMessages += '</ul>';
                Swal.fire({
                    icon: 'error',
                    title: 'Oops! Ada Kesalahan Validasi',
                    html: errorMessages,
                    confirmButtonText: 'Baik, Saya Mengerti',
                    customClass: {
                        htmlContainer: 'text-start'
                    }
                });
            });
        </script>
    @endif

    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">{{ $pageTitle }}</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('barang.index') }}">Daftar Jenis Barang</a></li>
                            <li class="breadcrumb-item active">{{ $breadcrumbTitle }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <div class="twitter-bs-wizard card-header-tab">
                            <ul class="twitter-bs-wizard-nav nav nav-pills nav-justified">
                                <li class="nav-item" style="width: 50%;">
                                    <a class="nav-link {{ $isWizardCreate || $isWizardEditStep1 ? 'active' : '' }}"
                                        href="javascript:void(0);" role="tab">
                                        <div class="step-icon" data-bs-toggle="tooltip"
                                            title="Data Barang Induk & Unit Awal"><i class="bx bx-list-ul"></i></div>
                                        <span class="step-title">Step 1: Info Barang & Unit</span>
                                    </a>
                                </li>
                                <li class="nav-item" style="width: 50%;">
                                    <a class="nav-link {{ $isWizardCreate || $isWizardEditStep1 ? '' : 'disabled' }}"
                                        href="javascript:void(0);" role="tab"
                                        style="{{ $isWizardCreate || $isWizardEditStep1 ? 'pointer-events: none; opacity: 0.65;' : '' }}">
                                        <div class="step-icon" data-bs-toggle="tooltip"
                                            title="Input Nomor Seri (Jika Perlu)"><i class="bx bx-barcode"></i></div>
                                        <span class="step-title">Step 2: Nomor Seri</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="tab-content twitter-bs-wizard-tab-content mt-3">
                            <div class="tab-pane fade show active" id="step1-data-barang" role="tabpanel">
                                <form action="{{ $formAction }}" method="POST" id="formTambahBarangStep1">
                                    @csrf
                                    @if ($formMethod === 'PUT')
                                        @method('PUT')
                                    @endif
                                    @if ($isWizardEditStep1 || ($isEdit && isset($barangInstance) && $barangInstance->exists))
                                        {{-- Jika ini adalah edit step 1 dari wizard, atau edit barang yang sudah ada, --}}
                                        {{-- kita mungkin perlu mengirimkan ID barang jika belum ada di URL action --}}
                                        {{-- Namun, dengan route model binding, ID sudah ada di URL --}}
                                    @endif

                                    {{-- SEKSI DATA INDUK BARANG --}}
                                    <h6 class="form-section-title mt-2">A. Informasi Jenis Barang (Induk)</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="nama_barang" class="form-label">Nama Jenis Barang <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="nama_barang" id="nama_barang"
                                                class="form-control @error('nama_barang') is-invalid @enderror"
                                                value="{{ old('nama_barang', $barangInstance->nama_barang ?? '') }}"
                                                required placeholder="Contoh: Laptop, Proyektor, Meja Siswa">
                                            @error('nama_barang')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="kode_barang" class="form-label">Kode Jenis Barang <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="kode_barang" id="kode_barang"
                                                class="form-control @error('kode_barang') is-invalid @enderror"
                                                value="{{ old('kode_barang', $barangInstance->kode_barang ?? '') }}"
                                                required placeholder="Contoh: LPT-ACR-01 (Harus Unik)"
                                                {{ ($isEdit || $isWizardEditStep1) && isset($barangInstance) && $barangInstance->qrCodes()->exists() ? 'readonly' : '' }}>
                                            @if (($isEdit || $isWizardEditStep1) && isset($barangInstance) && $barangInstance->qrCodes()->exists())
                                                <small class="text-muted">Kode barang tidak dapat diubah karena sudah ada
                                                    unit terdaftar.</small>
                                            @endif
                                            @error('kode_barang')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="id_kategori" class="form-label">Kategori Barang <span
                                                    class="text-danger">*</span></label>
                                            <select name="id_kategori" id="id_kategori"
                                                class="form-select @error('id_kategori') is-invalid @enderror" required>
                                                <option value="">-- Pilih Kategori --</option>
                                                @foreach ($kategoriList as $kategori)
                                                    <option value="{{ $kategori->id }}"
                                                        {{ old('id_kategori', $barangInstance->id_kategori ?? '') == $kategori->id ? 'selected' : '' }}>
                                                        {{ $kategori->nama_kategori }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('id_kategori')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="merk_model" class="form-label">Merk / Model</label>
                                            <input type="text" name="merk_model" id="merk_model"
                                                class="form-control @error('merk_model') is-invalid @enderror"
                                                value="{{ old('merk_model', $barangInstance->merk_model ?? '') }}"
                                                placeholder="Contoh: Acer Aspire 5, Epson EB-X500">
                                            @error('merk_model')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="ukuran" class="form-label">Ukuran/Dimensi</label>
                                            <input type="text" name="ukuran" id="ukuran"
                                                class="form-control @error('ukuran') is-invalid @enderror"
                                                value="{{ old('ukuran', $barangInstance->ukuran ?? '') }}"
                                                placeholder="Contoh: 14 inch, 120x60x75 cm">
                                            @error('ukuran')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="bahan" class="form-label">Bahan Utama</label>
                                            <input type="text" name="bahan" id="bahan"
                                                class="form-control @error('bahan') is-invalid @enderror"
                                                value="{{ old('bahan', $barangInstance->bahan ?? '') }}"
                                                placeholder="Contoh: Metal, Plastik, Kayu Jati">
                                            @error('bahan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="tahun_pembuatan" class="form-label">Tahun Pembuatan
                                                (Model)</label>
                                            <input type="number" name="tahun_pembuatan" id="tahun_pembuatan"
                                                class="form-control @error('tahun_pembuatan') is-invalid @enderror"
                                                value="{{ old('tahun_pembuatan', $barangInstance->tahun_pembuatan ?? date('Y')) }}"
                                                placeholder="YYYY" min="1900" max="{{ date('Y') + 5 }}">
                                            @error('tahun_pembuatan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="harga_perolehan_induk" class="form-label">Harga Perolehan Induk
                                                (Referensi)</label>
                                            <input type="number" name="harga_perolehan_induk" id="harga_perolehan_induk"
                                                class="form-control @error('harga_perolehan_induk') is-invalid @enderror"
                                                value="{{ old('harga_perolehan_induk', $barangInstance->harga_perolehan_induk ?? '') }}"
                                                placeholder="Harga umum/referensi (opsional)" min="0"
                                                step="1">
                                            @error('harga_perolehan_induk')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="sumber_perolehan_induk" class="form-label">Sumber Perolehan Induk
                                                (Referensi)</label>
                                            <input type="text" name="sumber_perolehan_induk"
                                                id="sumber_perolehan_induk"
                                                class="form-control @error('sumber_perolehan_induk') is-invalid @enderror"
                                                value="{{ old('sumber_perolehan_induk', $barangInstance->sumber_perolehan_induk ?? '') }}"
                                                placeholder="Contoh: BOS, APBN, Hibah Umum (opsional)">
                                            @error('sumber_perolehan_induk')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Barang Ini Menggunakan Nomor Seri per Unit? <span
                                                class="text-danger">*</span></label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input
                                                    class="form-check-input @error('menggunakan_nomor_seri') is-invalid @enderror"
                                                    type="radio" name="menggunakan_nomor_seri"
                                                    id="menggunakan_nomor_seri_ya_create" value="1"
                                                    {{ old('menggunakan_nomor_seri', $barangInstance->menggunakan_nomor_seri ?? '1') == '1' ? 'checked' : '' }}
                                                    {{ ($isEdit || $isWizardEditStep1) && isset($barangInstance) && $barangInstance->qrCodes()->exists() ? 'disabled' : '' }}
                                                    required>
                                                <label class="form-check-label"
                                                    for="menggunakan_nomor_seri_ya_create">Ya</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input
                                                    class="form-check-input @error('menggunakan_nomor_seri') is-invalid @enderror"
                                                    type="radio" name="menggunakan_nomor_seri"
                                                    id="menggunakan_nomor_seri_tidak_create" value="0"
                                                    {{ old('menggunakan_nomor_seri', $barangInstance->menggunakan_nomor_seri ?? '1') == '0' ? 'checked' : '' }}
                                                    {{ ($isEdit || $isWizardEditStep1) && isset($barangInstance) && $barangInstance->qrCodes()->exists() ? 'disabled' : '' }}
                                                    required>
                                                <label class="form-check-label"
                                                    for="menggunakan_nomor_seri_tidak_create">Tidak</label>
                                            </div>
                                        </div>
                                        @if (($isEdit || $isWizardEditStep1) && isset($barangInstance) && $barangInstance->qrCodes()->exists())
                                            <input type="hidden" name="menggunakan_nomor_seri"
                                                value="{{ $barangInstance->menggunakan_nomor_seri ? '1' : '0' }}">
                                            <small class="text-muted d-block">Opsi ini tidak dapat diubah karena sudah ada
                                                unit fisik terdaftar.</small>
                                        @endif
                                        @error('menggunakan_nomor_seri')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Jika "Ya", Anda akan diminta input nomor seri
                                            di langkah berikutnya. Jika "Tidak", unit akan digenerate otomatis.</small>
                                    </div>

                                    {{-- Sembunyikan bagian detail unit jika ini adalah mode edit standar (bukan wizard) --}}
                                    @if ($isWizardCreate || $isWizardEditStep1)
                                        <hr class="my-4">
                                        {{-- SEKSI DETAIL UNIT AWAL --}}
                                        <h6 class="form-section-title">B. Detail untuk Unit Awal yang Akan Dibuat</h6>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="jumlah_unit_awal" class="form-label">Jumlah Unit Awal <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" name="jumlah_unit_awal" id="jumlah_unit_awal"
                                                    class="form-control @error('jumlah_unit_awal') is-invalid @enderror"
                                                    value="{{ old('jumlah_unit_awal', $isWizardEditStep1 && session('unit_details_awal.jumlah_unit_awal') ? session('unit_details_awal.jumlah_unit_awal') : $barangInstance->total_jumlah_unit ?? 1) }}"
                                                    min="1" required
                                                    {{ $isWizardEditStep1 && isset($barangInstance) && $barangInstance->qrCodes()->exists() ? 'readonly' : '' }}>
                                                @if ($isWizardEditStep1 && isset($barangInstance) && $barangInstance->qrCodes()->exists())
                                                    <small class="text-muted">Jumlah unit tidak dapat diubah karena unit
                                                        sudah dibuat.</small>
                                                @else
                                                    <small class="form-text text-muted">Minimal 1 unit.</small>
                                                @endif
                                                @error('jumlah_unit_awal')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="kondisi_unit_awal" class="form-label">Kondisi Awal Semua Unit
                                                    <span class="text-danger">*</span></label>
                                                <select name="kondisi_unit_awal" id="kondisi_unit_awal"
                                                    class="form-select @error('kondisi_unit_awal') is-invalid @enderror"
                                                    required>
                                                    @foreach ($kondisiOptions as $kondisi)
                                                        <option value="{{ $kondisi }}"
                                                            {{ old('kondisi_unit_awal', $isWizardEditStep1 && session('unit_details_awal.kondisi') ? session('unit_details_awal.kondisi') : \App\Models\BarangQrCode::KONDISI_BAIK) == $kondisi ? 'selected' : '' }}>
                                                            {{ $kondisi }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('kondisi_unit_awal')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="tanggal_perolehan_unit_awal" class="form-label">Tgl. Perolehan
                                                    Unit Awal</label>
                                                <input type="date" name="tanggal_perolehan_unit_awal"
                                                    id="tanggal_perolehan_unit_awal"
                                                    class="form-control @error('tanggal_perolehan_unit_awal') is-invalid @enderror"
                                                    value="{{ old('tanggal_perolehan_unit_awal', $isWizardEditStep1 && session('unit_details_awal.tanggal_perolehan_unit') ? session('unit_details_awal.tanggal_perolehan_unit') : date('Y-m-d')) }}">
                                                @error('tanggal_perolehan_unit_awal')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <h6 class="form-subsection-title">Lokasi Awal / Pemegang Personal Awal <small
                                                class="text-muted">(Salah satu harus diisi)</small></h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="id_ruangan_awal" class="form-label">Ruangan Awal</label>
                                                <select name="id_ruangan_awal" id="id_ruangan_awal"
                                                    class="form-select @error('id_ruangan_awal') is-invalid @enderror">
                                                    <option value="">-- Pilih Ruangan --</option>
                                                    @if (isset($ruanganList))
                                                        @foreach ($ruanganList as $ruangan)
                                                            <option value="{{ $ruangan->id }}"
                                                                {{ old('id_ruangan_awal', $isWizardEditStep1 && session('unit_details_awal.id_ruangan') ? session('unit_details_awal.id_ruangan') : '') == $ruangan->id ? 'selected' : '' }}>
                                                                {{ $ruangan->nama_ruangan }}
                                                                ({{ $ruangan->kode_ruangan }})
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                @error('id_ruangan_awal')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="id_pemegang_personal_awal" class="form-label">Pemegang
                                                    Personal Awal</label>
                                                <select name="id_pemegang_personal_awal" id="id_pemegang_personal_awal"
                                                    class="form-select @error('id_pemegang_personal_awal') is-invalid @enderror">
                                                    <option value="">-- Pilih Guru --</option>
                                                    @if (isset($pemegangListAll))
                                                        @foreach ($pemegangListAll as $pemegang)
                                                            <option value="{{ $pemegang->id }}"
                                                                {{ old('id_pemegang_personal_awal', $isWizardEditStep1 && session('unit_details_awal.id_pemegang_personal') ? session('unit_details_awal.id_pemegang_personal') : '') == $pemegang->id ? 'selected' : '' }}>
                                                                {{ $pemegang->username }}
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                @error('id_pemegang_personal_awal')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <small class="form-text text-muted mb-3 d-block">Semua unit awal akan ditempatkan
                                            di ruangan atau dipegang oleh guru ini.</small>

                                        <h6 class="form-subsection-title">Detail Perolehan untuk Unit Awal (Opsional)</h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="harga_perolehan_unit_awal" class="form-label">Harga Perolehan
                                                    per Unit Awal (Rp)</label>
                                                <input type="number" name="harga_perolehan_unit_awal"
                                                    id="harga_perolehan_unit_awal"
                                                    class="form-control @error('harga_perolehan_unit_awal') is-invalid @enderror"
                                                    value="{{ old('harga_perolehan_unit_awal', $isWizardEditStep1 && session('unit_details_awal.harga_perolehan_unit') ? session('unit_details_awal.harga_perolehan_unit') : '') }}"
                                                    placeholder="Kosongkan jika sama dengan harga induk" min="0"
                                                    step="1">
                                                @error('harga_perolehan_unit_awal')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="sumber_dana_unit_awal" class="form-label">Sumber Dana Unit
                                                    Awal</label>
                                                <input type="text" name="sumber_dana_unit_awal"
                                                    id="sumber_dana_unit_awal"
                                                    class="form-control @error('sumber_dana_unit_awal') is-invalid @enderror"
                                                    value="{{ old('sumber_dana_unit_awal', $isWizardEditStep1 && session('unit_details_awal.sumber_dana_unit') ? session('unit_details_awal.sumber_dana_unit') : '') }}"
                                                    placeholder="Kosongkan jika sama dengan sumber induk">
                                                @error('sumber_dana_unit_awal')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="no_dokumen_unit_awal" class="form-label">No. Dokumen Perolehan
                                                    Unit Awal</label>
                                                <input type="text" name="no_dokumen_unit_awal"
                                                    id="no_dokumen_unit_awal"
                                                    class="form-control @error('no_dokumen_unit_awal') is-invalid @enderror"
                                                    value="{{ old('no_dokumen_unit_awal', $isWizardEditStep1 && session('unit_details_awal.no_dokumen_perolehan_unit') ? session('unit_details_awal.no_dokumen_perolehan_unit') : '') }}"
                                                    placeholder="Contoh: SPK/001/INV/2024">
                                                @error('no_dokumen_unit_awal')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="deskripsi_unit_awal" class="form-label">Deskripsi Tambahan
                                                    Unit Awal</label>
                                                <textarea name="deskripsi_unit_awal" id="deskripsi_unit_awal"
                                                    class="form-control @error('deskripsi_unit_awal') is-invalid @enderror" rows="1"
                                                    placeholder="Catatan spesifik untuk unit-unit awal ini">{{ old('deskripsi_unit_awal', $isWizardEditStep1 && session('unit_details_awal.deskripsi_unit') ? session('unit_details_awal.deskripsi_unit') : '') }}</textarea>
                                                @error('deskripsi_unit_awal')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    @endif {{-- End if(!$isEdit || $isWizardEdit) --}}

                                    <div class="d-flex justify-content-between mt-4">
                                        <a href="{{ route('barang.index') }}" class="btn btn-light waves-effect">
                                            <i class="mdi mdi-arrow-left me-1"></i>
                                            @if ($isEdit || $isWizardEditStep1)
                                                Batal
                                            @else
                                                Kembali ke Daftar
                                            @endif
                                        </a>
                                        @if ($isWizardEditStep1)
                                            <a href="{{ route('barang.input-serial', $barangInstance->id) }}"
                                                class="btn btn-info waves-effect">
                                                <i class="mdi mdi-arrow-right me-1"></i> Lanjut ke Step 2 (Nomor Seri)
                                            </a>
                                        @endif
                                        <button type="submit" class="btn btn-primary waves-effect waves-light">
                                            @if ($isWizardEditStep1)
                                                <i class="mdi mdi-content-save"></i> Simpan Perubahan Step 1 & Lanjut
                                            @elseif ($isEdit)
                                                <i class="mdi mdi-content-save"></i> Simpan Perubahan
                                            @else
                                                Simpan Data Induk & Lanjutkan <i class="mdi mdi-arrow-right ms-1"></i>
                                            @endif
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isWizardCreate = @json($isWizardCreate);
            const isWizardEditStep1 = @json($isWizardEditStep1);

            // Wizard Nav Styling
            const navLinks = document.querySelectorAll('.twitter-bs-wizard-nav .nav-link');
            if (navLinks.length > 1) {
                if (isWizardCreate || isWizardEditStep1) {
                    navLinks[0].classList.add('active');
                    navLinks[0].setAttribute('aria-selected', 'true');
                    navLinks[1].classList.remove('active');
                    navLinks[1].setAttribute('aria-selected', 'false');
                    navLinks[1].style.pointerEvents = 'none';
                    navLinks[1].style.opacity = '0.65';
                }
            }
        });
    </script>
@endpush
