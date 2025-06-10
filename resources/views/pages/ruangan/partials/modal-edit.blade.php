{{-- resources/views/admin/ruangan/partials/modal-edit.blade.php --}}
<div class="modal fade" id="modalEditRuangan" tabindex="-1" aria-labelledby="modalEditRuanganLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">

        <form method="POST" class="modal-content" id="formEditRuanganAction"> {{-- Action di-set oleh JavaScript --}}
            @csrf
            <input type="hidden" name="form_type" value="edit">

            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditRuanganLabelDynamic">Edit Ruangan: <span
                        id="editNamaRuanganTitleModal" class="fw-bold"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if ($errors->updateRuanganErrors->any())
                    <div class="alert alert-danger">
                        <h5 class="alert-heading">Gagal Memperbarui Ruangan!</h5>
                        <ul class="mb-0">
                            {{-- Loop dan tampilkan setiap pesan error --}}
                            @foreach ($errors->updateRuanganErrors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <input type="hidden" name="ruangan_id" id="edit_ruangan_id">
                <div class="mb-3">
                    <label for="edit_modal_nama_ruangan" class="form-label">Nama Ruangan <span
                            class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="edit_modal_nama_ruangan" name="nama_ruangan"
                        required>
                    @error('nama_ruangan', 'updateRuanganErrors')
                        {{-- Error bag spesifik untuk update --}}
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="edit_modal_kode_ruangan" class="form-label">Kode Ruangan <span
                            class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="edit_modal_kode_ruangan" name="kode_ruangan"
                        required>
                    @error('kode_ruangan', 'updateRuanganErrors')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="edit_modal_id_operator" class="form-label">Operator Penanggung Jawab</label>
                    <select name="id_operator" id="edit_modal_id_operator" class="form-select">
                        <option value="">-- Tidak Ada / Pilih Operator --</option>
                        {{-- Variabel $operators harus tersedia di view yang meng-include partial ini --}}
                        {{-- Jika tidak, loop ini akan error. Pastikan $operators di-pass ke view index. --}}
                        @if (isset($operators) && $operators->count() > 0)
                            @foreach ($operators as $operator)
                                <option value="{{ $operator->id }}">{{ $operator->username }} ({{ $operator->email }})
                                </option>
                            @endforeach
                        @else
                            <option value="" disabled>Tidak ada operator tersedia</option>
                        @endif
                    </select>
                    @error('id_operator', 'updateRuanganErrors')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i> Simpan
                    Perubahan</button>
            </div>
        </form>
    </div>
</div>
