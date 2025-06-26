<div class="modal fade" id="modalTransferPersonal" tabindex="-1" aria-labelledby="modalTransferPersonalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formTransferPersonalAction" action="" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTransferPersonalLabel">Formulir Transfer Pemegang Personal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan mentransfer unit: <strong id="transferUnitKodeDisplay" class="text-primary"></strong>.
                    </p>
                    <p>Saat ini dipegang oleh: <strong id="transferUnitPemegangLamaDisplay" class="text-info"></strong>.
                    </p>

                    <div class="mb-3">
                        <label for="transferNewIdPemegangPersonal" class="form-label">Pilih Pemegang Personal Baru <span
                                class="text-danger">*</span></label>
                        <select name="new_id_pemegang_personal" id="transferNewIdPemegangPersonal"
                            class="form-select select2-basic" required style="width: 100%;">
                            <option value="">-- Pilih Pengguna Baru --</option>
                            @if (isset($eligibleUsersForTransfer) && $eligibleUsersForTransfer->count() > 0)
                                @foreach ($eligibleUsersForTransfer as $user)
                                    <option value="{{ $user->id }}">{{ $user->username }} ({{ $user->role }})
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    {{-- ================== PERUBAHAN DI SINI ================== --}}
                    <div class="mb-3">
                        <label for="transfer_alasan_pemindahan" class="form-label">Alasan Transfer <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control" name="alasan_pemindahan" id="transfer_alasan_pemindahan" rows="3" required
                            placeholder="Contoh: Alih tanggung jawab proyek..."></textarea>
                    </div>
                    {{-- ======================================================= --}}

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" @if (!isset($eligibleUsersForTransfer) || $eligibleUsersForTransfer->isEmpty()) disabled @endif>
                        <i class="fas fa-exchange-alt me-1"></i> Ya, Transfer Unit Ini
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
