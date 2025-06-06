{{-- File: resources/views/guru/peminjaman/edit.blade.php --}}
@extends('layouts.app') {{-- Sesuaikan dengan layout guru Anda --}}

@section('title', 'Edit Pengajuan Peminjaman Aset')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            background-color: #0d6efd;
            border-color: #0a58ca;
            color: white;
            padding-right: 25px !important;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
            color: rgba(255, 255, 255, 0.7);
            margin-right: 5px;
            margin-left: 5px;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Edit Pengajuan: PMJ-{{ str_pad($peminjaman->id, 5, '0', STR_PAD_LEFT) }}</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('guru.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('guru.peminjaman.index') }}">Peminjaman
                                    Saya</a></li>
                            <li class="breadcrumb-item active">Edit Pengajuan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Form Edit Pengajuan</h5>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('guru.peminjaman.update', $peminjaman->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="tujuan_peminjaman_edit" class="form-label">Tujuan Peminjaman <span
                                                class="text-danger">*</span></label>
                                        <textarea class="form-control" id="tujuan_peminjaman_edit" name="tujuan_peminjaman" rows="3" required>{{ old('tujuan_peminjaman', $peminjaman->tujuan_peminjaman) }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="id_barang_qr_code_edit" class="form-label">Pilih Item Barang (Bisa lebih dari
                                    satu) <span class="text-danger">*</span></label>
                                <select class="form-select select2-barang-edit" id="id_barang_qr_code_edit"
                                    name="id_barang_qr_code[]" multiple="multiple" required>
                                    @foreach ($barangList as $item)
                                        <option value="{{ $item['id'] }}"
                                            {{ is_array(old('id_barang_qr_code', $selectedBarangIds)) && in_array($item['id'], old('id_barang_qr_code', $selectedBarangIds)) ? 'selected' : '' }}>
                                            {{ $item['text'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Hanya menampilkan barang yang tersedia dan dalam
                                    kondisi baik/kurang baik, atau yang sudah dipilih sebelumnya.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="tanggal_rencana_pinjam_edit" class="form-label">Tanggal Rencana Pinjam
                                            <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="tanggal_rencana_pinjam_edit"
                                            name="tanggal_rencana_pinjam"
                                            value="{{ old('tanggal_rencana_pinjam', $peminjaman->tanggal_rencana_pinjam->format('Y-m-d')) }}"
                                            required
                                            min="{{ \Carbon\Carbon::parse($peminjaman->tanggal_rencana_pinjam)->isPast() && !$errors->has('tanggal_rencana_pinjam') ? $peminjaman->tanggal_rencana_pinjam->format('Y-m-d') : date('Y-m-d') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="tanggal_harus_kembali_edit" class="form-label">Tanggal Harus Kembali
                                            <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="tanggal_harus_kembali_edit"
                                            name="tanggal_harus_kembali"
                                            value="{{ old('tanggal_harus_kembali', $peminjaman->tanggal_harus_kembali->format('Y-m-d')) }}"
                                            required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="id_ruangan_tujuan_peminjaman_edit" class="form-label">Ruangan Tujuan
                                    Penggunaan (Opsional)</label>
                                <select class="form-select select2-ruangan-edit" id="id_ruangan_tujuan_peminjaman_edit"
                                    name="id_ruangan_tujuan_peminjaman">
                                    <option value="">Pilih ruangan jika diperlukan</option>
                                    @foreach ($ruanganTujuanList as $ruangan)
                                        <option value="{{ $ruangan->id }}"
                                            {{ old('id_ruangan_tujuan_peminjaman', $peminjaman->id_ruangan_tujuan_peminjaman) == $ruangan->id ? 'selected' : '' }}>
                                            {{ $ruangan->nama_ruangan }} ({{ $ruangan->kode_ruangan }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="catatan_peminjam_edit" class="form-label">Catatan Tambahan (Opsional)</label>
                                <textarea class="form-control" id="catatan_peminjam_edit" name="catatan_peminjam" rows="3">{{ old('catatan_peminjam', $peminjaman->catatan_peminjam) }}</textarea>
                            </div>

                            <div class="d-flex justify-content-end">
                                <a href="{{ route('guru.peminjaman.show', $peminjaman->id) }}"
                                    class="btn btn-light me-2">Batal</a>
                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2-barang-edit').select2({
                placeholder: "Cari dan pilih item barang...",
                allowClear: true,
                theme: "bootstrap-5"
            });
            $('.select2-ruangan-edit').select2({
                placeholder: "Pilih ruangan tujuan...",
                allowClear: true,
                theme: "bootstrap-5",
                width: '100%'
            });

            function setMinTanggalKembali() {
                var tglPinjam = $('#tanggal_rencana_pinjam_edit').val();
                if (tglPinjam) {
                    $('#tanggal_harus_kembali_edit').attr('min', tglPinjam);
                    // Jika tanggal kembali yang sudah ada lebih kecil, kosongkan
                    if ($('#tanggal_harus_kembali_edit').val() && $('#tanggal_harus_kembali_edit').val() <
                        tglPinjam) {
                        // Tidak mengosongkan jika tgl kembali valid, hanya set min
                    }
                }
            }

            $('#tanggal_rencana_pinjam_edit').on('change', function() {
                setMinTanggalKembali();
                if ($('#tanggal_harus_kembali_edit').val() && $('#tanggal_harus_kembali_edit').val() <= $(
                        this).val()) {
                    $('#tanggal_harus_kembali_edit').val(''); // Kosongkan jika tidak valid
                }
            });

            // Inisialisasi min untuk tanggal_harus_kembali saat load halaman
            setMinTanggalKembali();
        });
    </script>
@endpush
