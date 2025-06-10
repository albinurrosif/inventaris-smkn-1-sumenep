<section class="space-y-6">
    <header>
        <h4 class="text-lg font-medium text-danger">Hapus Akun</h4>
        <p class="mt-1 text-sm text-gray-600">Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus
            secara permanen. Sebelum menghapus akun Anda, harap unduh data atau informasi apa pun yang ingin Anda
            pertahankan.</p>
    </header>

    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-user-deletion">Hapus
        Akun</button>

    <div class="modal fade" id="confirm-user-deletion" tabindex="-1" aria-labelledby="confirm-user-deletion-label"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
                @csrf
                @method('delete')

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirm-user-deletion-label">Apakah Anda yakin ingin menghapus akun
                            Anda?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mt-1 text-sm text-gray-600">
                            Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen.
                            Silakan masukkan password Anda untuk mengonfirmasi bahwa Anda ingin menghapus akun Anda
                            secara permanen.
                        </p>
                        <div class="mt-3">
                            <label for="password_delete" class="form-label sr-only">Password</label>
                            <input id="password_delete" name="password" type="password" class="form-control"
                                placeholder="Password">
                            @error('password', 'userDeletion')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus Akun</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
