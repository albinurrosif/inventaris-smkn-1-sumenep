{{-- resources/views/admin/barang_qr_code/partials/modal_mutasi_unit.blade.php --}}
<div class="modal fade" id="modalMutasiUnit" tabindex="-1" aria-labelledby="modalMutasiUnitLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        {{-- Form action akan di-set oleh JavaScript untuk menargetkan unit yang benar --}}
        <form id="formMutasiUnitAction" action="" method="POST" enctype="multipart/form-data">
            @csrf
            @method('POST') {{-- Sesuai dengan metode untuk route mutasi (bisa POST atau PUT) --}}
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalMutasiUnitLabel">Formulir Mutasi/Pemindahan Unit Barang</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan memindahkan unit barang dengan Kode Inventaris:
                        <strong id="mutasiUnitKodeDisplay" class="text-primary"></strong>.
                    </p>

                    {{-- ID unit barang yang akan dimutasi, diisi oleh JavaScript --}}
                    <input type="hidden" name="id_barang_qr_code_hidden" id="mutasiUnitId">
                    <input type="hidden" name="id_ruangan_asal_hidden" id="mutasiIdRuanganAsalHidden">

                    <div class="mb-3">
                        <label class="form-label">Ruangan Asal Saat Ini:</label>
                        <p><strong id="mutasiRuanganAsalDisplay">Memuat...</strong></p>
                    </div>

                    <div class="mb-3">
                        <label for="mutasiIdRuanganTujuan" class="form-label">Pindahkan ke Ruangan Tujuan <span
                                class="text-danger">*</span></label>
                        {{-- $ruanganListAll harus dikirim dari controller yang memanggil view ini --}}
                        {{-- atau dari view induk yang meng-include partial ini --}}
                        <select name="id_ruangan_tujuan" id="mutasiIdRuanganTujuan" class="form-select" required>
                            <option value="">-- Pilih Ruangan Tujuan --</option>
                            @if (isset($ruanganListAll) && $ruanganListAll->count() > 0)
                                @foreach ($ruanganListAll as $ruangan)
                                    <option value="{{ $ruangan->id }}">{{ $ruangan->nama_ruangan }}
                                        ({{ $ruangan->kode_ruangan }})</option>
                                @endforeach
                            @else
                                <option value="" disabled>Tidak ada data ruangan.</option>
                            @endif
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="mutasi_alasan_pemindahan" class="form-label">Alasan Pemindahan <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control" name="alasan_pemindahan" id="mutasi_alasan_pemindahan" rows="3" required
                            placeholder="Jelaskan alasan mengapa unit ini dipindahkan."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="mutasi_surat_pemindahan" class="form-label">Upload Surat Pemindahan (Jika
                            Ada)</label>
                        <input type="file" name="surat_pemindahan_path" id="mutasi_surat_pemindahan"
                            class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <small class="text-muted">Dokumen surat perintah atau berita acara pemindahan.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-truck me-1"></i> Ya, Pindahkan Unit
                        Ini</button>
                </div>
            </div>
        </form>
    </div>
</div>


