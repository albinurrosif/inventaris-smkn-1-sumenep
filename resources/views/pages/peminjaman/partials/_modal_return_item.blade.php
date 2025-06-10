<div class="modal fade" id="returnItemModal{{ $detail->id }}" tabindex="-1"
    aria-labelledby="returnItemModalLabel{{ $detail->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            {{-- PENYESUAIAN: Hapus action & method, tambahkan class & data-attribute --}}
            <form class="form-return-item" data-detail-id="{{ $detail->id }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="returnItemModalLabel{{ $detail->id }}">Proses Pengembalian Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan memproses pengembalian untuk unit:
                        <br><strong>{{ optional(optional($detail->barangQrCode)->barang)->nama_barang }}
                            ({{ optional($detail->barangQrCode)->kode_inventaris_sekolah }})</strong>.
                    </p>
                    <div class="mb-3">
                        <label for="kondisi_setelah_kembali_{{ $detail->id }}" class="form-label">
                            Kondisi Barang Saat Dikembalikan <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="kondisi_setelah_kembali_{{ $detail->id }}"
                            name="kondisi_setelah_kembali" required>
                            {{-- Opsi default 'Pilih Kondisi' agar pengguna wajib memilih --}}
                            <option value="" disabled selected>-- Pilih Kondisi --</option>
                            @foreach ($kondisiList as $kondisi)
                                <option value="{{ $kondisi }}" {{-- Jika kondisi sebelum sama, buat ini sebagai pilihan default --}}
                                    {{ $detail->kondisi_sebelum == $kondisi ? 'selected' : '' }}>
                                    {{ $kondisi }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="catatan_pengembalian_unit_{{ $detail->id }}" class="form-label">
                            Catatan Tambahan (Opsional)
                        </label>
                        <textarea class="form-control" id="catatan_pengembalian_unit_{{ $detail->id }}" name="catatan_pengembalian_unit"
                            rows="3" placeholder="Contoh: Ada goresan kecil di bagian atas."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    {{-- PENYESUAIAN: Tambahkan class untuk target JS --}}
                    <button type="submit" class="btn btn-primary btn-submit-return">Simpan Status Pengembalian</button>
                </div>
            </form>
        </div>
    </div>
</div>
