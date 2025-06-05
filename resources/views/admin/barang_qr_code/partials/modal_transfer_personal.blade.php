{{-- resources/views/admin/barang_qr_code/partials/modal_transfer_personal.blade.php --}}
{{-- Variabel $usersForTransferForm dan $barangQrCodeInstance (opsional) diharapkan dari parent view --}}
<div class="modal fade" id="modalTransferPersonal" tabindex="-1" aria-labelledby="modalTransferPersonalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formTransferPersonalAction" action="" method="POST"> {{-- Action akan di-set oleh JavaScript --}}
            @csrf
            @method('POST') {{-- Metode di controller adalah POST --}}
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTransferPersonalLabel">Formulir Transfer Pemegang Personal Unit
                        Barang</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan mentransfer unit barang dengan Kode Inventaris:
                        <strong id="transferUnitKodeDisplay" class="text-primary"></strong>.
                    </p>
                    <p>Saat ini dipegang oleh: <strong id="transferUnitPemegangLamaDisplay" class="text-info"></strong>.
                    </p>
                    <input type="hidden" name="id_barang_qr_code_transfer_hidden" id="transferUnitIdHidden">

                    <div class="mb-3">
                        <label for="transferNewIdPemegangPersonal" class="form-label">Pilih Pemegang Personal Baru <span
                                class="text-danger">*</span></label>
                        <select name="new_id_pemegang_personal" id="transferNewIdPemegangPersonal"
                            class="form-select select2-basic" required style="width: 100%;">
                            <option value="">-- Pilih Pengguna Baru --</option>
                            {{-- Variabel $usersForTransferForm dikirim dari view show.blade.php --}}
                            @if (isset($usersForTransferForm) && $usersForTransferForm->count() > 0)
                                @foreach ($usersForTransferForm as $user)
                                    <option value="{{ $user->id }}">{{ $user->username }} ({{ $user->role }})
                                    </option>
                                @endforeach
                            @else
                                <option value="" disabled>Tidak ada pengguna lain yang bisa dipilih.</option>
                            @endif
                        </select>
                        {{-- Error handling bisa ditambahkan di sini oleh JavaScript jika diperlukan --}}
                    </div>

                    {{-- Input Tanggal Transfer (opsi A: otomatis, tidak ada di form) --}}
                    {{-- Jika menggunakan Opsi B (readonly display):
                    <div class="mb-3">
                        <label for="transferTanggalDisplay" class="form-label">Tanggal Transfer</label>
                        <input type="date" id="transferTanggalDisplay" class="form-control" value="{{ now()->toDateString() }}" readonly>
                    </div>
                    --}}

                    <div class="mb-3">
                        <label for="transferCatatan" class="form-label">Catatan Transfer (Opsional)</label>
                        <textarea class="form-control" name="catatan_transfer_personal" id="transferCatatan" rows="3"
                            placeholder="Catatan terkait transfer unit ini."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" @if (!isset($usersForTransferForm) || $usersForTransferForm->isEmpty()) disabled @endif>
                        <i class="fas fa-exchange-alt me-1"></i> Ya, Transfer Unit Ini
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
