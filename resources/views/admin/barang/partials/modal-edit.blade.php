<!-- Modal Edit Barang -->
<div class="modal fade" id="modalEditBarang" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <form action="" method="POST" id="formEditBarang">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        {{-- Nama & Kode --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Barang</label>
                            <input type="text" name="nama_barang" id="editNamaBarang" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kode Barang</label>
                            <input type="text" name="kode_barang" id="editKodeBarang" class="form-control" required>
                        </div>

                        {{-- Merk & No Seri --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Merk / Model</label>
                            <input type="text" name="merk_model" id="editMerkModel" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">No Seri Pabrik</label>
                            <input type="text" name="no_seri_pabrik" id="editNoSeriPabrik" class="form-control">
                        </div>

                        {{-- Ukuran & Bahan --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ukuran</label>
                            <input type="text" name="ukuran" id="editUkuran" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bahan</label>
                            <input type="text" name="bahan" id="editBahan" class="form-control">
                        </div>

                        {{-- Tahun & Jumlah --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tahun Pembuatan / Pembelian</label>
                            <input type="number" name="tahun_pembuatan_pembelian" id="editTahunPembuatanPembelian"
                                class="form-control" min="1900" max="{{ date('Y') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jumlah Barang</label>
                            <input type="number" name="jumlah_barang" id="editJumlahBarang" class="form-control"
                                required>
                        </div>

                        {{-- Harga & Sumber --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Harga Beli</label>
                            <input type="number" step="0.01" name="harga_beli" id="editHargaBeli"
                                class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sumber</label>
                            <input type="text" name="sumber" id="editSumber" class="form-control">
                        </div>

                        {{-- Keadaan --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Keadaan Barang</label>
                            <select name="keadaan_barang" id="editKeadaanBarang" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <option value="Baik">Baik</option>
                                <option value="Kurang Baik">Kurang Baik</option>
                                <option value="Rusak Berat">Rusak Berat</option>
                            </select>
                        </div>

                        {{-- Keterangan Mutasi --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Keterangan Mutasi</label>
                            <textarea name="keterangan_mutasi" id="editKeteranganMutasi" class="form-control" rows="2"></textarea>
                        </div>

                        {{-- Ruangan --}}
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Ruangan</label>
                            <select name="id_ruangan" id="editIdRuangan" class="form-select" required>
                                <option value="">-- Pilih Ruangan --</option>
                                @foreach ($ruanganList as $ruanganItem)
                                    <option value="{{ $ruanganItem->id }}">{{ $ruanganItem->nama_ruangan }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="formEditBarang" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </div>
    </div>
</div>
