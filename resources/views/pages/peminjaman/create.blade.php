@extends('layouts.app')

@section('title', 'Buat Pengajuan Peminjaman Baru')

@push('styles')
    {{-- (Tidak ada perubahan di sini, biarkan seperti semula) --}}
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
        {{-- Page Title & Breadcrumb (Tidak ada perubahan) --}}
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

        {{-- =============================================== --}}
        {{-- ==== KODE UTAMA FORM DENGAN TATA LETAK BARU ==== --}}
        {{-- =============================================== --}}
        <form id="peminjaman-form" action="{{ route('guru.peminjaman.store') }}" method="POST">
            @csrf
            <div class="row">
                {{-- Kolom Kiri: Fokus pada Pemilihan Barang --}}
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">1. Pilih Barang</h5>
                            {{-- TOMBOL BARU: KEMBALI KE KATALOG --}}
                            <a href="{{ route('guru.katalog.index') }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-book-open me-1"></i> Pilih dari Katalog Barang
                            </a>
                        </div>
                        <div class="card-body">
                            {{-- Area untuk Mencari dan Menambah Barang --}}
                            <div class="mb-3">
                                <label for="search-barang-input" class="form-label">Cari & Tambah Barang</label>
                                <select class="form-control" id="search-barang-input" style="width: 100%;"></select>
                            </div>

                            {{-- Info Ruangan Terkunci --}}
                            <div id="info-ruangan-terkunci" class="alert alert-warning p-2" style="display: none;">
                                <i class="fas fa-info-circle me-1"></i>
                                Hanya akan menampilkan barang dari ruangan: <strong id="nama-ruangan-terkunci"></strong>

                                {{-- Ganti tombol "Reset Pilihan" lama Anda dengan blok form ini --}}
                                <button type="button" id="reset-keranjang-btn"
                                    class="btn btn-sm btn-outline-danger float-end py-0" data-bs-toggle="modal"
                                    data-bs-target="#universalConfirmModal"
                                    data-message="Anda yakin ingin mengosongkan seluruh keranjang dan mereset pilihan ruangan?"
                                    data-action-url="{{ route('guru.keranjang.reset') }}">
                                    Reset Pilihan
                                </button>
                            </div>
                            {{-- Area untuk Menampilkan Barang yang Sudah Dipilih (Keranjang) --}}
                            <div class="mt-3">
                                <p class="mb-2 fw-bold">Keranjang Peminjaman:</p>
                                <div id="keranjang-peminjaman" class="border rounded" style="min-height: 100px;">
                                    @if ($itemsDiKeranjang->isEmpty())
                                        <div id="keranjang-kosong" class="text-center text-muted p-4">
                                            <p class="mb-0">Keranjang masih kosong.</p>
                                        </div>
                                    @else
                                        @foreach ($itemsDiKeranjang as $item)
                                            <div class="d-flex justify-content-between align-items-center p-2 border-bottom"
                                                id="item-cart-{{ $item->id }}">
                                                <div>
                                                    {{-- PERBAIKAN FORMAT TEKS --}}
                                                    <span>{{ $item->barang->nama_barang }}
                                                        ({{ $item->kode_inventaris_sekolah }})
                                                        - Lokasi:
                                                        {{ optional($item->ruangan)->nama_ruangan ?? 'N/A' }}</span>

                                                    @if (!$item->is_available_for_loan)
                                                        <br>
                                                        <small class="fw-bold text-danger">
                                                            <i class="fas fa-exclamation-triangle"></i> Item ini sudah tidak
                                                            tersedia.
                                                        </small>
                                                    @endif
                                                </div>
                                                {{-- PERBAIKAN TOMBOL HAPUS --}}
                                                <td class="text-end">
                                                    {{-- 1. Bungkus tombol dengan <form> yang punya ID unik --}}
                                                    <button type="button" class="btn btn-sm btn-outline-danger py-0"
                                                        data-bs-toggle="modal" data-bs-target="#universalConfirmModal"
                                                        data-message="Anda yakin ingin menghapus '{{ $item->barang->nama_barang }}' dari keranjang?"
                                                        data-action-url="{{ route('guru.keranjang.hapus', $item->id) }}"
                                                        title="Hapus">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </td>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                                <div id="hidden-inputs-container">
                                    {{-- Kirim semua ID ke server, validasi akhir ada di controller --}}
                                    @foreach ($itemsDiKeranjang as $item)
                                        <input type="hidden" name="id_barang_qr_code[]" value="{{ $item->id }}">
                                    @endforeach
                                </div>
                                @error('id_barang_qr_code')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kolom Kanan: Fokus pada Detail Peminjaman --}}
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">2. Lengkapi Detail Pengajuan</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="tujuan_peminjaman" class="form-label">Tujuan Peminjaman <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control" id="tujuan_peminjaman" name="tujuan_peminjaman" rows="3" required>{{ old('tujuan_peminjaman') }}</textarea>
                                @error('tujuan_peminjaman')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tanggal_rencana_pinjam" class="form-label">Tgl. Pinjam <span
                                            class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tanggal_rencana_pinjam"
                                        name="tanggal_rencana_pinjam" value="{{ old('tanggal_rencana_pinjam') }}" required>
                                    @error('tanggal_rencana_pinjam')
                                        <div class="text-danger mt-1 small">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tanggal_harus_kembali" class="form-label">Tgl. Kembali <span
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
                                            {{ $ruangan->nama_ruangan }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="catatan_peminjam" class="form-label">Catatan Tambahan (Opsional)</label>
                                <textarea class="form-control" id="catatan_peminjam" name="catatan_peminjam" rows="2">{{ old('catatan_peminjam') }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol Aksi Final --}}
                    <div class="text-end">
                        <a href="{{ route('guru.peminjaman.index') }}" class="btn btn-light me-2">Batal</a>

                        {{-- Logika baru untuk menonaktifkan tombol submit --}}
                        @php
                            $hasUnavailableItems = $itemsDiKeranjang->contains('is_available_for_loan', false);
                        @endphp
                        <button type="submit" class="btn btn-primary" id="submit-btn"
                            @if (!$semuaSatuRuangan || $itemsDiKeranjang->isEmpty() || $hasUnavailableItems) disabled @endif>
                            <i class="mdi mdi-send-check-outline me-1"></i> Kirim Pengajuan
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    {{-- (Link ke JQuery & Select2 tetap sama) --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // --- VARIABEL DAN ELEMEN DOM ---
            let lockedRuanganId = null;
            let lockedRuanganNama = '';
            const searchInput = $('#search-barang-input');

            // Fungsi untuk mengupdate badge di keranjang mengambang
            function updateFloatingCartBadge(count) {
                const badge = $('.position-fixed .btn .badge');
                if (count > 0) {
                    badge.text(count).show();
                } else {
                    badge.hide();
                }
            }

            // Fungsi untuk me-reload halaman dengan pesan (untuk menjaga konsistensi)
            function reloadPage(type, message) {
                const url = new URL(window.location.href);
                url.searchParams.set(type, message);
                window.location.href = url.toString();
            }

            // --- INISIALISASI AWAL ---
            function initializeCart() {
                // Cek apakah ada barang dari server side rendering
                @if ($itemsDiKeranjang->isNotEmpty())
                    const firstItem = @json($itemsDiKeranjang->first());
                    lockedRuanganId = firstItem.id_ruangan;
                    lockedRuanganNama = '{{ optional($itemsDiKeranjang->first()->ruangan)->nama_ruangan }}';

                    $('#info-ruangan-terkunci').show();
                    $('#nama-ruangan-terkunci').text(lockedRuanganNama);
                @endif
            }

            // --- EVENT LISTENERS ---



            // 3. TAMBAH ITEM DARI SELECT2 (SEKARANG LEWAT AJAX)
            searchInput.on('select2:select', function(e) {
                const data = e.params.data;
                const item = {
                    id: data.id,
                    _token: '{{ csrf_token() }}',
                    id_barang_qr_code: data.id,
                };

                $.ajax({
                    url: '{{ route('guru.keranjang.tambah') }}',
                    type: 'POST',
                    data: item,
                    success: function(response) {
                        reloadPage('success', response.message ||
                            'Barang berhasil ditambahkan.');
                    },
                    error: function(xhr) {
                        const error = xhr.responseJSON ? xhr.responseJSON.message :
                            'Gagal menambahkan barang.';
                        alert(error);
                    }
                });

                $(this).val(null).trigger('change');
            });

            // Inisialisasi Select2 (logika pencarian tidak berubah)
            searchInput.select2({
                placeholder: "Ketik untuk mencari barang...",
                minimumInputLength: 2,
                ajax: {
                    url: "{{ route('guru.peminjaman.searchAvailableItems') }}",
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

            // Panggil fungsi inisialisasi saat halaman dimuat
            initializeCart();
        });
    </script>
@endpush
