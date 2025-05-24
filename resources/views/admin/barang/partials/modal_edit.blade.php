<div class="modal fade" id="modalEditBarang" tabindex="-1" aria-labelledby="modalEditBarangLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="formEditBarang">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditBarangLabel">Edit Informasi Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label for="editNamaBarang" class="form-label">Nama Barang</label>
                        <input type="text" class="form-control" id="editNamaBarang" name="nama_barang" required>
                    </div>

                    <div class="mb-3">
                        <label for="editMerkModel" class="form-label">Merk / Model</label>
                        <input type="text" class="form-control" id="editMerkModel" name="merk_model">
                    </div>

                    <div class="mb-3">
                        <label for="editUkuran" class="form-label">Ukuran</label>
                        <input type="text" class="form-control" id="editUkuran" name="ukuran">
                    </div>

                    <div class="mb-3">
                        <label for="editBahan" class="form-label">Bahan</label>
                        <input type="text" class="form-control" id="editBahan" name="bahan">
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>
