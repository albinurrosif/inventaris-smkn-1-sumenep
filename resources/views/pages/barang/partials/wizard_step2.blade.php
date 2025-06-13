{{-- Step 2: Informasi Rencana Unit Awal --}}
<div class="row mb-3">
    <label for="jumlah_unit_awal" class="col-md-4 col-form-label text-md-end">Jumlah Unit Awal <span
            class="text-danger">*</span></label>
    <div class="col-md-7">
        <input id="jumlah_unit_awal" type="number" class="form-control @error('jumlah_unit_awal') is-invalid @enderror"
            name="jumlah_unit_awal" value="{{ old('jumlah_unit_awal', 1) }}" placeholder="Min. 1" required min="1">
        @error('jumlah_unit_awal')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>
</div>

<div class="row mb-3">
    <label for="id_ruangan_awal" class="col-md-4 col-form-label text-md-end">Penempatan di Ruangan</label>
    <div class="col-md-7">
        <select id="id_ruangan_awal" class="form-control choices-select @error('id_ruangan_awal') is-invalid @enderror"
            name="id_ruangan_awal">
            <option value="">-- Opsional: Pilih Ruangan --</option>
            @foreach ($ruanganList as $ruangan)
                <option value="{{ $ruangan->id }}" {{ old('id_ruangan_awal') == $ruangan->id ? 'selected' : '' }}>
                    {{ $ruangan->nama_ruangan }} ({{ $ruangan->kode_ruangan }})
                </option>
            @endforeach
        </select>
        @error('id_ruangan_awal')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
        <small class="form-text text-muted">Pilih ruangan ATAU pemegang personal. Salah satu harus diisi.</small>
    </div>
</div>

<div class="row mb-3">
    <label for="id_pemegang_personal_awal" class="col-md-4 col-form-label text-md-end">Pemegang Personal</label>
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
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>
</div>

<div class="row mb-3">
    <label for="kondisi_unit_awal" class="col-md-4 col-form-label text-md-end">Kondisi Unit Awal <span
            class="text-danger">*</span></label>
    <div class="col-md-7">
        <select id="kondisi_unit_awal"
            class="form-control choices-select @error('kondisi_unit_awal') is-invalid @enderror"
            name="kondisi_unit_awal" required>
            <option value="">-- Pilih Kondisi --</option>
            @php $defaultKondisi = old('kondisi_unit_awal', \App\Models\BarangQrCode::KONDISI_BAIK); @endphp
            @foreach ($kondisiOptions as $kondisi)
                <option value="{{ $kondisi }}" {{ $defaultKondisi == $kondisi ? 'selected' : '' }}>
                    {{ $kondisi }}</option>
            @endforeach
        </select>
        @error('kondisi_unit_awal')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>
</div>

<div class="row mb-3">
    <label for="harga_perolehan_unit_awal" class="col-md-4 col-form-label text-md-end">Harga Perolehan per Unit (Rp)
        <span class="text-danger">*</span></label>
    <div class="col-md-7">
        <input id="harga_perolehan_unit_awal" type="number" step="0.01"
            class="form-control @error('harga_perolehan_unit_awal') is-invalid @enderror"
            name="harga_perolehan_unit_awal" value="{{ old('harga_perolehan_unit_awal') }}"
            placeholder="Contoh: 15000000" required min="0">
        @error('harga_perolehan_unit_awal')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>
</div>

<div class="row mb-3">
    <label for="tanggal_perolehan_unit_awal" class="col-md-4 col-form-label text-md-end">Tanggal Perolehan Unit <span
            class="text-danger">*</span></label>
    <div class="col-md-7">
        <input id="tanggal_perolehan_unit_awal" type="text"
            class="form-control datepicker-input @error('tanggal_perolehan_unit_awal') is-invalid @enderror"
            name="tanggal_perolehan_unit_awal" value="{{ old('tanggal_perolehan_unit_awal', date('Y-m-d')) }}"
            required>
        @error('tanggal_perolehan_unit_awal')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>
</div>

<div class="row mb-3">
    <label for="sumber_dana_unit_awal" class="col-md-4 col-form-label text-md-end">Sumber Dana Unit</label>
    <div class="col-md-7">
        <input id="sumber_dana_unit_awal" type="text"
            class="form-control @error('sumber_dana_unit_awal') is-invalid @enderror" name="sumber_dana_unit_awal"
            value="{{ old('sumber_dana_unit_awal') }}" placeholder="Contoh: Dana BOS 2024">
        @error('sumber_dana_unit_awal')
            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>
</div>

<ul class="pager wizard twitter-bs-wizard-pager-link">
    <li class="previous"><a href="javascript:void(0);" class="btn btn-primary"><i class="bx bx-chevron-left me-1"></i>
            Kembali</a></li>
    <li class="next float-end"><a href="javascript:void(0);" class="btn btn-primary">Lanjut <i
                class="bx bx-chevron-right ms-1"></i></a></li>
    <li class="submit-step2 float-end" style="display: none;"><button type="submit" class="btn btn-success">Simpan
            Data <i class="bx bx-save ms-1"></i></button></li>
</ul>
