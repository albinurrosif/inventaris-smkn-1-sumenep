{{-- resources/views/admin/barang_qr_code/create_units.blade.php --}}
{{--
    Halaman ini berfungsi sebagai formulir untuk menambahkan N unit fisik baru
    ke dalam satu jenis barang (induk) yang sudah ada.

    Variabel yang diharapkan dari Controller (BarangQrCodeController@create):
    - $barang: Model \App\Models\Barang (induknya).
    - $jumlah_unit: integer, jumlah form unit yang akan digenerate.
    - $ruanganList: Collection of \App\Models\Ruangan.
    - $pemegangList: Collection of \App\Models\User.
    - $kondisiOptions: Array of string, kondisi valid untuk barang.
--}}

@extends('layouts.app')

@section('title', 'Tambah Unit untuk ' . $barang->nama_barang)

@section('content')
    <div class="container-fluid">
        {{-- Judul Halaman dan Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Tambah Unit Barang Baru</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.barang.index') }}">Daftar Jenis Barang</a>
                            </li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route('admin.barang.show', $barang->id) }}">{{ $barang->nama_barang }}</a></li>
                            <li class="breadcrumb-item active">Tambah Unit</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Alert Informasi --}}
        <div class="alert alert-info">
            Anda akan menambahkan <strong>{{ $jumlah_unit }}</strong> unit fisik baru untuk jenis barang:
            <strong>{{ $barang->nama_barang }} ({{ $barang->kode_barang }})</strong>.
            @if (!$barang->menggunakan_nomor_seri)
                <p class="mt-2 text-danger fw-bold">PERHATIAN: Jenis barang ini tidak menggunakan nomor seri. Penambahan
                    unit individual mungkin tidak sesuai.</p>
            @endif
        </div>

        {{-- BLOK UNTUK MENAMPILKAN ERROR VALIDASI --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <h5 class="alert-heading">Terjadi Kesalahan!</h5>
                <p>Mohon periksa kembali isian Anda. Ada beberapa hal yang perlu diperbaiki:</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form Utama --}}
        {{-- Arahkan ke route store yang akan Anda buat di BarangQrCodeController --}}
        <form action="{{ route('admin.barang-qr-code.store') }}" method="POST">
            @csrf
            <input type="hidden" name="id_barang" value="{{ $barang->id }}">
            <input type="hidden" name="jumlah_unit" value="{{ $jumlah_unit }}">

            {{-- Card untuk mengatur semua unit sekaligus (UX Improvement) --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-cogs me-2"></i>Pengaturan Massal (Setel untuk Semua Unit)
                    </h5>
                    <small class="text-muted">Gunakan form ini untuk mengisi data yang sama ke semua form unit di bawah
                        secara otomatis.</small>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="master_id_ruangan" class="form-label">Lokasi Ruangan</label>
                            <select id="master_id_ruangan" class="form-select master-input">
                                <option value="">-- Pilih Ruangan --</option>
                                @foreach ($ruanganList as $ruangan)
                                    <option value="{{ $ruangan->id }}">{{ $ruangan->nama_ruangan }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="master_harga" class="form-label">Harga Perolehan per Unit</label>
                            <input type="number" id="master_harga" class="form-control master-input"
                                placeholder="Contoh: 1500000">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="master_tanggal" class="form-label">Tanggal Perolehan</label>
                            <input type="date" id="master_tanggal" class="form-control master-input"
                                value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Loop untuk setiap unit yang akan ditambahkan --}}
            @for ($i = 0; $i < $jumlah_unit; $i++)
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0">Detail Unit Ke-{{ $i + 1 }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            {{-- Kolom Kiri --}}
                            <div class="col-md-6">
                                @if ($barang->menggunakan_nomor_seri)
                                    <div class="mb-3">
                                        <label for="no_seri_pabrik_{{ $i }}" class="form-label">Nomor Seri
                                            Pabrik <span class="text-danger">*</span></label>
                                        <input type="text" name="units[{{ $i }}][no_seri_pabrik]"
                                            id="no_seri_pabrik_{{ $i }}" class="form-control"
                                            placeholder="Masukkan nomor seri unik" required>
                                    </div>
                                @endif
                                <div class="mb-3">
                                    <label for="kode_inventaris_{{ $i }}" class="form-label">Kode Inventaris
                                        Sekolah</label>
                                    <input type="text" id="kode_inventaris_{{ $i }}" class="form-control"
                                        placeholder="Akan digenerate otomatis" disabled>
                                </div>
                                <div class="mb-3">
                                    <label for="harga_perolehan_unit_{{ $i }}" class="form-label">Harga
                                        Perolehan Unit <span class="text-danger">*</span></label>
                                    <input type="number" name="units[{{ $i }}][harga_perolehan_unit]"
                                        id="harga_perolehan_unit_{{ $i }}" class="form-control auto-fill-harga"
                                        min="0" placeholder="Harga satuan barang" required>
                                </div>
                                <div class="mb-3">
                                    <label for="tanggal_perolehan_unit_{{ $i }}" class="form-label">Tanggal
                                        Perolehan <span class="text-danger">*</span></label>
                                    <input type="date" name="units[{{ $i }}][tanggal_perolehan_unit]"
                                        id="tanggal_perolehan_unit_{{ $i }}"
                                        class="form-control auto-fill-tanggal" required>
                                </div>
                                <div class="mb-3">
                                    <label for="deskripsi_unit_{{ $i }}" class="form-label">Deskripsi Spesifik
                                        Unit</label>
                                    <textarea name="units[{{ $i }}][deskripsi_unit]" id="deskripsi_unit_{{ $i }}"
                                        class="form-control" rows="2" placeholder="Contoh: Warna merah, sedikit goresan di sisi kanan"></textarea>
                                </div>
                            </div>
                            {{-- Kolom Kanan --}}
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_ruangan_{{ $i }}" class="form-label">Lokasi Ruangan</label>
                                    <select name="units[{{ $i }}][id_ruangan]"
                                        id="id_ruangan_{{ $i }}" class="form-select auto-fill-ruangan">
                                        <option value="">-- Pilih Ruangan (Jika tidak dipegang personal) --</option>
                                        @foreach ($ruanganList as $ruangan)
                                            <option value="{{ $ruangan->id }}">{{ $ruangan->nama_ruangan }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="id_pemegang_personal_{{ $i }}" class="form-label">Pemegang
                                        Personal</label>
                                    <select name="units[{{ $i }}][id_pemegang_personal]"
                                        id="id_pemegang_personal_{{ $i }}" class="form-select">
                                        <option value="">-- Pilih Pengguna (Jika tidak ditaruh di ruangan) --
                                        </option>
                                        @foreach ($pemegangList as $pemegang)
                                            <option value="{{ $pemegang->id }}">{{ $pemegang->username }}
                                                ({{ $pemegang->role }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="kondisi_{{ $i }}" class="form-label">Kondisi Awal <span
                                            class="text-danger">*</span></label>
                                    <select name="units[{{ $i }}][kondisi]"
                                     id="kondisi_{{ $i }}" class="form-select"
                                        required>
                                        @foreach ($kondisiOptions as $kondisi)
                                            <option value="{{ $kondisi }}"
                                                @if ($kondisi == \App\Models\BarangQrCode::KONDISI_BAIK) selected @endif>{{ $kondisi }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="sumber_dana_unit_{{ $i }}" class="form-label">Sumber
                                        Dana</label>
                                    <input type="text" name="units[{{ $i }}][sumber_dana_unit]" 
                                        id="sumber_dana_unit_{{ $i }}" class="form-control"
                                        placeholder="Contoh: DANA BOS 2025">
                                </div>
                                <div class="mb-3">
                                    <label for="no_dokumen_perolehan_unit_{{ $i }}" class="form-label">No.
                                        Dokumen Perolehan</label>
                                    <input type="text" name="units[{{ $i }}][no_dokumen_perolehan_unit]"
                                        id="no_dokumen_perolehan_unit_{{ $i }}" class="form-control"
                                        placeholder="Contoh: FKT/2025/VI/123">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endfor

            <div class="card mt-3">
                <div class="card-body text-end">
                    <a href="{{ route('admin.barang.show', $barang->id) }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan
                        {{ $jumlah_unit }} Unit Baru</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fungsi untuk menerapkan nilai dari master input ke semua form unit
            function applyMasterValue(masterInput, targetSelector) {
                const targetInputs = document.querySelectorAll(targetSelector);
                targetInputs.forEach(input => {
                    input.value = masterInput.value;
                });
            }

            // Event listener untuk setiap master input
            const masterRuangan = document.getElementById('master_id_ruangan');
            if (masterRuangan) {
                masterRuangan.addEventListener('change', function() {
                    applyMasterValue(this, '.auto-fill-ruangan');
                });
            }

            const masterHarga = document.getElementById('master_harga');
            if (masterHarga) {
                masterHarga.addEventListener('input', function() {
                    applyMasterValue(this, '.auto-fill-harga');
                });
            }

            const masterTanggal = document.getElementById('master_tanggal');
            if (masterTanggal) {
                masterTanggal.addEventListener('change', function() {
                    applyMasterValue(this, '.auto-fill-tanggal');
                });
            }
        });
    </script>
@endpush
