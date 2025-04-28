<div class="modal fade" id="modalEditUser" tabindex="-1" aria-labelledby="modalEditUserLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formEditUser" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditUserLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nama</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role</label>
                        <select name="role" id="edit_role" class="form-select" required>
                            @foreach (\App\Models\User::getRoles() as $role)
                                <option value="{{ $role }}">{{ $role }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Password Baru (Opsional)</label>
                        <input type="password" name="password" id="edit_password" class="form-control" minlength="6"
                            placeholder="Kosongkan jika tidak diubah">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">Update</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>
