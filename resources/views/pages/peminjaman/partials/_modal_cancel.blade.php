<div class="modal fade" id="cancelPeminjamanModal" tabindex="-1" aria-labelledby="cancelPeminjamanModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route($rolePrefix . 'peminjaman.cancelByUser', $peminjaman->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelPeminjamanModalLabel">Batalkan Pengajuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin membatalkan pengajuan peminjaman ini?</p>
                    <div class="mb-3">
                        <label for="alasan_pembatalan" class="form-label">Alasan Pembatalan (Opsional)</label>
                        <textarea class="form-control" id="alasan_pembatalan" name="alasan_pembatalan" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-warning text-dark">Ya, Batalkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
