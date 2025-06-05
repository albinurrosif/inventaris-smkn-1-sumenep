{{-- resources/views/admin/barang_qr_code/partials/modal_return_personal.blade.php --}}
{{-- Variabel $ruangansForReturnForm dan $barangQrCodeInstance (opsional) diharapkan dari parent view --}}
<div class="modal fade" id="modalReturnPersonal" tabindex="-1" aria-labelledby="modalReturnPersonalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formReturnPersonalAction" action="" method="POST"> {{-- Action akan di-set oleh JavaScript --}}
            @csrf
            @method('POST') {{-- Metode di controller adalah POST --}}
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalReturnPersonalLabel">Formulir Pengembalian Unit ke Ruangan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan mengembalikan unit barang dengan Kode Inventaris:
                        <strong id="returnUnitKodeDisplay" class="text-primary"></strong>.
                    </p>
                    <p>Saat ini dipegang oleh: <strong id="returnUnitPemegangDisplay" class="text-info"></strong>.</p>
                    <input type="hidden" name="id_barang_qr_code_return_hidden" id="returnUnitIdHidden">

                    <div class="mb-3">
                        <label for="returnIdRuanganTujuan" class="form-label">Pilih Ruangan Tujuan Pengembalian <span
                                class="text-danger">*</span></label>
                        <select name="id_ruangan_tujuan" id="returnIdRuanganTujuan" class="form-select select2-basic"
                            required style="width: 100%;">
                            <option value="">-- Pilih Ruangan --</option>
                            {{-- Variabel $ruangansForReturnForm dikirim dari view show.blade.php --}}
                            @if (isset($ruangansForReturnForm) && $ruangansForReturnForm->count() > 0)
                                @foreach ($ruangansForReturnForm as $ruangan)
                                    <option value="{{ $ruangan->id }}">{{ $ruangan->nama_ruangan }}
                                        ({{ $ruangan->kode_ruangan }})</option>
                                @endforeach
                            @else
                                <option value="" disabled>Tidak ada ruangan yang bisa dipilih (Operator mungkin
                                    tidak mengelola ruangan).</option>
                            @endif
                        </select>
                        {{-- Error handling bisa ditambahkan di sini oleh JavaScript jika diperlukan --}}
                    </div>

                    {{-- Input Tanggal Pengembalian (opsi A: otomatis, tidak ada di form) --}}
                    {{-- Jika menggunakan Opsi B (readonly display):
                    <div class="mb-3">
                        <label for="returnTanggalPengembalianDisplay" class="form-label">Tanggal Pengembalian</label>
                        <input type="date" id="returnTanggalPengembalianDisplay" class="form-control" value="{{ now()->toDateString() }}" readonly>
                    </div>
                    --}}

                    <div class="mb-3">
                        <label for="returnCatatanPengembalian" class="form-label">Catatan Pengembalian
                            (Opsional)</label>
                        <textarea class="form-control" name="catatan_pengembalian_ruangan" id="returnCatatanPengembalian" rows="3"
                            placeholder="Catatan terkait pengembalian unit ini."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" @if (!isset($ruangansForReturnForm) || $ruangansForReturnForm->isEmpty()) disabled @endif>
                        <i class="fas fa-undo me-1"></i> Ya, Kembalikan Unit Ini
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
