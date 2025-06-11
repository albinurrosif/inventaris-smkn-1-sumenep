{{-- Step 1: Informasi Jenis Barang --}}
<div class="row mb-3">
    <label for="nama_barang" class="col-md-4 col-form-label text-md-end">Nama Barang <span
            class="text-danger">*</span></label>
    <div class="col-md-7">
        <input id="nama_barang" type="text" class="form-control @error('nama_barang') is-invalid @enderror"
            name="nama_barang" value="{{ old('nama_barang') }}" placeholder="Contoh: Laptop ASUS Zenbook 14 OLED"
            required>
        @error('nama_barang')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>
</div>

<div class="row mb-3">
    <label for="kode_barang" class="col-md-4 col-form-label text-md-end">Kode Barang <span
            class="text-danger">*</span></label>
    <div class="col-md-7">
        <input id="kode_barang" type="text" class="form-control @error('kode_barang') is-invalid @enderror"
            name="kode_barang" value="{{ old('kode_barang') }}" placeholder="Contoh: LP-ASUS-ZB14 (unik)" required>
        @error('kode_barang')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>
</div>

<div class="row mb-3">
    <label for="id_kategori" class="col-md-4 col-form-label text-md-end">Kategori Barang <span
            class="text-danger">*</span></label>
    <div class="col-md-7">
        <select id="id_kategori" class="form-control choices-select @error('id_kategori') is-invalid @enderror"
            name="id_kategori" required>
            <option value="">-- Pilih Kategori --</option>
            @foreach ($kategoriList as $kategori)
                <option value="{{ $kategori->id }}" {{ old('id_kategori') == $kategori->id ? 'selected' : '' }}>
                    {{ $kategori->nama_kategori }}
                </option>
            @endforeach
        </select>
        @error('id_kategori')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>
</div>

<div class="row mb-3">
    <label class="col-md-4 col-form-label text-md-end">Kelola Unit dengan Nomor Seri? <span
            class="text-danger">*</span></label>
    <div class="col-md-7">
        <div class="form-check form-check-inline mt-2">
            <input class="form-check-input @error('menggunakan_nomor_seri') is-invalid @enderror" type="radio"
                name="menggunakan_nomor_seri" id="menggunakan_nomor_seri_ya" value="1"
                {{ old('menggunakan_nomor_seri', '1') == '1' ? 'checked' : '' }} required>
            <label class="form-check-label" for="menggunakan_nomor_seri_ya">Ya</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input @error('menggunakan_nomor_seri') is-invalid @enderror" type="radio"
                name="menggunakan_nomor_seri" id="menggunakan_nomor_seri_tidak" value="0"
                {{ old('menggunakan_nomor_seri') == '0' ? 'checked' : '' }} required>
            <label class="form-check-label" for="menggunakan_nomor_seri_tidak">Tidak</label>
        </div>
        @error('menggunakan_nomor_seri')
            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>
</div>

<div class="row mb-3">
    <label for="merk_model" class="col-md-4 col-form-label text-md-end">Merk / Model</label>
    <div class="col-md-7">
        <input id="merk_model" type="text" class="form-control @error('merk_model') is-invalid @enderror"
            name="merk_model" value="{{ old('merk_model') }}" placeholder="Contoh: ASUS Zenbook UX3402ZA">
        @error('merk_model')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>
</div>

<div class="row mb-3">
    <label for="ukuran" class="col-md-4 col-form-label text-md-end">Ukuran</label>
    <div class="col-md-7">
        <input id="ukuran" type="text" class="form-control @error('ukuran') is-invalid @enderror" name="ukuran"
            value="{{ old('ukuran') }}" placeholder="Contoh: 14 inch, 120x60 cm">
        @error('ukuran')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>
</div>

<div class="row mb-3">
    <label for="bahan" class="col-md-4 col-form-label text-md-end">Bahan</label>
    <div class="col-md-7">
        <input id="bahan" type="text" class="form-control @error('bahan') is-invalid @enderror" name="bahan"
            value="{{ old('bahan') }}" placeholder="Contoh: Aluminium, Kayu Jati">
        @error('bahan')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>
</div>

<div class="row mb-3">
    <label for="tahun_pembuatan" class="col-md-4 col-form-label text-md-end">Tahun Pembuatan</label>
    <div class="col-md-7">
        <input id="tahun_pembuatan" type="number"
            class="form-control @error('tahun_pembuatan') is-invalid @enderror" name="tahun_pembuatan"
            value="{{ old('tahun_pembuatan') }}" placeholder="Contoh: {{ date('Y') }}" min="1900"
            max="{{ date('Y') + 1 }}">
        @error('tahun_pembuatan')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>
</div>

<ul class="pager wizard twitter-bs-wizard-pager-link">
    <li class="float-start"><a href="{{ route($rolePrefix . 'barang.index') }}" class="btn btn-light">Batal</a></li>
    <li class="next float-end"><a href="javascript:void(0);" class="btn btn-primary">Lanjut <i
                class="bx bx-chevron-right ms-1"></i></a></li>
</ul>
