{{-- resources/views/admin/barang_qr_code/partials/modal_arsip_unit.blade.php --}}
{{--
    Modal ini digunakan untuk mengumpulkan detail saat admin mengajukan pengarsipan
    untuk SATU UNIT BARANG SPESIFIK (BarangQrCode).
    Form ini akan menargetkan route('barang-qr-code.archive', [id_unit]),
    yang dihandle oleh metode `archive` di BarangQrCodeController menggunakan metode POST.
--}}
<div class="modal fade" id="modalArsipUnit" tabindex="-1" aria-labelledby="modalArsipUnitLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        {{-- Form action akan di-set oleh JavaScript dari tombol trigger. Methodnya adalah POST. --}}
        <form id="formArsipUnitAction" action="" method="POST" enctype="multipart/form-data">
            @csrf
            {{-- @method('DELETE') DIHAPUS karena route 'archive' menggunakan POST --}}

            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalArsipUnitLabel">Formulir Pengajuan Arsip Unit Barang</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan mengajukan pengarsipan untuk unit barang dengan Kode Inventaris:
                        <strong id="arsipUnitKodeDisplay" class="text-danger"></strong>.
                    </p>
                    <p><small>Status unit akan diubah menjadi "Dalam Proses Arsip", dan data detailnya akan dicatat di
                            modul Arsip Barang menunggu persetujuan (jika ada alur persetujuan).</small></p>

                    {{-- Input hidden untuk ID unit tidak diperlukan karena ID sudah ada di URL action form --}}

                    <div class="mb-3">
                        <label for="arsip_jenis_penghapusan_unit" class="form-label">Jenis Penghapusan/Alasan Utama
                            <span class="text-danger">*</span></label>
                        <select name="jenis_penghapusan" id="arsip_jenis_penghapusan_unit" class="form-select" required>
                            <option value="" disabled selected>-- Pilih Jenis/Alasan --</option>
                            {{-- Menggunakan metode statis dari model ArsipBarang untuk konsistensi --}}
                            @if (class_exists(\App\Models\ArsipBarang::class) &&
                                    method_exists(\App\Models\ArsipBarang::class, 'getValidJenisPenghapusan'))
                                @foreach (\App\Models\ArsipBarang::getValidJenisPenghapusan() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            @else
                                {{-- Fallback jika metode tidak ada, atau tambahkan opsi manual yang sesuai --}}
                                <option value="Rusak Berat">Rusak Berat (Tidak Dapat Diperbaiki)</option>
                                <option value="Hilang">Hilang</option>
                                <option value="Dimusnahkan">Dimusnahkan (Sesuai Prosedur)</option>
                                <option value="Dijual">Dijual (Sesuai Prosedur)</option>
                                <option value="Dihibahkan">Dihibahkan</option>
                                <option value="Lain-lain">Lain-lain (Jelaskan di Alasan Detail)</option>
                            @endif
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="arsip_alasan_penghapusan_unit_detail" class="form-label">Alasan Detail <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control" name="alasan_penghapusan" id="arsip_alasan_penghapusan_unit_detail" rows="3"
                            required placeholder="Jelaskan secara detail mengapa unit ini diarsipkan."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="arsip_berita_acara_unit" class="form-label">Upload Berita Acara (Opsional)</label>
                        <input type="file" name="berita_acara_path" id="arsip_berita_acara_unit" class="form-control"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"> <small class="text-muted">Dokumen berita acara
                            terkait pengarsipan unit ini.</small>
                    </div>

                    <div class="mb-3">
                        <label for="arsip_foto_bukti_unit" class="form-label">Upload Foto Bukti (Opsional)</label>
                        <input type="file" name="foto_bukti_path" id="arsip_foto_bukti_unit" class="form-control"
                            accept="image/*"> <small class="text-muted">Foto kondisi barang, bukti kehilangan, atau
                            bukti lainnya.</small>
                    </div>

                    {{-- Konfirmasi ketik bisa dipertahankan untuk UX --}}
                    <div class="alert alert-warning mt-3">
                        <p class="mb-1 fw-bold">Konfirmasi Tindakan:</p>
                        <small>Untuk melanjutkan pengajuan arsip unit <strong id="konfirmasiArsipKodeUnit"></strong>,
                            ketik "<strong>ARSIPKAN</strong>" pada kolom di bawah ini.</small>
                        <input type="text" name="konfirmasi_arsip_unit" class="form-control mt-2"
                            placeholder='Ketik: ARSIPKAN' required pattern="ARSIPKAN"
                            oninvalid="this.setCustomValidity('Mohon ketik \'ARSIPKAN\' untuk konfirmasi.')"
                            oninput="this.setCustomValidity('')">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-archive me-1"></i> Ya, Ajukan Arsip
                        Unit Ini</button>
                </div>
            </div>
        </form>
    </div>
</div>
