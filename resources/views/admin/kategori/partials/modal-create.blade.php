{{-- resources/views/admin/kategori/partials/modal-create.blade.php --}}
<div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-labelledby="modalTambahKategoriLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTambahKategoriLabel">Tambah Kategori Barang Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- Menggunakan route dengan prefix admin --}}
            <form action="{{ route('admin.kategori-barang.store') }}" method="POST" id="formTambahKategoriAction">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nama_kategori_create_modal" class="form-label">Nama Kategori <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_kategori_create_modal" name="nama_kategori"
                            value="{{ old('nama_kategori') }}" required>
                        {{-- Pesan error akan ditangani oleh Laravel validation jika redirect back with errors --}}
                        {{-- Jika menggunakan AJAX, validasi error akan ditangani di JavaScript --}}
                        @error('nama_kategori')
                            {{-- Menampilkan error di bawah field jika ada --}}
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    {{-- Field Deskripsi dihapus karena tidak ada di model KategoriBarang --}}
                    {{-- <div class="mb-3">
                        <label for="deskripsi_create_modal" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi_create_modal" name="deskripsi" rows="3">{{ old('deskripsi') }}</textarea>
                    </div> --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan
                        Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>
