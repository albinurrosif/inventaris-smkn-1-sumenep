<!-- Modal Tambah Barang -->
<div class="modal fade" id="modalTambahBarang" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">

    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('barang.store') }}" method="POST" id="formTambahBarang">
                    @csrf

                    <div class="row">
                        {{-- Nama & Kode --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Barang</label>
                            <input type="text" name="nama_barang" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kode Barang</label>
                            <input type="text" name="kode_barang" class="form-control" required>
                        </div>

                        {{-- Merk & No Seri --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Merk / Model</label>
                            <input type="text" name="merk_model" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">No Seri Pabrik</label>
                            <input type="text" name="no_seri_pabrik" class="form-control">
                        </div>

                        {{-- Ukuran & Bahan --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ukuran</label>
                            <input type="text" name="ukuran" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bahan</label>
                            <input type="text" name="bahan" class="form-control">
                        </div>

                        {{-- Tahun & Jumlah --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tahun Pembuatan / Pembelian</label>
                            <input type="number" name="tahun_pembuatan_pembelian" class="form-control" min="1900"
                                max="{{ date('Y') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jumlah Barang</label>
                            <input type="number" name="jumlah_barang" class="form-control" value="1" required>
                        </div>

                        {{-- Harga & Sumber --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Harga Beli</label>
                            <input type="number" step="0.01" name="harga_beli" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sumber</label>
                            <input type="text" name="sumber" class="form-control">
                        </div>

                        {{-- Keadaan --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Keadaan Barang</label>
                            <select name="keadaan_barang" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                <option value="Baik">Baik</option>
                                <option value="Kurang Baik">Kurang Baik</option>
                                <option value="Rusak Berat">Rusak Berat</option>
                            </select>
                        </div>

                        {{-- Keterangan Mutasi --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Keterangan Mutasi</label>
                            <textarea name="keterangan_mutasi" class="form-control" rows="2"></textarea>
                        </div>

                        {{-- Ruangan --}}
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Ruangan</label>
                            <select name="id_ruangan" class="form-select" required>
                                <option value="">-- Pilih Ruangan --</option>
                                @php
                                    $ruangan = $ruangan ?? []; // Inisialisasi $ruangan sebagai array kosong jika belum ada
                                @endphp
                                @if (is_iterable($ruangan) && count($ruangan) > 0)
                                    @foreach ($ruangan as $r)
                                        <option value="{{ $r->id }}">{{ $r->nama_ruangan }}</option>
                                    @endforeach
                                @else
                                    <option value="">Data ruangan tidak tersedia</option>
                                    <script>
                                        console.error('Variabel $ruangan tidak valid atau kosong:', @json($ruangan));
                                    </script>
                                @endif
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="formTambahBarang" class="btn btn-primary">Simpan</button>
            </div>
        </div>
    </div>
</div>
<script>
    console.log('Data ruangan:', @json($ruangan));
</script>
