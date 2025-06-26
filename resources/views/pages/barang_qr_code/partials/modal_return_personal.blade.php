<div class="modal fade" id="modalReturnPersonal" tabindex="-1" aria-labelledby="modalReturnPersonalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="formReturnPersonalAction" action="" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalReturnPersonalLabel">Formulir Pengembalian Unit ke Ruangan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan mengembalikan unit: <strong id="returnUnitKodeDisplay" class="text-primary"></strong>.
                    </p>
                    <p>Saat ini dipegang oleh: <strong id="returnUnitPemegangDisplay" class="text-info"></strong>.</p>

                    <div class="mb-3">
                        <label for="returnIdRuanganTujuan" class="form-label">Pilih Ruangan Tujuan <span
                                class="text-danger">*</span></label>
                        <select name="id_ruangan_tujuan" id="returnIdRuanganTujuan" class="form-select select2-basic"
                            required style="width: 100%;">
                            <option value="">-- Pilih Ruangan --</option>
                            @if (isset($ruangansForReturnForm) && $ruangansForReturnForm->count() > 0)
                                @foreach ($ruangansForReturnForm as $ruangan)
                                    <option value="{{ $ruangan->id }}">{{ $ruangan->nama_ruangan }}
                                        ({{ $ruangan->kode_ruangan }})</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    {{-- ================== PERUBAHAN DI SINI ================== --}}
                    <div class="mb-3">
                        <label for="return_alasan_pemindahan" class="form-label">Alasan Pengembalian <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control" name="alasan_pemindahan" id="return_alasan_pemindahan" rows="3" required
                            placeholder="Contoh: Aset sudah tidak digunakan lagi..."></textarea>
                    </div>
                    {{-- ======================================================= --}}

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-undo me-1"></i> Ya, Kembalikan Unit
                        Ini</button>
                </div>
            </div>
        </form>
    </div>
</div>
