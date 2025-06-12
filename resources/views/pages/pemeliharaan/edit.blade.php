@extends('layouts.app')

@section('title', 'Proses Laporan Pemeliharaan #' . $pemeliharaan->id)

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush
@php
    // Definisikan status-status final di sini
    $finalStatuses = ['Selesai', 'Gagal', 'Tidak Dapat Diperbaiki', 'Ditolak', 'Dibatalkan'];
    $isLocked =
        in_array($pemeliharaan->status_pengerjaan, $finalStatuses) ||
        in_array($pemeliharaan->status_pengajuan, $finalStatuses);
@endphp
@section('content')
    <div class="container-fluid">
        {{-- Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Proses Pemeliharaan</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route($rolePrefix . 'dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route($rolePrefix . 'pemeliharaan.index') }}">Pemeliharaan</a></li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route($rolePrefix . 'pemeliharaan.show', $pemeliharaan->id) }}">Detail
                                    #{{ $pemeliharaan->id }}</a></li>
                            <li class="breadcrumb-item active">Proses</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route($rolePrefix . 'pemeliharaan.update', $pemeliharaan->id) }}" method="POST"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row">
                {{-- Kolom Kiri: Form Aksi --}}
                <div class="col-xl-8 col-lg-7">
                    {{-- Tampilkan pesan peringatan jika laporan terkunci --}}
                    @if ($isLocked)
                        <div class="alert alert-warning" role="alert">
                            <i class="fas fa-lock me-2"></i>
                            <strong>Laporan Terkunci.</strong> Laporan ini sudah berstatus final dan tidak dapat diedit
                            lagi. Form ditampilkan dalam mode 'read-only'.
                        </div>
                    @endif
                    {{-- Tampilkan error validasi jika ada --}}
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <strong>Terjadi Kesalahan:</strong>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Form untuk Admin --}}
                    @if (Auth::user()->hasRole(\App\Models\User::ROLE_ADMIN))
                        @include('pages.pemeliharaan.partials._edit_form_admin')
                        {{-- Form untuk Operator PIC --}}
                    @elseif (Auth::user()->hasRole(\App\Models\User::ROLE_OPERATOR) &&
                            Auth::id() === $pemeliharaan->id_operator_pengerjaan &&
                            $pemeliharaan->status_pengajuan === 'Disetujui')
                        @include('pages.pemeliharaan.partials._edit_form_operator')
                        {{-- Form untuk Pengaju (Guru/Operator) jika status masih Diajukan --}}
                    @elseif (Auth::id() === $pemeliharaan->id_user_pengaju && $pemeliharaan->status_pengajuan === 'Diajukan')
                        @include('pages.pemeliharaan.partials._edit_form_pengaju')
                    @else
                        <div class="alert alert-warning">Tidak ada aksi yang dapat Anda lakukan pada tahap ini.</div>
                    @endif
                </div>

                {{-- Kolom Kanan: Info --}}
                <div class="col-xl-4 col-lg-5">
                    <div class="card sticky-top" style="top: 80px;">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Ringkasan Laporan</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><strong>ID Laporan:</strong> #{{ $pemeliharaan->id }}</p>
                            <p class="mb-2"><strong>Kode Unit:</strong>
                                <code>{{ $barangQrCode->kode_inventaris_sekolah }}</code>
                            </p>
                            <p class="mb-2"><strong>Nama Barang:</strong> {{ $barangQrCode->barang->nama_barang }}</p>
                            <p class="mb-2"><strong>Pelapor:</strong> {{ $pemeliharaan->pengaju->username }}</p>
                            <p class="mb-2"><strong>PIC Pengerjaan:</strong>
                                {{ optional($pemeliharaan->operatorPengerjaan)->username ?? 'Belum Ditentukan' }}</p>
                            <hr>
                            <p class="mb-2"><strong>Status Pengajuan:</strong> <span
                                    class="badge {{ \App\Models\Pemeliharaan::statusColor($pemeliharaan->status_pengajuan) }}">{{ $pemeliharaan->status_pengajuan }}</span>
                            </p>
                            <p class="mb-0"><strong>Status Pengerjaan:</strong> <span
                                    class="badge {{ \App\Models\Pemeliharaan::statusColor($pemeliharaan->status_pengerjaan) }}">{{ $pemeliharaan->status_pengerjaan }}</span>
                            </p>
                            <hr>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary"
                                    @if ($isLocked) disabled @endif>
                                    <i class="fas fa-save me-2"></i>Simpan Perubahan
                                </button>
                                <a href="{{ route($rolePrefix . 'pemeliharaan.show', $pemeliharaan->id) }}"
                                    class="btn btn-outline-secondary">Kembali ke Detail</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inisialisasi semua select2 standar di halaman ini
            $('.select2-basic').select2({
                theme: "bootstrap-5",
                width: '100%',
            });

            // Logika untuk menampilkan/menyembunyikan form hasil akhir
            function toggleHasilPengerjaan() {
                const statusPengerjaan = $('#status_pengerjaan').val();
                const formHasil = $('#form-hasil-pengerjaan');
                const hasilRequiredFields = $('#hasil_pemeliharaan, #kondisi_barang_setelah_pemeliharaan');

                const showHasil = [
                    'Selesai',
                    'Tidak Dapat Diperbaiki',
                    'Gagal'
                ].includes(statusPengerjaan);

                if (showHasil) {
                    formHasil.slideDown();
                    hasilRequiredFields.prop('required', true);
                } else {
                    formHasil.slideUp();
                    hasilRequiredFields.prop('required', false);
                }
            }

            toggleHasilPengerjaan();
            $('#status_pengerjaan').on('change', toggleHasilPengerjaan);
        });
    </script>
@endpush
