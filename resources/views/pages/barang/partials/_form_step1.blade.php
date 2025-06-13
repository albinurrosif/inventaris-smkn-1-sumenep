@php
    $isEdit = isset($editMode) && $editMode === true;
    $isWizardEdit = isset($wizardStep) && $wizardStep === 1; // Flag untuk edit di wizard step 1
    $submitUrl = $isEdit ? route('barang.update', $barang->id) : route('barang.store');

    // Jika dalam mode Edit Wizard Step 1, gunakan route khusus
    if ($isWizardEdit) {
        $submitUrl = route('barang.update-step1', $barang->id);
    }
@endphp

<form action="{{ $submitUrl }}" method="POST" id="formStep1" onsubmit="window.isWizardNavigation = true;">
    @csrf
    @if ($isEdit || $isWizardEdit)
        @method('PUT')
        <!-- Tambahkan input hidden untuk menandakan wizard -->
        <input type="hidden" name="wizard_step" value="1">
    @endif

    <div class="row">
        {{-- Nama & Kode --}}
        <div class="col-md-6 mb-3">
            <label class="form-label">Nama Barang</label>
            <input type="text" name="nama_barang" class="form-control"
                value="{{ old('nama_barang', $barang->nama_barang ?? '') }}" required>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Kode Barang</label>
            <input type="text" name="kode_barang" class="form-control"
                value="{{ old('kode_barang', $barang->kode_barang ?? '') }}" required
                {{ $isEdit && $barang->qrCodes()->count() > 0 ? 'readonly' : '' }}>
            @if ($isEdit && $barang->qrCodes()->count() > 0)
                <small class="text-muted">Kode barang tidak dapat diubah karena sudah memiliki QR Code.</small>
            @endif
        </div>

        {{-- Merk & Ukuran --}}
        <div class="col-md-6 mb-3">
            <label class="form-label">Merk / Model</label>
            <input type="text" name="merk_model" class="form-control"
                value="{{ old('merk_model', $barang->merk_model ?? '') }}">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Ukuran</label>
            <input type="text" name="ukuran" class="form-control"
                value="{{ old('ukuran', $barang->ukuran ?? '') }}">
        </div>

        {{-- Bahan & Tahun --}}
        <div class="col-md-6 mb-3">
            <label class="form-label">Bahan</label>
            <input type="text" name="bahan" class="form-control" value="{{ old('bahan', $barang->bahan ?? '') }}">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Tahun Pembuatan / Pembelian</label>
            <input type="number" name="tahun_pembuatan_pembelian" class="form-control" min="1900"
                max="{{ date('Y') }}"
                value="{{ old('tahun_pembuatan_pembelian', $barang->tahun_pembuatan_pembelian ?? '') }}">
        </div>

        {{-- Harga & Sumber --}}
        <div class="col-md-6 mb-3">
            <label class="form-label">Harga Beli</label>
            <input type="number" step="0.01" name="harga_beli" class="form-control"
                value="{{ old('harga_beli', $barang->harga_beli ?? '') }}">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Sumber</label>
            <input type="text" name="sumber" class="form-control"
                value="{{ old('sumber', $barang->sumber ?? '') }}">
        </div>

        {{-- Keadaan & Jumlah --}}
        <div class="col-md-6 mb-3">
            <label class="form-label">Keadaan Barang</label>
            <select name="keadaan_barang" class="form-select" required>
                <option value="">-- Pilih --</option>
                @foreach (['Baik', 'Kurang Baik', 'Rusak Berat'] as $keadaan)
                    <option value="{{ $keadaan }}"
                        {{ old('keadaan_barang', $barang->keadaan_barang ?? '') == $keadaan ? 'selected' : '' }}>
                        {{ $keadaan }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Jumlah Barang</label>
            <input type="number" name="jumlah_barang" class="form-control" min="1"
                value="{{ old('jumlah_barang', $barang->jumlah_barang ?? 1) }}" required
                {{ $isEdit && $barang->qrCodes()->count() > 0 ? 'min=' . $barang->qrCodes()->count() : '' }}>
            @if ($isEdit && $barang->qrCodes()->count() > 0)
                <small class="text-muted">Jumlah minimal harus {{ $barang->qrCodes()->count() }} (sesuai QR Code yang
                    sudah ada).</small>
            @endif
        </div>

        {{-- Kategori --}}
        <div class="col-md-6 mb-3">
            <label class="form-label">Kategori Barang</label>
            <select name="id_kategori" class="form-select" required>
                <option value="">-- Pilih Kategori --</option>
                @foreach ($kategoriList as $kategori)
                    <option value="{{ $kategori->id }}"
                        {{ old('id_kategori', $barang->id_kategori ?? '') == $kategori->id ? 'selected' : '' }}>
                        {{ $kategori->nama_kategori }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Ruangan (Added here in Step 1) -->
        <div class="col-md-6 mb-3">
            <label class="form-label">Ruangan</label>
            <select name="id_ruangan" class="form-select" required>
                <option value="">-- Pilih Ruangan --</option>
                @foreach ($ruanganList as $ruangan)
                    <option value="{{ $ruangan->id }}" {{ old('id_ruangan') == $ruangan->id ? 'selected' : '' }}>
                        {{ $ruangan->nama_ruangan }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted">Semua unit akan ditempatkan di ruangan ini.</small>
        </div>

        {{-- Checkbox: menggunakan_nomor_seri --}}
        <div class="col-md-12 mb-3 form-check">
            <input type="checkbox" class="form-check-input" name="menggunakan_nomor_seri" id="checkSeri" value="1"
                {{ old('menggunakan_nomor_seri', $barang->menggunakan_nomor_seri ?? true) ? 'checked' : '' }}
                {{ $isEdit && $barang->qrCodes()->count() > 0 ? 'disabled' : '' }}>
            <label class="form-check-label" for="checkSeri">
                Barang ini menggunakan nomor seri per unit
            </label>
            @if ($isEdit && $barang->qrCodes()->count() > 0)
                <input type="hidden" name="menggunakan_nomor_seri"
                    value="{{ $barang->menggunakan_nomor_seri ? '1' : '0' }}">
                <small class="text-muted d-block">Opsi ini tidak dapat diubah karena QR Code sudah dibuat.</small>
            @endif
        </div>
    </div>

    <div class="d-flex justify-content-between mt-3 mb-3">
        @if (!$isWizardEdit)
            <div>
                <a href="{{ route('barang.index') }}" class="btn btn-primary">
                    <i class="mdi mdi-arrow-left"></i> Kembali
                </a>
            </div>
        @endif
        @if ($isWizardEdit)
            <div>
                <a href="{{ route('barang.input-serial', $barang->id) }}" class="btn btn-primary">
                    <i class="mdi mdi-arrow-right"></i> Kembali ke Step 2
                </a>
            </div>
        @endif

        <button type="submit" class="btn btn-primary">
            @if ($isWizardEdit)
                <i class="mdi mdi-content-save"></i> Simpan & Kembali ke Step 2
            @elseif ($isEdit)
                <i class="mdi mdi-content-save"></i> Simpan Perubahan
            @else
                <i class="mdi mdi-arrow-right"></i> Simpan & Lanjut ke Step 2
            @endif
        </button>
    </div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('formStep1');
                const checkSeri = document.getElementById('checkSeri');
                const originalSeriValue = checkSeri.checked;

                form.addEventListener('submit', function(e) {
                    // Cek hanya jika dalam mode wizard edit
                    const isWizardEdit = @json($isWizardEdit);

                    if (isWizardEdit && checkSeri.checked !== originalSeriValue && !checkSeri.checked) {
                        e.preventDefault();

                        Swal.fire({
                            title: 'Konfirmasi Perubahan',
                            text: 'Dengan menonaktifkan nomor seri, QR Code akan digenerate otomatis. Lanjutkan?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Lanjutkan',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    }
                });
            });
        </script>
    @endpush
</form>

@if ($isWizardEdit)
    <div class="d-flex justify-content-between mt-3 mb-3">
        <div class="d-flex gap-2">
            <form action="{{ route('barang.cancel', $barang->id) }}" method="POST" id="formCancelBarang"
                style="display: inline;">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-outline-danger" id="btnCancelBarang">
                    <i class="mdi mdi-close"></i> Batal
                </button>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            // Konfirmasi Pembatalan (style disesuaikan dengan tema toast)
            document.getElementById('btnCancelBarang')?.addEventListener('click', function() {
                Swal.fire({
                    title: 'Batalkan Proses?',
                    text: 'Data yang sudah diinput akan dihapus permanen',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Batalkan',
                    cancelButtonText: 'Kembali',
                    confirmButtonColor: '#d33',
                    background: '#fff',
                    position: 'center',
                    customClass: {
                        popup: 'shadow-lg rounded-lg'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Submit form pembatalan
                        window.isWizardNavigation = true;
                        document.getElementById('formCancelBarang').submit();

                        // Tampilkan toast feedback
                        Swal.fire({
                            icon: 'success',
                            title: 'Dibatalkan',
                            text: 'Proses pembuatan barang dibatalkan',
                            timer: 3000,
                            position: 'top',
                            toast: true,
                            showConfirmButton: false
                        });
                    }
                });
            });
        </script>
    @endpush
@endif
