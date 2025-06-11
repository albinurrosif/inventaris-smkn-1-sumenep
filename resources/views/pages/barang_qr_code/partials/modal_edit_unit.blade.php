{{-- resources/views/admin/barang_qr_code/partials/modal_edit_unit.blade.php --}}
<div class="modal fade" id="modalEditUnitBarang" tabindex="-1" aria-labelledby="modalEditUnitBarangLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl"> {{-- Modal lebih besar untuk banyak field --}}
        {{-- Form action akan di-set oleh JavaScript --}}
        <form id="formEditUnitBarangAction" action="" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="modalEditUnitBarangLabel">Edit Detail Unit Barang: <span
                            id="editUnitKodeInventarisDisplay" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_barang_qr_code_hidden" id="editUnitId">

                    <div class="alert alert-info" role="alert">
                        <p class="mb-1"><strong>Jenis Barang (Induk):</strong> <span
                                id="editUnitJenisBarangDisplay">-</span></p>
                        <p class="mb-0"><strong>Merk/Model:</strong> <span id="editUnitMerkModelDisplay">-</span></p>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editUnitKodeInventarisSekolah" class="form-label">Kode Inventaris Sekolah
                                    <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editUnitKodeInventarisSekolah"
                                    name="kode_inventaris_sekolah" required>
                                <small class="form-text text-muted">Jika diubah, QR Code mungkin perlu digenerate
                                    ulang.</small>
                            </div>

                            <div class="mb-3">
                                <label for="editUnitNoSeriPabrik" class="form-label">Nomor Seri Pabrik</label>
                                <input type="text" class="form-control" id="editUnitNoSeriPabrik"
                                    name="no_seri_pabrik">
                                <small id="infoNoSeriPabrikEditUnit" class="form-text text-muted"></small>
                            </div>

                            <div class="mb-3">
                                <label for="editUnitIdRuangan" class="form-label">Lokasi Penempatan (Ruangan)</label>
                                <select class="form-select" id="editUnitIdRuangan" name="id_ruangan">
                                    <option value="">-- Tidak Ditempatkan di Ruangan --</option>
                                    {{-- $ruanganList akan di-pass dari view induk atau di-load via JS jika banyak --}}
                                    @if (isset($ruanganList) && $ruanganList->count() > 0)
                                        @foreach ($ruanganList as $ruangan)
                                            <option value="{{ $ruangan->id }}">{{ $ruangan->nama_ruangan }}
                                                ({{ $ruangan->kode_ruangan }})</option>
                                        @endforeach
                                    @else
                                        <option value="" disabled>Ruangan tidak tersedia</option>
                                    @endif
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="editUnitIdPemegangPersonal" class="form-label">Pemegang Personal
                                    (Guru)</label>
                                <select class="form-select" id="editUnitIdPemegangPersonal" name="id_pemegang_personal">
                                    <option value="">-- Tidak Ada Pemegang Personal --</option>
                                    {{-- $pemegangList (user guru) akan di-pass dari view induk --}}
                                    @if (isset($pemegangList) && $pemegangList->count() > 0)
                                        @foreach ($pemegangList as $pemegang)
                                            <option value="{{ $pemegang->id }}">{{ $pemegang->username }}
                                                ({{ $pemegang->email }})</option>
                                        @endforeach
                                    @else
                                        <option value="" disabled>Guru tidak tersedia</option>
                                    @endif
                                </select>
                                <small class="form-text text-muted">Kosongkan jika ditempatkan di ruangan.</small>
                            </div>

                            <div class="mb-3">
                                <label for="editUnitDeskripsi" class="form-label">Deskripsi Spesifik Unit</label>
                                <textarea class="form-control" id="editUnitDeskripsi" name="deskripsi_unit" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editUnitHargaPerolehan" class="form-label">Harga Perolehan Unit (Rp) <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="editUnitHargaPerolehan"
                                    name="harga_perolehan_unit" min="0" step="1" required>
                            </div>

                            <div class="mb-3">
                                <label for="editUnitTanggalPerolehan" class="form-label">Tanggal Perolehan Unit <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="editUnitTanggalPerolehan"
                                    name="tanggal_perolehan_unit" required>
                            </div>

                            <div class="mb-3">
                                <label for="editUnitSumberDana" class="form-label">Sumber Dana Unit</label>
                                <input type="text" class="form-control" id="editUnitSumberDana"
                                    name="sumber_dana_unit">
                            </div>

                            <div class="mb-3">
                                <label for="editUnitNoDokumenPerolehan" class="form-label">No. Dokumen Perolehan
                                    Unit</label>
                                <input type="text" class="form-control" id="editUnitNoDokumenPerolehan"
                                    name="no_dokumen_perolehan_unit">
                            </div>

                            <div class="mb-3">
                                <label for="editUnitKondisi" class="form-label">Kondisi Barang <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="editUnitKondisi" name="kondisi" required>
                                    {{-- $kondisiOptions akan di-pass dari view induk --}}
                                    @if (isset($kondisiOptions) && count($kondisiOptions) > 0)
                                        @foreach ($kondisiOptions as $kondisi)
                                            <option value="{{ $kondisi }}">{{ $kondisi }}</option>
                                        @endforeach
                                    @else
                                        <option value="Baik">Baik</option>
                                        <option value="Kurang Baik">Kurang Baik</option>
                                        <option value="Rusak Berat">Rusak Berat</option>
                                    @endif
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="editUnitStatus" class="form-label">Status Ketersediaan <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="editUnitStatus" name="status" required>
                                    {{-- $statusOptions akan di-pass dari view induk --}}
                                    @if (isset($statusOptions) && count($statusOptions) > 0)
                                        @foreach ($statusOptions as $status)
                                            <option value="{{ $status }}">{{ $status }}</option>
                                        @endforeach
                                    @else
                                        <option value="Tersedia">Tersedia</option>
                                        <option value="Dipinjam">Dipinjam</option>
                                        <option value="Dalam Pemeliharaan">Dalam Pemeliharaan</option>
                                        <option value="Diarsipkan/Dihapus">Diarsipkan/Dihapus</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i> Simpan Perubahan
                        Unit</button>
                </div>
            </div>
        </form>
    </div>
</div>
