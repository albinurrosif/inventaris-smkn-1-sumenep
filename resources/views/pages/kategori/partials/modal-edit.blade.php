{{-- resources/views/admin/kategori/partials/modal-edit.blade.php --}}
<div class="modal fade" id="modalEditKategori" tabindex="-1" aria-labelledby="modalEditKategoriLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditKategoriLabelDynamic">Edit Kategori: <span
                        id="editNamaKategoriTitleModal" class="fw-bold"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditKategoriAction" method="POST"> {{-- Action di-set oleh JavaScript --}}
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_modal_nama_kategori" class="form-label">Nama Kategori <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_modal_nama_kategori" name="nama_kategori"
                            required>
                        {{-- Pesan error akan ditangani oleh Laravel validation jika redirect back with errors --}}
                        @error('nama_kategori', 'updateKategoriErrors')
                            {{-- Menggunakan error bag jika perlu --}}
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    {{-- Field Deskripsi dihapus --}}
                    {{-- <div class="mb-3">
                        <label for="edit_modal_deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="edit_modal_deskripsi" name="deskripsi" rows="3"></textarea>
                    </div> --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i> Simpan
                        Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
