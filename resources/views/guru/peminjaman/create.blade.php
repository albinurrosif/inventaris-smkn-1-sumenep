@extends('layouts.app')

@section('title', 'Ajukan Peminjaman Barang')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Ajukan Peminjaman Barang</h4>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Tambah Barang ke Peminjaman</h5>
                <form id="form-tambah-barang">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="select-ruangan-asal" class="form-label">Ruangan Asal</label>
                            <select class="form-select" id="select-ruangan-asal" required>
                                <option value="" disabled selected>Pilih Ruangan Asal</option>
                                @foreach ($ruangan as $r)
                                    <option value="{{ $r->id }}">{{ $r->nama_ruangan }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="select-barang" class="form-label">Nama Barang</label>
                            <select class="form-select" id="select-barang" disabled required>
                                <option value="" disabled selected>Pilih Barang (setelah ruangan asal)</option>
                            </select>
                            <small class="text-muted">Stok tersedia: <span id="stok-tersedia">0</span></small>
                        </div>
                        <div class="col-md-4">
                            <label for="select-ruangan-tujuan" class="form-label">Ruangan Tujuan</label>
                            <select class="form-select" id="select-ruangan-tujuan" required>
                                <option value="" disabled selected>Pilih Ruangan Tujuan</option>
                                @foreach ($ruangan as $r)
                                    <option value="{{ $r->id }}">{{ $r->nama_ruangan }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="input-jumlah" class="form-label">Jumlah</label>
                            <input type="number" class="form-control" id="input-jumlah" min="1" value="1"
                                required>
                        </div>
                        <div class="col-md-3">
                            <label for="input-tanggal-pinjam" class="form-label">Tanggal Pinjam</label>
                            <input type="date" class="form-control" id="input-tanggal-pinjam" required>
                        </div>
                        <div class="col-md-3">
                            <label for="input-durasi-pinjam" class="form-label">Durasi Pinjam (hari)</label>
                            <input type="number" class="form-control" id="input-durasi-pinjam" min="1"
                                value="1" required>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary" id="btn-tambah-barang">
                                <i class="mdi mdi-plus-circle-outline me-1"></i> Tambah Barang
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <h5 class="mb-3">Keranjang Peminjaman</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="tabel-keranjang">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nama Barang</th>
                                <th>Ruangan Asal</th>
                                <th>Ruangan Tujuan</th>
                                <th>Jumlah</th>
                                <th>Tanggal Pinjam</th>
                                <th>Tanggal Kembali</th>
                                <th>Durasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="8" class="text-end"><strong>Total Item:</strong> <span
                                        id="total-items">0</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <form action="{{ route('guru.peminjaman.store') }}" method="POST">
                    @csrf
                    <div id="form-inputs-container">
                        <input type="hidden" name="items" id="input-items-json">
                    </div>
                    <div class="card mt-4 mb-4">
                        <div class="card-body">

                            <div class="mb-3">
                                <label for="input-keterangan" class="form-label">Keterangan Peminjaman (Opsional)</label>
                                <textarea class="form-control" id="input-keterangan" name="keterangan" rows="3"
                                    placeholder="Tambahkan keterangan atau tujuan peminjaman di sini..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-success" id="btn-submit-peminjaman" disabled>
                            <i class="mdi mdi-check-circle-outline me-1"></i> Ajukan Semua Peminjaman
                        </button>
                        <a href="{{ route('guru.peminjaman.index') }}" class="btn btn-secondary ms-2">
                            <i class="mdi mdi-arrow-left-circle-outline me-1"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 2000
            });
        </script>
    @endif
    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: '{{ session('error') }}',
                showConfirmButton: false,
                timer: 2000
            });
        </script>
    @endif

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let keranjangBarang = [];
            const tabelKeranjang = document.getElementById('tabel-keranjang');
            const totalItemsElement = document.getElementById('total-items');
            const btnSubmit = document.getElementById('btn-submit-peminjaman');

            const formBarang = document.getElementById('form-tambah-barang');
            const selectRuanganAsal = document.getElementById('select-ruangan-asal');
            const selectBarang = document.getElementById('select-barang');
            const selectRuanganTujuan = document.getElementById('select-ruangan-tujuan');
            const inputJumlah = document.getElementById('input-jumlah');
            const inputTanggalPinjam = document.getElementById('input-tanggal-pinjam');
            const inputDurasiPinjam = document.getElementById('input-durasi-pinjam');
            const inputKeterangan = document.getElementById('input-keterangan');
            const btnTambahBarang = document.getElementById('btn-tambah-barang');
            const inputItemsJson = document.getElementById('input-items-json');
            const formInputsContainer = document.getElementById('form-inputs-container');

            const allBarang = @json($barang->toArray());

            function loadBarangByRuangan(ruanganId) {
                selectBarang.innerHTML = '<option value="" disabled selected>Pilih Barang</option>';
                selectBarang.disabled = true;
                document.getElementById('stok-tersedia').textContent = '0';

                if (ruanganId) {
                    const filteredBarang = allBarang.filter(barang => barang.id_ruangan == ruanganId);
                    if (filteredBarang.length > 0) {
                        filteredBarang.forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.id;
                            option.dataset.stok = item.jumlah_barang;
                            option.dataset.ruanganAsal = item.ruangan_id;
                            option.textContent = item.nama_barang;
                            selectBarang.appendChild(option);
                        });
                        selectBarang.disabled = false;
                    } else {
                        selectBarang.innerHTML =
                            '<option value="" disabled selected>Tidak ada barang di ruangan ini</option>';
                    }
                }
            }

            // Perbaikan fungsi tambahBarang di JavaScript
            function tambahBarang(e) {
                e.preventDefault();

                if (!selectBarang.value || !selectRuanganAsal.value || !selectRuanganTujuan.value ||
                    !inputJumlah.value || !inputTanggalPinjam.value || !inputDurasiPinjam.value) {
                    showAlert('Silakan lengkapi semua field', 'danger');
                    return;
                }

                if (selectRuanganAsal.value === selectRuanganTujuan.value) {
                    showAlert('Ruangan asal dan ruangan tujuan tidak boleh sama', 'danger');
                    return;
                }

                if (parseInt(inputJumlah.value) <= 0) {
                    showAlert('Jumlah harus lebih dari 0', 'danger');
                    return;
                }

                const barangOption = selectBarang.options[selectBarang.selectedIndex];
                const ruanganAsalOption = selectRuanganAsal.options[selectRuanganAsal.selectedIndex];
                const ruanganTujuanOption = selectRuanganTujuan.options[selectRuanganTujuan.selectedIndex];

                const stokTersedia = parseInt(barangOption.dataset.stok);
                if (parseInt(inputJumlah.value) > stokTersedia) {
                    showAlert(`Stok ${barangOption.text} tidak mencukupi. Tersedia: ${stokTersedia}`, 'danger');
                    return;
                }

                // Hitung tanggal kembali berdasarkan tanggal pinjam dan durasi
                const tanggalPinjam = new Date(inputTanggalPinjam.value);
                const tanggalKembali = new Date(tanggalPinjam);
                tanggalKembali.setDate(tanggalKembali.getDate() + parseInt(inputDurasiPinjam.value));

                // Format tanggal kembali sebagai string YYYY-MM-DD
                const tanggalKembaliFormatted = tanggalKembali.toISOString().split('T')[0];

                const barang = {
                    barang_id: selectBarang.value, // Gunakan barang_id bukan id
                    nama: barangOption.text,
                    ruangan_asal: selectRuanganAsal.value,
                    nama_ruangan_asal: ruanganAsalOption.text,
                    ruangan_tujuan: selectRuanganTujuan.value,
                    nama_ruangan_tujuan: ruanganTujuanOption.text,
                    jumlah: parseInt(inputJumlah.value),
                    tanggal_pinjam: inputTanggalPinjam.value,
                    tanggal_kembali: tanggalKembaliFormatted, // Tambahkan tanggal kembali yang sudah dihitung
                    durasi_pinjam: parseInt(inputDurasiPinjam.value)
                };

                keranjangBarang.push(barang);
                updateKeranjang();
                formBarang.reset();
                selectBarang.innerHTML =
                    '<option value="" disabled selected>Pilih Barang (setelah ruangan asal)</option>';
                selectBarang.disabled = true;
                document.getElementById('stok-tersedia').textContent = '0';
                showAlert('Barang berhasil ditambahkan ke keranjang', 'success');
            }

            // Perbaikan fungsi hapusBarang - pindahkan ke global scope
            window.hapusBarang = function(index) {
                keranjangBarang.splice(index, 1);
                updateKeranjang();
                showAlert('Barang dihapus dari keranjang', 'info');
            }

            function updateKeranjang() {
                totalItemsElement.textContent = keranjangBarang.length;
                btnSubmit.disabled = keranjangBarang.length === 0;
                const tbody = tabelKeranjang.querySelector('tbody');
                tbody.innerHTML = '';

                keranjangBarang.forEach((barang, index) => {
                    const tr = document.createElement('tr');
                    const tanggalPinjam = new Date(barang.tanggal_pinjam);
                    const tanggalKembali = new Date(tanggalPinjam);
                    tanggalKembali.setDate(tanggalKembali.getDate() + barang.durasi_pinjam);

                    tr.innerHTML = `
                <td>${index + 1}</td>
                <td>${barang.nama}</td>
                <td>${barang.nama_ruangan_asal}</td>
                <td>${barang.nama_ruangan_tujuan}</td>
                <td>${barang.jumlah}</td>
                <td>${formatDate(tanggalPinjam)}</td>
                <td>${formatDate(tanggalKembali)}</td>
                <td>${barang.durasi_pinjam} hari</td>
                <td>
    <button type="button" class="btn btn-danger btn-sm" onclick="hapusBarang(${index})">
        <i class="mdi mdi-delete"></i>
    </button>
</td>
`;

                    tbody.appendChild(tr);
                });

                inputItemsJson.value = JSON.stringify(keranjangBarang);
            }

            function hapusBarang(index) {
                keranjangBarang.splice(index, 1);
                updateKeranjang();
                showAlert('Barang dihapus dari keranjang', 'info');
            }

            function formatDate(date) {
                const d = new Date(date);
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = d.getFullYear();
                return `${day}-${month}-${year}`;
            }

            function showAlert(message, type) {
                Swal.fire({
                    icon: type,
                    title: type === 'success' ? 'Sukses' : type === 'danger' ? 'Gagal' : type === 'info' ?
                        'Info' : 'Peringatan',
                    text: message,
                    timer: 3000,
                    position: 'top', // Ubah posisi agar lebih terlihat sebagai notifikasi
                    toast: true
                });

            }

            function updateFormInputs() {
                // Create hidden input for keterangan if it doesn't exist
                let keteranganInput = document.querySelector('input[name="keterangan"]');
                if (!keteranganInput) {
                    keteranganInput = document.createElement('input');
                    keteranganInput.type = 'hidden';
                    keteranganInput.name = 'keterangan';
                    formInputsContainer.appendChild(keteranganInput);
                }
                keteranganInput.value = inputKeterangan.value;
            }

            // Event listeners
            selectRuanganAsal.addEventListener('change', function() {
                loadBarangByRuangan(this.value);
            });

            selectBarang.addEventListener('change', function() {
                const selected = this.options[this.selectedIndex];
                const stok = selected.dataset.stok || 0;
                document.getElementById('stok-tersedia').textContent = stok;
            });

            formBarang.addEventListener('submit', tambahBarang);
            btnTambahBarang.addEventListener('click', tambahBarang);

            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            inputTanggalPinjam.min = `${year}-${month}-${day}`;
            inputTanggalPinjam.value = `${year}-${month}-${day}`;

            btnSubmit.addEventListener('click', function(e) {
                inputItemsJson.value = JSON.stringify(keranjangBarang);
                updateFormInputs(); // pastikan hidden input diperbarui sebelum submit
            });

            updateKeranjang(); // inisialisasi
        });
    </script>
@endpush
