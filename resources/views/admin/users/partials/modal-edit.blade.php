{{-- resources/views/admin/users/partials/modal-edit.blade.php --}}
<div class="modal fade" id="modalEditUser" tabindex="-1" aria-labelledby="modalEditUserLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <form id="formEditUserAction" method="POST"> {{-- Action akan di-set oleh JavaScript --}}
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditUserLabelDynamic">Edit User: <span id="editUsernameTitleModal"
                            class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    {{-- Input hidden untuk user ID jika diperlukan, meskipun action URL sudah mengandung ID --}}
                    {{-- <input type="hidden" name="user_id" id="edit_user_id"> --}}
                    <div class="mb-3">
                        <label for="edit_modal_username" class="form-label">Username <span
                                class="text-danger">*</span></label>
                        <input type="text" name="username" id="edit_modal_username"
                            class="form-control @error('username', 'updateUserErrors') is-invalid @enderror" required>
                        @error('username', 'updateUserErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit_modal_email" class="form-label">Email <span
                                class="text-danger">*</span></label>
                        <input type="email" name="email" id="edit_modal_email"
                            class="form-control @error('email', 'updateUserErrors') is-invalid @enderror" required>
                        @error('email', 'updateUserErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit_modal_role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select name="role" id="edit_modal_role"
                            class="form-select @error('role', 'updateUserErrors') is-invalid @enderror" required>
                            @foreach (\App\Models\User::getRoles() as $roleValue)
                                <option value="{{ $roleValue }}">{{ $roleValue }}</option>
                            @endforeach
                        </select>
                        @error('role', 'updateUserErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit_modal_password" class="form-label">Password Baru (Opsional)</label>
                        <input type="password" name="password" id="edit_modal_password"
                            class="form-control @error('password', 'updateUserErrors') is-invalid @enderror"
                            minlength="6" placeholder="Kosongkan jika tidak diubah">
                        @error('password', 'updateUserErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="edit_modal_password_confirmation" class="form-label">Konfirmasi Password
                            Baru</label>
                        <input type="password" name="password_confirmation" id="edit_modal_password_confirmation"
                            class="form-control" minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i> Simpan
                        Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>
