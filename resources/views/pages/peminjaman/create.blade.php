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
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="id_barang_qr_code" class="form-label">Pilih Barang yang Akan Dipinjam <span
                                        class="text-danger">*</span></label>

                                <select class="form-control" id="id_barang_qr_code" name="id_barang_qr_code[]"
                                    multiple="multiple" required>
                                    {{-- JIKA ADA BARANG YANG DIPILIH DARI KATALOG, SISIPKAN SEBAGAI OPTION DI SINI --}}
                                    {{-- Ini akan dibaca oleh JavaScript untuk inisialisasi --}}
                                    @if (isset($selectedBarang) && $selectedBarang)
                                        @php
                                            $lokasi = optional($selectedBarang->ruangan)->nama_ruangan ?? 'N/A';
                                            $text = "{$selectedBarang->barang->nama_barang} ({$selectedBarang->kode_inventaris_sekolah}) - Lokasi: {$lokasi}";
                                        @endphp
                                        <option value="{{ $selectedBarang->id }}" selected>{{ $text }}</option>
                                    @endif
                                </select>

                                {{-- Info ruangan terkunci akan di-handle oleh JavaScript --}}
                                <div id="info-ruangan-terkunci" class="form-text text-primary mt-1" style="display: none;">
                                </div>

                                @error('id_barang_qr_code')
                                    <div class="text-danger mt-1 small">{{ $message }}</div>
                                @enderror
                            </div>
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
            let lockedRuanganNama = '';
            const selectBarang = $('#id_barang_qr_code');

            // Fungsi untuk mengunci atau membuka kunci ruangan
            function handleRoomLock() {
                const selectedItems = selectBarang.select2('data');

                if (selectedItems && selectedItems.length > 0) {
                    // Kunci ruangan jika belum terkunci
                    if (lockedRuanganId === null) {
                        // Ambil data dari item PERTAMA yang dipilih
                        const firstItemData = selectedItems[0];

                        // Cek apakah data custom (ruangan_id, ruangan_nama) ada. 
                        // Jika tidak, ambil dari option yang sudah ada (kasus dari katalog)
                        if (firstItemData.ruangan_id) {
                            lockedRuanganId = firstItemData.ruangan_id;
                            lockedRuanganNama = firstItemData.ruangan_nama;
                        } else {
                            // Fallback untuk item dari katalog yang option-nya sudah dirender di HTML
                            // Kita butuh query controller untuk memberikan data ini
                            @if (isset($selectedBarang) && $selectedBarang)
                                lockedRuanganId = {{ $selectedBarang->id_ruangan ?? 'null' }};
                                lockedRuanganNama = '{{ optional($selectedBarang->ruangan)->nama_ruangan ?? '' }}';
                            @endif
                        }

                        if (lockedRuanganId) {
                            $('#info-ruangan-terkunci').text(
                                `Dalam satu sesi peminjaman anda hanya bisa memilih barang dengan asal ruangan yang sama\n
                                Saat ini hanya menampilkan barang dari ruangan: ${lockedRuanganNama}`).slideDown();
                        }
                    }
                } else {
                    // Jika tidak ada item terpilih, buka kunci
                    lockedRuanganId = null;
                    lockedRuanganNama = '';
                    $('#info-ruangan-terkunci').slideUp();
                }
            }

            // Inisialisasi Select2
            selectBarang.select2({
                placeholder: "Ketik nama barang atau kode unit...",
                minimumInputLength: 2,
                ajax: {
                    url: "{{ route('guru.peminjaman.search-items') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        // Kirim ID ruangan yang terkunci sebagai parameter query
                        return {
                            q: params.term, // term pencarian
                            ruangan_id: lockedRuanganId // hanya cari di ruangan ini
                        };
                    },
                    processResults: function(data) {
                        // Map data untuk Select2, pastikan semua data custom ikut terbawa
                        const mappedResults = data.results.map(item => {
                            return {
                                id: item.id,
                                text: item.text,
                                ruangan_id: item.ruangan_id,
                                ruangan_nama: item.ruangan_nama
                            };
                        });
                        return {
                            results: mappedResults
                        };
                    },
                    cache: true
                }
            }).on('change', handleRoomLock); // Panggil fungsi setiap kali ada perubahan

            // ======================================================
            // BAGIAN BARU: Cek saat halaman pertama kali dimuat
            // ======================================================
            // Jika ada item yang sudah dipilih dari katalog, panggil handleRoomLock() secara manual
            if (selectBarang.val() && selectBarang.val().length > 0) {
                handleRoomLock();
            }
        });
    </script>
@endpush
