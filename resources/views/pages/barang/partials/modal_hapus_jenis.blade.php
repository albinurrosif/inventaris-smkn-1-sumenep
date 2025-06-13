{{-- resources/views/admin/barang/partials/modal_hapus_jenis_barang.blade.php --}}
{{--
    Modal ini digunakan untuk konfirmasi akhir dan pengisian detail saat menghapus
    sebuah Jenis Barang (Induk) beserta semua unit fisiknya.
    Form ini akan menargetkan route('barang.destroy', [id_jenis_barang]),
    yang dihandle oleh metode `destroy` di BarangController.
    Metode `destroy` di BarangController akan melakukan:
    1. Validasi input dari modal ini.
    2. Membuat entri ArsipBarang untuk setiap unit terkait menggunakan data dari modal ini.
    3. Soft delete semua unit BarangQrCode terkait.
    4. Soft delete Jenis Barang (Barang) itu sendiri.
--}}
<div class="modal fade" id="modalHapusJenisBarang" tabindex="-1" aria-labelledby="modalHapusJenisBarangLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        {{-- Form action akan di-set oleh JavaScript dari tombol trigger di view admin.barang.show --}}
        <form id="formHapusJenisBarangAction" action="" method="POST" enctype="multipart/form-data">
            @csrf
            @method('DELETE') {{-- Sesuai untuk route barang.destroy --}}
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalHapusJenisBarangLabel">Konfirmasi Hapus Jenis Barang & Arsipkan
                        Semua Unit</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p>Anda akan menghapus jenis barang <strong id="hapusJenisBarangNamaDisplayModal"
                            class="text-danger"></strong>.
                        Tindakan ini akan mengarsipkan semua (<strong id="hapusJenisBarangJumlahUnitDisplayModal"
                            class="text-danger"></strong>) unit fisik aktif yang terkait.
                    </p>
                    <p><small>Data unit akan dipindahkan ke modul Arsip Barang. Jenis barang ini juga akan
                            di-soft-delete.</small></p>

                    <div class="mb-3">
                        <label for="hapus_induk_jenis_penghapusan" class="form-label">Alasan Umum Pengarsipan Unit <span
                                class="text-danger">*</span></label>
                        <select name="jenis_penghapusan" id="hapus_induk_jenis_penghapusan" class="form-select"
                            required>
                            <option value="" disabled selected>-- Pilih Alasan Umum --</option>
                            {{-- Opsi ini harus ada di ArsipBarang::getValidJenisPenghapusan() atau ditangani khusus di Controller --}}
                            <option value="Usang">Usang/Obsolete (Seluruh Jenis Barang)</option>
                            <option value="Dimusnahkan">Dimusnahkan (Seluruh Jenis Barang)</option>
                            <option value="Lain-lain">Lain-lain (Jelaskan di Alasan Detail)</option>
                            {{-- Pastikan opsi ini konsisten dengan ArsipBarang::getValidJenisPenghapusan() --}}
                            {{-- Contoh jika Anda ingin menggunakan enum yang ada: --}}
                            {{-- @foreach (\App\Models\ArsipBarang::getValidJenisPenghapusan() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach --}}
                        </select>
                        <small class="text-muted">Alasan ini akan diterapkan pada semua unit yang diarsipkan.</small>
                    </div>

                    <div class="mb-3">
                        <label for="hapus_induk_alasan_penghapusan" class="form-label">Detail Alasan Umum <span
                                class="text-danger">*</span></label>
                        <textarea class="form-control" name="alasan_penghapusan" id="hapus_induk_alasan_penghapusan" rows="3" required
                            placeholder="Jelaskan alasan mengapa jenis barang ini beserta semua unitnya dihapus/diarsip."></textarea>
                        <small class="text-muted">Detail ini akan diterapkan untuk semua unit yang diarsipkan.</small>
                    </div>

                    <div class="mb-3">
                        <label for="hapus_induk_berita_acara" class="form-label">Upload Berita Acara Umum
                            (Opsional)</label>
                        <input type="file" name="berita_acara_path" id="hapus_induk_berita_acara"
                            class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <small class="text-muted">Dokumen berita acara yang mencakup penghapusan semua unit ini.</small>
                    </div>

                    <div class="mb-3">
                        <label for="hapus_induk_foto_bukti" class="form-label">Upload Foto Bukti Umum (Opsional)</label>
                        <input type="file" name="foto_bukti_path" id="hapus_induk_foto_bukti" class="form-control"
                            accept="image/*">
                        <small class="text-muted">Foto umum yang mendukung penghapusan massal ini.</small>
                    </div>

                    <div class="alert alert-danger mt-3">
                        <p class="mb-1 fw-bold">KONFIRMASI TINDAKAN BERISIKO TINGGI:</p>
                        <small>Untuk melanjutkan penghapusan jenis barang <strong
                                id="konfirmasiNamaBarangIndukModal"></strong> dan semua unitnya, ketik "<strong>HAPUS
                                SEMUA</strong>" pada kolom di bawah ini.</small>
                        <input type="text" name="konfirmasi_hapus_semua" class="form-control mt-2"
                            placeholder='Ketik: HAPUS SEMUA' required pattern="HAPUS SEMUA"
                            oninvalid="this.setCustomValidity('Mohon ketik \'HAPUS SEMUA\' untuk konfirmasi.')"
                            oninput="this.setCustomValidity('')">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-skull-crossbones me-1"></i> Ya, Hapus
                        Jenis & Arsipkan Unit</button>
                </div>
            </div>
        </form>
    </div>
</div>
