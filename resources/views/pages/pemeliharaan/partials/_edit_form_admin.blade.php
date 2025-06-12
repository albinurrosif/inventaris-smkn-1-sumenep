{{-- Form yang akan dilihat oleh Admin --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Panel Admin</h5>
    </div>
    <div class="card-body">
        {{-- Semua field bisa diedit oleh Admin --}}
        <div class="mb-3">
            <label for="catatan_pengajuan" class="form-label">Deskripsi Kerusakan</label>
            <textarea name="catatan_pengajuan" id="catatan_pengajuan" class="form-control">{{ old('catatan_pengajuan', $pemeliharaan->catatan_pengajuan) }}</textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="status_pengajuan" class="form-label">Status Pengajuan</label>
                <select name="status_pengajuan" id="status_pengajuan" class="form-select select2-basic">
                    @foreach ($statusPengajuanList as $key => $value)
                        <option value="{{ $key }}"
                            {{ old('status_pengajuan', $pemeliharaan->status_pengajuan) == $key ? 'selected' : '' }}>
                            {{ $value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="id_operator_pengerjaan" class="form-label">Tugaskan PIC</label>
                <select name="id_operator_pengerjaan" id="id_operator_pengerjaan" class="form-select select2-basic"
                    required>
                    <option value="">-- Hapus PIC --</option>
                    @foreach ($picList as $pic)
                        <option value="{{ $pic->id }}"
                            {{ old('id_operator_pengerjaan', $pemeliharaan->id_operator_pengerjaan) == $pic->id ? 'selected' : '' }}>
                            {{ $pic->username }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ===== AWAL PERUBAHAN ALUR KERJA ===== --}}
        <hr>
        {{-- Hanya tampilkan form progres jika laporan sudah disetujui --}}
        @if ($pemeliharaan->status_pengajuan === \App\Models\Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI)
            {{-- Include form pengerjaan agar admin juga bisa update progres --}}
            @include('pages.pemeliharaan.partials._edit_form_operator')
        @else
            {{-- Beri pesan petunjuk jika belum disetujui --}}
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                Untuk mengisi progres pengerjaan, ubah <strong>Status Pengajuan</strong> menjadi "Disetujui" dan simpan
                perubahan terlebih dahulu.
            </div>
        @endif
        {{-- ===== AKHIR PERUBAHAN ALUR KERJA ===== --}}
    </div>
</div>
