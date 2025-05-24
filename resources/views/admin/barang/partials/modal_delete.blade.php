<div class="modal fade" id="modalHapusBarang" tabindex="-1" aria-labelledby="modalHapusBarangLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('barang-qrcode.destroy', $barang->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalHapusBarangLabel">Penghapusan Barang</h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan menghapus barang <strong>{{ $barang->nama_barang }}</strong>. Barang ini akan
                        dipindahkan ke arsip dan tidak akan tampil di halaman utama.</p>

                    <div class="mb-3">
                        <label for="alasan_penghapusan" class="form-label">Alasan Penghapusan <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control" name="alasan_penghapusan" id="alasan_penghapusan" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="berita_acara" class="form-label">Upload Berita Acara (Wajib)</label>
                        <input type="file" name="berita_acara" id="berita_acara" class="form-control"
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                        <small class="text-muted">File scan berita acara dalam bentuk foto atau dokumen.</small>
                    </div>

                    <div class="mb-3">
                        <label for="foto_bukti" class="form-label">Foto Bukti (Opsional)</label>
                        <input type="file" name="foto_bukti" id="foto_bukti" class="form-control" accept="image/*">
                    </div>

                    <div class="alert alert-warning">
                        <p class="mb-1">Langkah Konfirmasi:</p>
                        <small>Ketik <strong>HAPUS</strong> untuk melanjutkan.</small>
                        <input type="text" name="konfirmasi_ketik" class="form-control mt-2"
                            placeholder="Ketik: HAPUS" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus dan Arsipkan</button>
                </div>
            </div>
        </form>
    </div>
</div>
