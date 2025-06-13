@extends('layouts.app')

@section('title', 'Buat Pengajuan Peminjaman Baru')

@push('styles')
    {{-- Diperlukan untuk Select2 --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--default .select2-selection--multiple {
            border-color: #ced4da;
        }

        .select2-container .select2-selection--multiple {
            min-height: calc(1.5em + .75rem + 2px);
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        {{-- Page Title & Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Pengajuan Peminjaman Baru</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('guru.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('guru.peminjaman.index') }}">Peminjaman</a></li>
                            <li class="breadcrumb-item active">Buat Pengajuan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Formulir Pengajuan Peminjaman</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('guru.peminjaman.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        {{-- Kolom Kiri --}}
                        {{-- GANTI ISI DARI <div class="col-md-6"> PERTAMA DENGAN INI --}}

                        <div class="col-md-6">
                            {{-- 1. Area untuk Menampilkan Barang yang Sudah Dipilih (Keranjang) --}}
                            <div class="mb-3">
                                <label class="form-label">Barang yang Akan Dipinjam:</label>
                                <div id="keranjang-peminjaman" class="border rounded p-3"
                                    style="min-height: 150px; background-color: #f8f9fa;">
                                    {{-- Pesan jika keranjang kosong --}}
                                    <div id="keranjang-kosong" class="text-center text-muted">
                                        <p class="mb-0">Keranjang masih kosong.</p>
                                        <small>Silakan cari dan tambah barang di bawah.</small>
                                    </div>
                                    {{-- Daftar barang akan ditambahkan di sini oleh JavaScript --}}
                                </div>
                                {{-- Input hidden untuk menampung semua ID barang yang dipilih --}}
                                <div id="hidden-inputs-container"></div>
                                @error('id_barang_qr_code')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- 2. Area untuk Mencari dan Menambah Barang --}}
                            <div class="mb-3">
                                <label for="search-barang-input" class="form-label">Cari & Tambah Barang:</label>
                                {{-- Ini adalah input Select2 untuk mencari barang --}}
                                <select class="form-control" id="search-barang-input" style="width: 100%;">
                                    {{-- Opsi akan diisi oleh AJAX --}}
                                </select>

                                {{-- Info ruangan terkunci akan muncul di sini --}}
                                <div id="info-ruangan-terkunci" class="alert alert-warning p-2 mt-2" style="display: none;">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Hanya menampilkan barang dari ruangan: <strong id="nama-ruangan-terkunci"></strong>
                                    <button type="button" id="reset-keranjang-btn"
                                        class="btn btn-sm btn-outline-danger float-end py-0">Reset</button>
                                </div>
                            </div>

                            {{-- Sisa form (Tujuan & Catatan) bisa tetap di sini atau dipindah ke kolom kanan --}}
                            <div class="mb-3">
                                <label for="tujuan_peminjaman" class="form-label">Tujuan Peminjaman <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control" id="tujuan_peminjaman" name="tujuan_peminjaman" rows="3" required>{{ old('tujuan_peminjaman') }}</textarea>
                                @error('tujuan_peminjaman')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="catatan_peminjam" class="form-label">Catatan Tambahan (Opsional)</label>
                                <textarea class="form-control" id="catatan_peminjam" name="catatan_peminjam" rows="2">{{ old('catatan_peminjam') }}</textarea>
                            </div>
                        </div>

                        {{-- Kolom Kanan --}}
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tanggal_rencana_pinjam" class="form-label">Rencana Tanggal Pinjam <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tanggal_rencana_pinjam"
                                        name="tanggal_rencana_pinjam" value="{{ old('tanggal_rencana_pinjam') }}" required>
                                    @error('tanggal_rencana_pinjam')
                                        <div class="text-danger mt-1 small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tanggal_harus_kembali" class="form-label">Tenggat Pengembalian <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tanggal_harus_kembali"
                                        name="tanggal_harus_kembali" value="{{ old('tanggal_harus_kembali') }}" required>
                                    @error('tanggal_harus_kembali')
                                        <div class="text-danger mt-1 small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="id_ruangan_tujuan_peminjaman" class="form-label">Ruangan Tujuan Penggunaan
                                    (Opsional)</label>
                                <select class="form-select" id="id_ruangan_tujuan_peminjaman"
                                    name="id_ruangan_tujuan_peminjaman">
                                    <option value="">-- Tidak ada ruangan spesifik --</option>
                                    @foreach ($ruanganTujuanList as $ruangan)
                                        <option value="{{ $ruangan->id }}"
                                            {{ old('id_ruangan_tujuan_peminjaman') == $ruangan->id ? 'selected' : '' }}>
                                            {{ $ruangan->nama_ruangan }} ({{ $ruangan->kode_ruangan }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <a href="{{ route('guru.peminjaman.index') }}" class="btn btn-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="mdi mdi-send-check-outline me-1"></i> Kirim Pengajuan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Diperlukan untuk Select2 dan JQuery --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            let lockedRuanganId = null;
            const keranjangDiv = $('#keranjang-peminjaman');
            const hiddenInputsContainer = $('#hidden-inputs-container');
            const searchInput = $('#search-barang-input');
            const keranjangKosongMsg = $('#keranjang-kosong');
            const infoRuanganDiv = $('#info-ruangan-terkunci');
            const namaRuanganSpan = $('#nama-ruangan-terkunci');
            const resetBtn = $('#reset-keranjang-btn');

            // Fungsi untuk menambahkan item ke keranjang visual dan input hidden
            function addItemToCart(item) {
                // Cek duplikat
                if ($(`#hidden-input-${item.id}`).length > 0) {
                    // Beri feedback bahwa item sudah ada
                    searchInput.select2('open'); // buka lagi dropdown
                    return;
                }

                keranjangKosongMsg.hide();

                // Tambahkan ke keranjang visual
                const itemHtml = `
                    <div class="d-flex justify-content-between align-items-center p-2 border-bottom" id="item-cart-${item.id}">
                        <span>${item.text}</span>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn py-0" data-id="${item.id}" title="Hapus">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                keranjangDiv.append(itemHtml);

                // Tambahkan ke input hidden untuk dikirim ke server
                const hiddenInputHtml =
                    `<input type="hidden" id="hidden-input-${item.id}" name="id_barang_qr_code[]" value="${item.id}">`;
                hiddenInputsContainer.append(hiddenInputHtml);

                // Kunci ruangan jika ini item pertama
                if (lockedRuanganId === null) {
                    lockedRuanganId = item.ruangan_id;
                    namaRuanganSpan.text(item.ruangan_nama);
                    infoRuanganDiv.show();
                }
            }

            // Fungsi untuk menghapus item dari keranjang
            function removeItemFromCart(itemId) {
                $(`#item-cart-${itemId}`).remove();
                $(`#hidden-input-${itemId}`).remove();

                // Jika keranjang jadi kosong, reset kunci ruangan
                if (hiddenInputsContainer.children().length === 0) {
                    resetCart();
                }
            }

            // Fungsi untuk mereset seluruh keranjang dan kunci ruangan
            function resetCart() {
                keranjangDiv.find('.border-bottom').remove();
                hiddenInputsContainer.empty();
                keranjangKosongMsg.show();
                lockedRuanganId = null;
                infoRuanganDiv.hide();
            }

            // Event listener untuk tombol hapus
            keranjangDiv.on('click', '.remove-item-btn', function() {
                const itemId = $(this).data('id');
                removeItemFromCart(itemId);
            });

            // Event listener untuk tombol reset
            resetBtn.on('click', resetCart);

            // Inisialisasi Select2 untuk pencarian
            searchInput.select2({
                placeholder: "Ketik untuk mencari barang...",
                minimumInputLength: 2,
                ajax: {
                    url: "{{ route('guru.peminjaman.search-items') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            ruangan_id: lockedRuanganId
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                }
            });

            // Event listener saat item dipilih dari Select2
            searchInput.on('select2:select', function(e) {
                var data = e.params.data;
                addItemToCart(data);
                // Reset input select2 agar bisa memilih lagi
                $(this).val(null).trigger('change');
            });

            // Logika untuk menangani item yang sudah dipilih dari katalog
            @if (isset($selectedBarang) && $selectedBarang)
                @php
                    $lokasi = optional($selectedBarang->ruangan)->nama_ruangan ?? 'N/A';
                    $text = "{$selectedBarang->barang->nama_barang} ({$selectedBarang->kode_inventaris_sekolah}) - Lokasi: {$lokasi}";
                @endphp

                addItemToCart({
                    id: '{{ $selectedBarang->id }}',
                    text: '{{ addslashes($text) }}',
                    ruangan_id: {{ $selectedBarang->id_ruangan ?? 'null' }},
                    ruangan_nama: '{{ addslashes(optional($selectedBarang->ruangan)->nama_ruangan) }}'
                });
            @endif
        });
    </script>
@endpush
