{{-- resources/views/admin/barang/partials/modal_edit_jenis.blade.php --}}
<div class="modal fade" id="modalEditJenisBarang" tabindex="-1" aria-labelledby="modalEditJenisBarangLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formEditJenisBarangAction" action="" method="POST"> {{-- Action di-set oleh JS --}}
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="modalEditJenisBarangLabel">Edit Informasi Jenis Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editNamaBarang" class="form-label">Nama Barang <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editNamaBarang" name="nama_barang"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="editKodeBarang" class="form-label">Kode Barang (Induk)</label>
                                <input type="text" class="form-control" id="editKodeBarang" name="kode_barang">
                                <small class="text-muted" id="infoKodeBarangEdit"></small>
                            </div>
                            <div class="mb-3">
                                <label for="editIdKategori" class="form-label">Kategori Barang <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="editIdKategori" name="id_kategori" required>
                                    <option value="" disabled>-- Pilih Kategori --</option>
                                    @if (isset($kategoriList))
                                        @foreach ($kategoriList as $kategori)
                                            <option value="{{ $kategori->id }}">{{ $kategori->nama_kategori }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="editMerkModel" class="form-label">Merk / Model</label>
                                <input type="text" class="form-control" id="editMerkModel" name="merk_model">
                            </div>
                            <div class="mb-3">
                                <label for="editUkuran" class="form-label">Ukuran</label>
                                <input type="text" class="form-control" id="editUkuran" name="ukuran">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editBahan" class="form-label">Bahan</label>
                                <input type="text" class="form-control" id="editBahan" name="bahan">
                            </div>
                            <div class="mb-3">
                                <label for="editTahunPembuatan" class="form-label">Tahun Pembuatan</label>
                                <input type="number" class="form-control" id="editTahunPembuatan"
                                    name="tahun_pembuatan" min="1900" max="{{ date('Y') + 5 }}">
                            </div>
                            <div class="mb-3">
                                <label for="editHargaPerolehanInduk" class="form-label">Harga Perolehan Induk
                                    (Rp)</label>
                                <input type="number" class="form-control" id="editHargaPerolehanInduk"
                                    name="harga_perolehan_induk" step="any" min="0">
                            </div>
                            <div class="mb-3">
                                <label for="editSumberPerolehanInduk" class="form-label">Sumber Perolehan Induk</label>
                                <input type="text" class="form-control" id="editSumberPerolehanInduk"
                                    name="sumber_perolehan_induk">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Menggunakan Nomor Seri/Pelacakan per Unit? <span
                                        class="text-danger">*</span></label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="menggunakan_nomor_seri"
                                            id="editMenggunakanNomorSeriYa" value="1">
                                        <label class="form-check-label" for="editMenggunakanNomorSeriYa">Ya</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="menggunakan_nomor_seri"
                                            id="editMenggunakanNomorSeriTidak" value="0">
                                        <label class="form-check-label"
                                            for="editMenggunakanNomorSeriTidak">Tidak</label>
                                    </div>
                                </div>
                                <div id="infoNomorSeriEdit" class="form-text text-danger"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
        </form>
    </div>
</div>
</div>
