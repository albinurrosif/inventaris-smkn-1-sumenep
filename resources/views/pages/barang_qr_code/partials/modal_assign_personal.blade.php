<div class="modal fade" id="modalAssignPersonal" tabindex="-1" aria-labelledby="modalAssignPersonalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formAssignPersonalAction" action="" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalAssignPersonalLabel">Formulir Serah Terima Unit ke Pemegang
                        Personal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan menyerahkan unit barang: <strong id="assignUnitKodeDisplay"
                            class="text-primary"></strong>.</p>

                    <div class="mb-3">
                        <label for="assignIdPemegangPersonal" class="form-label">Pilih Pemegang Personal <span
                                class="text-danger">*</span></label>
                        <select name="id_pemegang_personal" id="assignIdPemegangPersonal"
                            class="form-select select2-basic" required style="width: 100%;">
                            <option value="">-- Pilih Pengguna --</option>
                            @if (isset($eligibleUsersForAssign) && $eligibleUsersForAssign->count() > 0)
                                @foreach ($eligibleUsersForAssign as $user)
                                    <option value="{{ $user->id }}">{{ $user->username }} ({{ $user->role }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    {{-- ================== PERUBAHAN DI SINI ================== --}}
                    <div class="mb-3">
                        <label for="assign_alasan_pemindahan" class="form-label">Alasan Penyerahan <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control" name="alasan_pemindahan" id="assign_alasan_pemindahan" rows="3" required
                            placeholder="Contoh: Untuk keperluan dinas luar kota..."></textarea>
                    </div>
                    {{-- ======================================================= --}}

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info"><i class="fas fa-user-check me-1"></i> Ya, Serahkan Unit
                        Ini</button>
                </div>
            </div>
        </form>
    </div>
</div>
