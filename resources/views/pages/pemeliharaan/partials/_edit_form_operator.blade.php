{{-- Form yang dilihat oleh Operator PIC (atau Admin) --}}
<div class="card mt-3">
    <div class="card-header">
        <h5 class="card-title mb-0">Update Progres Pengerjaan</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="status_pengerjaan" class="form-label">Ubah Status Pengerjaan <span
                        class="text-danger">*</span></label>
                <select name="status_pengerjaan" id="status_pengerjaan" class="form-select select2-basic" required>
                    @foreach ($statusPengerjaanList as $key => $value)
                        <option value="{{ $key }}"
                            {{ old('status_pengerjaan', $pemeliharaan->status_pengerjaan) == $key ? 'selected' : '' }}>
                            {{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="biaya" class="form-label">Biaya Perbaikan (Rp)</label>
                <input type="number" name="biaya" id="biaya" class="form-control"
                    value="{{ old('biaya', $pemeliharaan->biaya) }}" placeholder="Contoh: 50000">
            </div>
        </div>

        {{-- ===== AWAL PERUBAHAN TAMPILAN TANGGAL ===== --}}
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="tanggal_mulai_pengerjaan" class="form-label">Tgl. Mulai Dikerjakan</label>
                <input type="text" class="form-control" id="tanggal_mulai_pengerjaan"
                    value="{{ optional($pemeliharaan->tanggal_mulai_pengerjaan)->isoFormat('DD MMMM YYYY, HH:mm') ?? '' }}"
                    readonly>
                <small class="form-text text-muted">Diisi otomatis saat status diubah.</small>
            </div>
            <div class="col-md-6 mb-3">
                <label for="tanggal_selesai_pengerjaan" class="form-label">Tgl. Selesai Dikerjakan</label>
                <input type="text" class="form-control" id="tanggal_selesai_pengerjaan"
                    value="{{ optional($pemeliharaan->tanggal_selesai_pengerjaan)->isoFormat('DD MMMM YYYY, HH:mm') ?? '' }}"
                    readonly>
                <small class="form-text text-muted">Diisi otomatis saat status diubah.</small>
            </div>
        </div>
        {{-- ===== AKHIR PERUBAHAN TAMPILAN TANGGAL ===== --}}

        <div class="mb-3">
            <label for="deskripsi_pekerjaan" class="form-label">Pekerjaan yang Dilakukan</label>
            <textarea name="deskripsi_pekerjaan" id="deskripsi_pekerjaan" class="form-control" rows="3">{{ old('deskripsi_pekerjaan', $pemeliharaan->deskripsi_pekerjaan) }}</textarea>
        </div>

        {{-- Form Hasil Akhir (logika JS tidak berubah) --}}
        <div id="form-hasil-pengerjaan"
            style="{{ in_array($pemeliharaan->status_pengerjaan, ['Selesai', 'Tidak Dapat Diperbaiki', 'Gagal']) ? '' : 'display: none;' }}">
            <h6 class="text-primary mt-4">Hasil Akhir Pengerjaan</h6>
            <div class="mb-3">
                <label for="hasil_pemeliharaan" class="form-label">Hasil Perbaikan <span
                        class="text-danger">*</span></label>
                <textarea name="hasil_pemeliharaan" id="hasil_pemeliharaan" class="form-control" rows="3" required>{{ old('hasil_pemeliharaan', $pemeliharaan->hasil_pemeliharaan) }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="kondisi_barang_setelah_pemeliharaan" class="form-label">Kondisi Barang Setelah
                        Diperbaiki <span class="text-danger">*</span></label>
                    <select name="kondisi_barang_setelah_pemeliharaan" id="kondisi_barang_setelah_pemeliharaan"
                        class="form-select select2-basic" required>
                        <option value="">-- Pilih Kondisi Akhir --</option>
                        @foreach ($kondisiBarangList as $value)
                            <option value="{{ $value }}"
                                {{ old('kondisi_barang_setelah_pemeliharaan', $pemeliharaan->kondisi_barang_setelah_pemeliharaan) == $value ? 'selected' : '' }}>
                                {{ $value }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="foto_perbaikan" class="form-label">Unggah Foto Bukti Perbaikan</label>
                    <input class="form-control" type="file" id="foto_perbaikan" name="foto_perbaikan"
                        accept="image/*">
                </div>
            </div>
        </div>
    </div>
</div>
