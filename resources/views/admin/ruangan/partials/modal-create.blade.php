{{-- resources/views/admin/ruangan/partials/modal-create.blade.php --}}
<div class="modal fade" id="modalTambahRuangan" tabindex="-1" aria-labelledby="modalTambahRuanganLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.ruangan.store') }}" class="modal-content"
            id="formTambahRuanganAction">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="modalTambahRuanganLabel">Tambah Ruangan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="nama_ruangan_create_modal" class="form-label">Nama Ruangan <span
                            class="text-danger">*</span></label>
                    <input type="text"
                        class="form-control @error('nama_ruangan', 'storeRuanganErrors') is-invalid @enderror"
                        id="nama_ruangan_create_modal" name="nama_ruangan" value="{{ old('nama_ruangan') }}" required>
                    @error('nama_ruangan', 'storeRuanganErrors')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="kode_ruangan_create_modal" class="form-label">Kode Ruangan <span
                            class="text-danger">*</span></label>
                    <input type="text"
                        class="form-control @error('kode_ruangan', 'storeRuanganErrors') is-invalid @enderror"
                        id="kode_ruangan_create_modal" name="kode_ruangan" value="{{ old('kode_ruangan') }}" required>
                    @error('kode_ruangan', 'storeRuanganErrors')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="id_operator_create_modal" class="form-label">Operator Penanggung Jawab</label>
                    <select name="id_operator" id="id_operator_create_modal" class="form-select">
                        <option value="">-- Tidak Ada / Pilih Operator --</option>
                        @if (isset($operators) && $operators->count() > 0)
                            @foreach ($operators as $operator)
                                <option value="{{ $operator->id }}"
                                    {{ old('id_operator') == $operator->id ? 'selected' : '' }}>
                                    {{ $operator->username }} ({{ $operator->email }})
                                </option>
                            @endforeach
                        @else
                            <option value="" disabled>Tidak ada operator tersedia</option>
                        @endif
                    </select>
                    @error('id_operator', 'storeRuanganErrors')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan Ruangan</button>
            </div>
        </form>
    </div>
</div>
