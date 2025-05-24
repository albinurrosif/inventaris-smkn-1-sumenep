<div class="modal fade" id="modalEditRuangan" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <form method="POST" class="modal-content" id="formEditRuangan">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Edit Ruangan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="edit_nama_ruangan" class="form-label">Nama Ruangan</label>
                    <input type="text" name="nama_ruangan" id="edit_nama_ruangan" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="edit_id_operator" class="form-label">Operator</label>
                    <select name="id_operator" id="edit_id_operator" class="form-select">
                        <option value="">-- Pilih Operator --</option>
                        @foreach ($operators as $operator)
                            <option value="{{ $operator->id }}">{{ $operator->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
