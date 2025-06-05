<div>
    {{-- Komponen Livewire harus memiliki satu elemen root --}}

    {{-- Notifikasi error validasi global Livewire (jika ada) --}}
    @if ($errors->any())
        <div class="alert alert-danger mb-3 py-2">
            <ul class="list-unstyled mb-0 small">
                @foreach ($errors->all() as $error)
                    <li><i class="mdi mdi-alert-circle-outline me-1"></i>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form di-handle oleh Livewire, tidak ada tag <form> di sini --}}
    {{-- Tombol navigasi (Previous/Next/Submit) akan disediakan oleh view wizard utama --}}

    {{-- SEKSI DATA INDUK BARANG --}}
    <h6 class="form-section-title mt-0">A. Informasi Jenis Barang (Induk)</h6>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="nama_barang_lw" class="form-label">Nama Jenis Barang <span class="text-danger">*</span></label>
            <input type="text" wire:model.defer="nama_barang" id="nama_barang_lw"
                class="form-control @error('nama_barang') is-invalid @enderror" required
                placeholder="Contoh: Laptop, Proyektor, Meja Siswa">
            @error('nama_barang')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-6 mb-3">
            <label for="kode_barang_lw" class="form-label">Kode Jenis Barang <span class="text-danger">*</span></label>
            <input type="text" wire:model.defer="kode_barang" id="kode_barang_lw"
                class="form-control @error('kode_barang') is-invalid @enderror" required placeholder=" ">
            @error('kode_barang')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="id_kategori_lw" class="form-label">Kategori Barang <span class="text-danger">*</span></label>
            <select wire:model.defer="id_kategori" id="id_kategori_lw"
                class="form-select @error('id_kategori') is-invalid @enderror" required>
                <option value="">-- Pilih Kategori --</option>
                @if ($kategoriList)
                    @foreach ($kategoriList as $kategori)
                        <option value="{{ $kategori->id }}">{{ $kategori->nama_kategori }}</option>
                    @endforeach
                @endif
            </select>
            @error('id_kategori')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-6 mb-3">
            <label for="merk_model_lw" class="form-label">Merk / Model</label>
            <input type="text" wire:model.defer="merk_model" id="merk_model_lw"
                class="form-control @error('merk_model') is-invalid @enderror"
                placeholder="Contoh: Acer Aspire 5, Epson EB-X500">
            @error('merk_model')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="ukuran_lw" class="form-label">Ukuran/Dimensi</label>
            <input type="text" wire:model.defer="ukuran" id="ukuran_lw"
                class="form-control @error('ukuran') is-invalid @enderror" placeholder="Contoh: 14 inch, 120x60x75 cm">
            @error('ukuran')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-4 mb-3">
            <label for="bahan_lw" class="form-label">Bahan Utama</label>
            <input type="text" wire:model.defer="bahan" id="bahan_lw"
                class="form-control @error('bahan') is-invalid @enderror"
                placeholder="Contoh: Metal, Plastik, Kayu Jati">
            @error('bahan')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-4 mb-3">
            <label for="tahun_pembuatan_lw" class="form-label">Tahun Pembuatan (Model)</label>
            <input type="number" wire:model.defer="tahun_pembuatan" id="tahun_pembuatan_lw"
                class="form-control @error('tahun_pembuatan') is-invalid @enderror" placeholder="YYYY" min="1900"
                max="{{ date('Y') + 5 }}">
            @error('tahun_pembuatan')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="harga_perolehan_induk_lw" class="form-label">Harga Perolehan Induk (Referensi)</label>
            <input type="number" wire:model.defer="harga_perolehan_induk" id="harga_perolehan_induk_lw"
                class="form-control @error('harga_perolehan_induk') is-invalid @enderror"
                placeholder="Harga umum/referensi (opsional)" min="0" step="1">
            @error('harga_perolehan_induk')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-6 mb-3">
            <label for="sumber_perolehan_induk_lw" class="form-label">Sumber Perolehan Induk (Referensi)</label>
            <input type="text" wire:model.defer="sumber_perolehan_induk" id="sumber_perolehan_induk_lw"
                class="form-control @error('sumber_perolehan_induk') is-invalid @enderror"
                placeholder="Contoh: BOS, APBN, Hibah Umum (opsional)">
            @error('sumber_perolehan_induk')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">Barang Ini Menggunakan Nomor Seri per Unit? <span class="text-danger">*</span></label>
        <div>
            <div class="form-check form-check-inline">
                {{-- Menggunakan wire:model untuk update instan jika diperlukan untuk logika skip step --}}
                <input class="form-check-input @error('menggunakan_nomor_seri') is-invalid @enderror" type="radio"
                    wire:model="menggunakan_nomor_seri" id="menggunakan_nomor_seri_ya_lw" value="1">
                <label class="form-check-label" for="menggunakan_nomor_seri_ya_lw">Ya</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input @error('menggunakan_nomor_seri') is-invalid @enderror" type="radio"
                    wire:model="menggunakan_nomor_seri" id="menggunakan_nomor_seri_tidak_lw" value="0">
                <label class="form-check-label" for="menggunakan_nomor_seri_tidak_lw">Tidak</label>
            </div>
        </div>
        @error('menggunakan_nomor_seri')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        <small class="form-text text-muted">Jika "Ya", Anda akan diminta input nomor seri di langkah berikutnya. Jika
            "Tidak", unit akan digenerate otomatis.</small>
    </div>

    <hr class="my-4">

    {{-- SEKSI DETAIL UNIT AWAL --}}
    <h6 class="form-section-title">B. Detail untuk Unit Awal yang Akan Dibuat</h6>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="jumlah_unit_awal_lw" class="form-label">Jumlah Unit Awal <span
                    class="text-danger">*</span></label>
            <input type="number" wire:model.defer="jumlah_unit_awal" id="jumlah_unit_awal_lw"
                class="form-control @error('jumlah_unit_awal') is-invalid @enderror" min="1" required>
            @error('jumlah_unit_awal')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
            <small class="form-text text-muted">Minimal 1 unit.</small>
        </div>
        <div class="col-md-4 mb-3">
            <label for="kondisi_unit_awal_lw" class="form-label">Kondisi Awal Semua Unit <span
                    class="text-danger">*</span></label>
            <select wire:model.defer="kondisi_unit_awal" id="kondisi_unit_awal_lw"
                class="form-select @error('kondisi_unit_awal') is-invalid @enderror" required>
                @if ($kondisiOptions)
                    @foreach ($kondisiOptions as $kondisi)
                        <option value="{{ $kondisi }}">{{ $kondisi }}</option>
                    @endforeach
                @endif
            </select>
            @error('kondisi_unit_awal')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-4 mb-3">
            <label for="tanggal_perolehan_unit_awal_lw" class="form-label">Tgl. Perolehan Unit Awal</label>
            <input type="date" wire:model.defer="tanggal_perolehan_unit_awal" id="tanggal_perolehan_unit_awal_lw"
                class="form-control @error('tanggal_perolehan_unit_awal') is-invalid @enderror">
            @error('tanggal_perolehan_unit_awal')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <h6 class="form-subsection-title">Lokasi Awal / Pemegang Personal Awal <small class="text-muted">(Salah satu harus
            diisi)</small></h6>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="id_ruangan_awal_lw" class="form-label">Ruangan Awal</label>
            <select wire:model.defer="id_ruangan_awal" id="id_ruangan_awal_lw"
                class="form-select @error('id_ruangan_awal') is-invalid @enderror">
                <option value="">-- Pilih Ruangan --</option>
                @if ($ruanganList)
                    @foreach ($ruanganList as $ruangan)
                        <option value="{{ $ruangan->id }}">{{ $ruangan->nama_ruangan }}
                            ({{ $ruangan->kode_ruangan }})
                        </option>
                    @endforeach
                @endif
            </select>
            @error('id_ruangan_awal')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-6 mb-3">
            <label for="id_pemegang_personal_awal_lw" class="form-label">Pemegang Personal Awal</label>
            <select wire:model.defer="id_pemegang_personal_awal" id="id_pemegang_personal_awal_lw"
                class="form-select @error('id_pemegang_personal_awal') is-invalid @enderror">
                <option value="">-- Pilih Guru --</option>
                @if ($pemegangListAll)
                    @foreach ($pemegangListAll as $pemegang)
                        <option value="{{ $pemegang->id }}">{{ $pemegang->username }}</option>
                    @endforeach
                @endif
            </select>
            @error('id_pemegang_personal_awal')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <small class="form-text text-muted mb-3 d-block">Semua unit awal akan ditempatkan di ruangan atau dipegang oleh
        guru ini.</small>

    <h6 class="form-subsection-title">Detail Perolehan untuk Unit Awal (Opsional)</h6>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="harga_perolehan_unit_awal_lw" class="form-label">Harga Perolehan per Unit Awal (Rp)</label>
            <input type="number" wire:model.defer="harga_perolehan_unit_awal" id="harga_perolehan_unit_awal_lw"
                class="form-control @error('harga_perolehan_unit_awal') is-invalid @enderror"
                placeholder="Kosongkan jika sama dengan harga induk" min="0" step="1">
            @error('harga_perolehan_unit_awal')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
        <div class="col-md-6 mb-3">
            <label for="sumber_dana_unit_awal_lw" class="form-label">Sumber Dana Unit Awal</label>
            <input type="text" wire:model.defer="sumber_dana_unit_awal" id="sumber_dana_unit_awal_lw"
                class="form-control @error('sumber_dana_unit_awal') is-invalid @enderror"
                placeholder="Kosongkan jika sama dengan sumber induk">
            @error('sumber_dana_unit_awal')
                <span class="invalid-feedback d-block">{{ $message }}</span>
            @enderror
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="no_dokumen_unit_awal_lw" class="form-label">No. Dokumen Perolehan Unit Awal</label>
            <input type="text" wire:model.defer="no_dokumen_unit_awal" id="no_dokumen_unit_awal_lw"
                class="form-control @error('no_dokumen_unit_awal') is-invalid @enderror"
                placeholder="Contoh: SPK/001/INV/2024">
            @error('no_dokumen_unit_awal')
                <div class="invalid-feedback d-block">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="deskripsi_unit_awal_lw" class="form-label">Deskripsi Tambahan Unit Awal</label>
                <textarea wire:model.defer="deskripsi_unit_awal" id="deskripsi_unit_awal_lw"
                    class="form-control @error('deskripsi_unit_awal') is-invalid @enderror" rows="1"
                    placeholder="Catatan spesifik untuk unit-unit awal ini"></textarea>
                @error('deskripsi_unit_awal')
                    <div class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
    </div>
