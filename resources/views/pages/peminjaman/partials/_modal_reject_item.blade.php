{{-- Modal ini akan di-include di dalam loop, sehingga $detail akan tersedia --}}
<div class="modal fade" id="rejectItemModal{{ $detail->id }}" tabindex="-1"
    aria-labelledby="rejectItemModalLabel{{ $detail->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectItemModalLabel{{ $detail->id }}">Tolak Item Peminjaman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{-- Form ini tidak punya action, akan di-handle oleh JavaScript --}}
            <form class="form-reject-item">
                @csrf
                <div class="modal-body">
                    <p>Anda akan menolak item berikut dari daftar peminjaman:</p>
                    <p class="fw-bold fst-italic">"{{ optional(optional($detail->barangQrCode)->barang)->nama_barang }}
                        ({{ optional($detail->barangQrCode)->kode_inventaris_sekolah }})"</p>

                    <div class="mt-3">
                        <label for="alasan_penolakan_{{ $detail->id }}" class="form-label">Alasan Penolakan <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control" name="alasan" id="alasan_penolakan_{{ $detail->id }}" rows="3"
                            placeholder="Contoh: Barang sedang dalam pemeliharaan." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    {{-- Tombol submit ini akan ditangkap oleh JavaScript --}}
                    <button type="submit" class="btn btn-danger btn-submit-reject"
                        data-detail-id="{{ $detail->id }}">
                        <i class="fas fa-times-circle me-1"></i> Ya, Tolak Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
