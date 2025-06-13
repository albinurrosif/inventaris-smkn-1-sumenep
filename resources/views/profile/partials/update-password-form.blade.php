<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Ubah Password</h5>
    </div>
    <div class="card-body">
        <p class="text-muted">
            Pastikan akun Anda menggunakan password yang panjang dan acak agar tetap aman.
        </p>

        <form method="post" action="{{ route('password.update') }}">
            @csrf
            @method('put')

            <div class="mb-3">
                <label for="current_password" class="form-label">Password Saat Ini</label>
                <input type="password" name="current_password" id="current_password"
                    class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" required>
                @error('current_password', 'updatePassword')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password Baru</label>
                <input type="password" name="password" id="password"
                    class="form-control @error('password', 'updatePassword') is-invalid @enderror" required>
                @error('password', 'updatePassword')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control"
                    required>
            </div>

            <div class="d-flex align-items-center gap-4">
                <button type="submit" class="btn btn-primary">Simpan</button>

                @if (session('status') === 'password-updated')
                    <p class="text-success mb-0">Password berhasil diperbarui.</p>
                @endif
            </div>
        </form>
    </div>
</div>
