{{-- resources/views/admin/users/partials/modal-create.blade.php --}}
<div class="modal fade" id="modalTambahUser" tabindex="-1" aria-labelledby="modalTambahUserLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <form action="{{ route('admin.users.store') }}" method="POST" id="formTambahUserAction">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTambahUserLabel">Tambah User Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username_create_modal" class="form-label">Username <span
                                class="text-danger">*</span></label>
                        <input type="text" name="username" id="username_create_modal"
                            class="form-control @error('username', 'storeUserErrors') is-invalid @enderror"
                            value="{{ old('username') }}" required>
                        @error('username', 'storeUserErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="email_create_modal" class="form-label">Email <span
                                class="text-danger">*</span></label>
                        <input type="email" name="email" id="email_create_modal"
                            class="form-control @error('email', 'storeUserErrors') is-invalid @enderror"
                            value="{{ old('email') }}" required>
                        @error('email', 'storeUserErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="role_create_modal" class="form-label">Role <span
                                class="text-danger">*</span></label>
                        <select name="role" id="role_create_modal"
                            class="form-select @error('role', 'storeUserErrors') is-invalid @enderror" required>
                            <option value="">-- Pilih Role --</option>
                            @foreach (\App\Models\User::getRoles() as $roleValue)
                                <option value="{{ $roleValue }}" {{ old('role') == $roleValue ? 'selected' : '' }}>
                                    {{ $roleValue }}</option>
                            @endforeach
                        </select>
                        @error('role', 'storeUserErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password_create_modal" class="form-label">Password <span
                                class="text-danger">*</span></label>
                        <input type="password" name="password" id="password_create_modal"
                            class="form-control @error('password', 'storeUserErrors') is-invalid @enderror" required
                            minlength="6">
                        @error('password', 'storeUserErrors')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation_create_modal" class="form-label">Konfirmasi Password <span
                                class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" id="password_confirmation_create_modal"
                            class="form-control" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan User</button>
                </div>
            </div>
        </form>
    </div>
</div>
