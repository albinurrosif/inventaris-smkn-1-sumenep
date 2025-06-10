<div class="modal fade" id="finalizeApprovalModal" tabindex="-1" aria-labelledby="finalizeApprovalModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="finalizeApprovalModalLabel">Finalisasi Proses Persetujuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- Form ini menargetkan route finalize yang baru --}}
            <form action="{{ route($rolePrefix . 'peminjaman.finalize', $peminjaman->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Anda akan menyelesaikan proses persetujuan untuk peminjaman ini. Sistem akan mengubah status
                        transaksi berdasarkan item yang telah Anda setujui atau tolak.</p>
                    <p>Pastikan Anda telah meninjau semua item.</p>

                    <div class="mt-3">
                        <label for="catatan_final" class="form-label">Catatan Operator Final (Opsional)</label>
                        <textarea class="form-control" name="catatan_final" id="catatan_final" rows="3"
                            placeholder="Contoh: 2 dari 3 barang disetujui. Silakan hubungi operator untuk info lebih lanjut."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check-double me-1"></i> Ya, Finalisasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
