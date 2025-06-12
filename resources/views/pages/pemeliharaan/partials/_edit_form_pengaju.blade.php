{{-- Form yang dilihat oleh Pengaju Awal (jika status masih Diajukan) --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Edit Laporan Anda</h5>
    </div>
    <div class="card-body">
        <p>Anda dapat mengedit laporan Anda karena statusnya masih "Diajukan".</p>
        <div class="mb-3">
            <label for="catatan_pengajuan" class="form-label">Jelaskan Kerusakan atau Keluhan <span
                    class="text-danger">*</span></label>
            <textarea name="catatan_pengajuan" id="catatan_pengajuan" class="form-control" rows="4" required>{{ old('catatan_pengajuan', $pemeliharaan->catatan_pengajuan) }}</textarea>
        </div>
        <div class="mb-3">
            <label for="prioritas" class="form-label">Prioritas Penanganan <span class="text-danger">*</span></label>
            <select name="prioritas" id="prioritas" class="form-select select2-basic" required>
                @foreach ($prioritasOptions as $key => $value)
                    <option value="{{ $key }}"
                        {{ old('prioritas', $pemeliharaan->prioritas) == $key ? 'selected' : '' }}>{{ $value }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label for="foto_kerusakan" class="form-label">Ubah Foto Kerusakan (Opsional)</label>
            <input class="form-control" type="file" id="foto_kerusakan" name="foto_kerusakan" accept="image/*">
        </div>
    </div>
</div>
