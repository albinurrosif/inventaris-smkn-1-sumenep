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
                                    multiple="multiple">
                                    {{-- Jika ada barang yang dipilih dari katalog, tampilkan di sini --}}
                                    @if ($selectedBarang)
                                        @php
                                            $lokasi = optional($selectedBarang->ruangan)->nama_ruangan ?? 'N/A';
                                            $text = "{$selectedBarang->barang->nama_barang} ({$selectedBarang->kode_inventaris_sekolah}) - Lokasi: {$lokasi}";
                                        @endphp
                                        <option value="{{ $selectedBarang->id }}" selected>{{ $text }}</option>
                                    @endif
                                </select>

                                <div id="info-ruangan-terkunci" class="form-text text-primary" style="display: none;"></div>
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
                        const firstItem = selectedItems[0];

                        // Cek properti ruangan_id langsung dari objek data Select2
                        if (firstItem.ruangan_id) {
                            lockedRuanganId = firstItem.ruangan_id;
                            lockedRuanganNama = firstItem.ruangan_nama;
                            $('#info-ruangan-terkunci').text(`Hanya menampilkan barang dari: ${lockedRuanganNama}`)
                                .slideDown();
                        } else {
                            // Ini seharusnya tidak terjadi jika query controller sudah benar
                            selectBarang.val(null).trigger('change');
                            alert('Error: Data ruangan untuk item terpilih tidak ditemukan. Silakan coba lagi.');
                        }
                    }
                } else {
                    // Jika tidak ada item terpilih, buka kunci
                    lockedRuanganId = null;
                    lockedRuanganNama = '';
                    $('#info-ruangan-terkunci').hide();
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
                        return {
                            q: params.term,
                            ruangan_id: lockedRuanganId
                        };
                    },
                    // PENYESUAIAN KUNCI: Pastikan semua data (termasuk ruangan_id) diteruskan
                    processResults: function(data) {
                        return {
                            results: data.results // Cukup teruskan semua data dari server
                        };
                    },
                    cache: true
                }
            }).on('change', handleRoomLock); // Gunakan event 'change' yang lebih umum

            // Logika untuk menangani item yang sudah dipilih dari katalog
            @if ($selectedBarang)
                // Buat <option> baru secara manual dengan data lengkap
                var preselectedOption = new Option(
                    "{{ $selectedBarang->barang->nama_barang }} ({{ $selectedBarang->kode_inventaris_sekolah }}) - Lokasi: {{ optional($selectedBarang->ruangan)->nama_ruangan }}",
                    '{{ $selectedBarang->id }}', true, true
                );

                // Tambahkan option ke select2
                selectBarang.append(preselectedOption).trigger('change');

                // Panggil handleRoomLock secara manual untuk mengunci ruangan
                handleRoomLock();
            @endif
        });
    </script>
@endpush
