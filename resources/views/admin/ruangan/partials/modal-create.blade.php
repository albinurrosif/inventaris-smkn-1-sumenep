<div class="modal fade" id="modalTambahRuangan" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('ruangan.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah Ruangan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="nama_ruangan" class="form-label">Nama Ruangan</label>
                    <input type="text" name="nama_ruangan" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="id_operator" class="form-label">Operator</label>
                    <select name="id_operator" class="form-select">
                        <option value="">-- Pilih Operator --</option>
                        @foreach ($operators as $operator)
                            <option value="{{ $operator->id }}">{{ $operator->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
