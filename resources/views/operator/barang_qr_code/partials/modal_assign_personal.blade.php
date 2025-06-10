{{-- resources/views/admin/barang_qr_code/partials/modal_assign_personal.blade.php --}}
{{-- Variabel $usersForAssignForm dan $barangQrCodeInstance diharapkan dari parent view --}}
<div class="modal fade" id="modalAssignPersonal" tabindex="-1" aria-labelledby="modalAssignPersonalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formAssignPersonalAction" action="" method="POST"> {{-- Action akan di-set oleh JavaScript --}}
            @csrf
            @method('POST')
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalAssignPersonalLabel">Formulir Serah Terima Unit ke Pemegang
                        Personal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan menyerahkan unit barang dengan Kode Inventaris:
                        <strong id="assignUnitKodeDisplay" class="text-primary"></strong>.
                    </p>
                    <input type="hidden" name="id_barang_qr_code_assign_hidden" id="assignUnitIdHidden">

                    <div class="mb-3">
                        <label for="assignIdPemegangPersonal" class="form-label">Pilih Pemegang Personal <span
                                class="text-danger">*</span></label>
                        <select name="id_pemegang_personal" id="assignIdPemegangPersonal"
                            class="form-select select2-basic" required style="width: 100%;">
                            <option value="">-- Pilih Pengguna --</option>
                            {{-- Variabel $usersForAssignForm dikirim dari view show.blade.php --}}
                            @if (isset($usersForAssignForm) && $usersForAssignForm->count() > 0)
                                @foreach ($usersForAssignForm as $user)
                                    <option value="{{ $user->id }}">{{ $user->username }} ({{ $user->role }})
                                    </option>
                                @endforeach
                            @else
                                <option value="" disabled>Tidak ada pengguna eligible.</option>
                            @endif
                        </select>
                        {{-- Error handling bisa ditambahkan di sini oleh JavaScript jika diperlukan --}}
                    </div>

                    {{-- Input Tanggal Penyerahan (opsi A: otomatis, tidak ada di form) --}}
                    {{-- Jika menggunakan Opsi B (readonly display):
                    <div class="mb-3">
                        <label for="assignTanggalPenyerahanDisplay" class="form-label">Tanggal Penyerahan</label>
                        <input type="date" id="assignTanggalPenyerahanDisplay" class="form-control" value="{{ now()->toDateString() }}" readonly>
                    </div>
                    --}}

                    <div class="mb-3">
                        <label for="assignCatatanPenyerahan" class="form-label">Catatan (Opsional)</label>
                        <textarea class="form-control" name="catatan_penyerahan_personal" id="assignCatatanPenyerahan" rows="3"
                            placeholder="Catatan terkait penyerahan unit ini."></textarea>
                    </div>
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
