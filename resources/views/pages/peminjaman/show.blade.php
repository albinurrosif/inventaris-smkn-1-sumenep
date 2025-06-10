@extends('layouts.app')

@section('title', 'Detail Peminjaman #' . $peminjaman->id)

@push('styles')
    {{-- Tambahkan style jika diperlukan --}}
@endpush

@php
    $rolePrefix = Auth::user()->getRolePrefix();
@endphp

@section('content')
    <div class="container-fluid">
        {{-- Page Title & Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Detail Peminjaman #{{ $peminjaman->id }}</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route($rolePrefix . 'peminjaman.index') }}">Peminjaman</a></li>
                            <li class="breadcrumb-item active">Detail</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tombol Aksi Utama --}}
        <div class="row">
            <div class="col-12">
                <div class="d-flex align-items-center mb-3">
                    <a href="{{ route($rolePrefix . 'peminjaman.index') }}" class="btn btn-secondary btn-sm me-auto">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>

                    {{-- Tombol Aksi hanya ditampilkan jika statusnya memungkinkan --}}

                    {{-- TAMBAHKAN TOMBOL BARU INI DI BAGIAN ATAS --}}
                    @can('manage', $peminjaman)
                        @if ($peminjaman->status === \App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN)
                            {{-- Tombol untuk membuka modal finalisasi --}}
                            <button type="button" class="btn btn-primary btn-sm me-2" data-bs-toggle="modal"
                                data-bs-target="#finalizeApprovalModal">
                                <i class="fas fa-check-double me-1"></i> Finalisasi Persetujuan
                            </button>
                        @endif
                    @endcan

                    {{-- 2. Tombol untuk Guru Membatalkan Pengajuan (Logika sudah di dalam Policy) --}}
                    {{-- Directive @can di sini sudah cukup karena policy 'cancelByUser' sudah memeriksa status --}}
                    @can('cancelByUser', $peminjaman)
                        {{-- PENYESUAIAN: Tambahkan pengecekan status secara eksplisit untuk UI.
                        Tombol ini hanya muncul jika statusnya adalah salah satu dari yang diizinkan di policy.
                        Ini penting agar Admin tidak melihat tombol ini pada peminjaman yang sudah selesai. --}}
                        @if (in_array($peminjaman->status, [
                                \App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN,
                                \App\Models\Peminjaman::STATUS_DISETUJUI,
                            ]))
                            <button type="button" class="btn btn-warning btn-sm text-dark" data-bs-toggle="modal"
                                data-bs-target="#cancelPeminjamanModal">
                                <i class="fas fa-ban me-1"></i> Batalkan Pengajuan
                            </button>
                        @endif
                    @endcan
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Kolom Kiri: Detail Peminjaman --}}
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informasi Transaksi</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <th class="fw-medium" style="width: 40%;">Status</th>
                                        <td>: <span
                                                class="badge fs-6 {{ \App\Models\Peminjaman::statusColor($peminjaman->status) }}">{{ $peminjaman->status }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="fw-medium">Tujuan</th>
                                        <td>: {{ $peminjaman->tujuan_peminjaman }}</td>
                                    </tr>
                                    <tr>
                                        <th class="fw-medium">Peminjam</th>
                                        <td>: {{ optional($peminjaman->guru)->username ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="fw-medium">Tgl. Pengajuan</th>
                                        <td>: {{ $peminjaman->tanggal_pengajuan->isoFormat('dddd, DD MMMM YYYY, HH:mm') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="fw-medium">Rencana Pinjam</th>
                                        <td>:
                                            {{ \Carbon\Carbon::parse($peminjaman->tanggal_rencana_pinjam)->isoFormat('DD MMMM YYYY') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th class="fw-medium">Tenggat Kembali</th>
                                        <td>:
                                            {{ \Carbon\Carbon::parse($peminjaman->tanggal_harus_kembali)->isoFormat('DD MMMM YYYY') }}
                                        </td>
                                    </tr>
                                    @if ($peminjaman->id_ruangan_tujuan_peminjaman)
                                        <tr>
                                            <th class="fw-medium">Ruangan Tujuan</th>
                                            <td>:
                                                {{ optional($peminjaman->ruanganTujuanPeminjaman)->nama_ruangan ?? 'N/A' }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($peminjaman->catatan_peminjam)
                                        <tr>
                                            <th class="fw-medium">Catatan Peminjam</th>
                                            <td>: <span class="fst-italic">"{{ $peminjaman->catatan_peminjam }}"</span>
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($peminjaman->catatan_operator)
                                        <tr>
                                            <th class="fw-medium">Catatan Operator</th>
                                            <td>: <span
                                                    class="text-danger fst-italic">"{{ $peminjaman->catatan_operator }}"</span>
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($peminjaman->disetujui_oleh)
                                        <tr>
                                            <th class="fw-medium">Disetujui Oleh</th>
                                            <td>: {{ optional($peminjaman->disetujuiOlehUser)->username }} (pada
                                                {{ $peminjaman->tanggal_disetujui->isoFormat('DD/MM/YY HH:mm') }})</td>
                                        </tr>
                                    @endif
                                    @if ($peminjaman->ditolak_oleh)
                                        <tr>
                                            <th class="fw-medium">Ditolak Oleh</th>
                                            <td>: {{ optional($peminjaman->ditolakOlehUser)->username }} (pada
                                                {{ $peminjaman->tanggal_ditolak->isoFormat('DD/MM/YY HH:mm') }})</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Detail Barang yang Dipinjam --}}
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Daftar Barang yang Dipinjam</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kode Unit</th>
                                        <th>Nama Barang</th>
                                        <th>Kondisi Awal</th>
                                        <th>Status Unit</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($peminjaman->detailPeminjaman as $detail)
                                        <tr id="row-{{ $detail->id }}">
                                            <td>
                                                <a href="{{ route($rolePrefix . 'barang-qr-code.show', $detail->id_barang_qr_code) }}"
                                                    target="_blank">
                                                    {{ optional($detail->barangQrCode)->kode_inventaris_sekolah ?? 'N/A' }}
                                                </a>
                                            </td>
                                            <td>{{ Str::limit(optional(optional($detail->barangQrCode)->barang)->nama_barang, 35) }}
                                            </td>
                                            <td>{{ $detail->kondisi_sebelum }}</td>

                                            {{-- PENYESUAIAN: Status unit dinamis --}}
                                            <td id="status_unit_{{ $detail->id }}">
                                                <span
                                                    class="badge {{ \App\Models\DetailPeminjaman::statusColor($detail->status_unit) }}">{{ $detail->status_unit }}</span>
                                            </td>
                                            {{-- Kolom Aksi per Item --}}
                                            {{-- PENYESUAIAN: Logika lengkap untuk tombol aksi per item --}}
                                            <td class="text-center" id="actions-{{ $detail->id }}">
                                                {{-- Hanya tampilkan aksi jika peminjaman masih dalam proses --}}
                                                @if (in_array($peminjaman->status, [
                                                        \App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN,
                                                        \App\Models\Peminjaman::STATUS_DISETUJUI,
                                                        \App\Models\Peminjaman::STATUS_SEDANG_DIPINJAM,
                                                        \App\Models\Peminjaman::STATUS_TERLAMBAT,
                                                    ]))
                                                    @switch($detail->status_unit)
                                                        @case(\App\Models\DetailPeminjaman::STATUS_ITEM_DIAJUKAN)
                                                            @can('manage', $peminjaman)
                                                                <div class="btn-group">
                                                                    <button class="btn btn-success btn-sm action-btn-item"
                                                                        data-action="approve" data-detail-id="{{ $detail->id }}"
                                                                        title="Setujui item ini"><i class="fas fa-check"></i></button>
                                                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                                                        data-bs-target="#rejectItemModal{{ $detail->id }}"
                                                                        title="Tolak item ini"><i class="fas fa-times"></i></button>
                                                                </div>
                                                            @endcan
                                                        @break

                                                        @case(\App\Models\DetailPeminjaman::STATUS_ITEM_DISETUJUI)
                                                            @can('processHandover', $detail)
                                                                <button class="btn btn-success btn-sm action-btn" data-action="handover"
                                                                    data-detail-id="{{ $detail->id }}"
                                                                    title="Konfirmasi barang diambil">Serahkan</button>
                                                            @endcan
                                                        @break

                                                        @case(\App\Models\DetailPeminjaman::STATUS_ITEM_DIAMBIL)
                                                            {{-- PENYESUAIAN: Tombol 'Kembalikan' hanya muncul untuk status 'Diambil' --}}
                                                            @can('processReturn', $detail)
                                                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                                                    data-bs-target="#returnItemModal{{ $detail->id }}"
                                                                    title="Proses pengembalian barang">Kembalikan</button>
                                                            @endcan
                                                        @break

                                                        {{-- PENAMBAHAN: Tambahkan case untuk status akhir lainnya untuk menampilkan teks, bukan tombol --}}
                                                        @case(\App\Models\DetailPeminjaman::STATUS_ITEM_DIKEMBALIKAN)
                                                            <span class="text-success fst-italic">Sudah Kembali</span>
                                                        @break

                                                        @case(\App\Models\DetailPeminjaman::STATUS_ITEM_DITOLAK)
                                                            <span class="text-danger fst-italic">Item Ditolak</span>
                                                        @break

                                                        @case(\App\Models\DetailPeminjaman::STATUS_ITEM_RUSAK_SAAT_DIPINJAM)
                                                            <span class="text-warning fst-italic">Dikembalikan (Rusak)</span>
                                                        @break

                                                        @case(\App\Models\DetailPeminjaman::STATUS_ITEM_HILANG_SAAT_DIPINJAM)
                                                            <span class="text-dark fst-italic">Hilang</span>
                                                        @break

                                                        @default
                                                            <span class="text-muted fst-italic">--</span>
                                                    @endswitch
                                                @else
                                                    <span class="text-muted fst-italic">--</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Include Modals --}}
    {{-- 1. Modal Finalize hanya di-include untuk Admin/Operator yang berwenang --}}
    @can('manage', $peminjaman)
        @include('pages.peminjaman.partials._modal_finalize')
    @endcan

    {{-- 2. Modal Cancel hanya di-include untuk pengguna yang bisa membatalkan (Guru/Admin) --}}
    @can('cancelByUser', $peminjaman)
        @include('pages.peminjaman.partials._modal_cancel', ['peminjaman' => $peminjaman])
    @endcan

    {{-- 3. Modal Return dan reject Item selalu di-include karena bisa ada banyak item dengan status berbeda --}}
    {{-- Pengecekan @can untuk tombolnya sudah ada di dalam tabel di atas --}}
    @foreach ($peminjaman->detailPeminjaman as $detail)
        @can('manage', $peminjaman)
            @include('pages.peminjaman.partials._modal_reject_item')
        @endcan
        @can('processReturn', $detail)
            @include('pages.peminjaman.partials._modal_return_item', [
                'detail' => $detail,
                'kondisiList' => $kondisiList,
            ])
        @endcan
    @endforeach

@endsection

@push('scripts')
    {{-- SweetAlert2 dan JQuery diperlukan untuk skrip ini --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fungsi helper untuk menampilkan notifikasi
            const showAlert = (icon, title, timer = 2000) => {
                Swal.fire({
                    icon: icon,
                    title: title,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: timer,
                    timerProgressBar: true
                });
            };

            const getCsrfToken = () => $('meta[name="csrf-token"]').attr('content');
            const rolePrefix = "{{ rtrim($rolePrefix, '.') }}";

            // 1. AKSI SETUJUI ITEM (AJAX)
            $('body').on('click', '.action-btn-item[data-action="approve"]', function() {
                const detailId = $(this).data('detail-id');
                const url = `{{ url('/') }}/${rolePrefix}/peminjaman/detail/${detailId}/approve-item`;
                const actionCell = $(`#actions-${detailId}`);

                Swal.fire({
                    title: 'Setujui Item Ini?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Setujui!',
                    cancelButtonText: 'Batal',
                }).then((result) => {
                    if (result.isConfirmed) {
                        actionCell.html(
                            '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>'
                        );

                        $.ajax({
                            url: url,
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': getCsrfToken()
                            },
                            success: function(response) {
                                showAlert('success', response.message);
                                $(`#status_unit_${detailId}`).html(
                                    `<span class="badge bg-success">Disetujui</span>`
                                );
                                actionCell.html(
                                    `<span class="text-success fst-italic">Telah Disetujui</span>`
                                );
                            },
                            error: function(jqXHR) {
                                showAlert('error', jqXHR.responseJSON?.message ||
                                    'Terjadi kesalahan.');
                                actionCell.html(
                                    '<span class="text-danger">Gagal</span>');
                            }
                        });
                    }
                });
            });

            // 2. AKSI TOLAK ITEM (AJAX dari Modal)
            $('body').on('submit', '.form-reject-item', function(e) {
                e.preventDefault();
                const form = $(this);
                const modal = form.closest('.modal');
                const submitButton = form.find('.btn-submit-reject');
                const detailId = submitButton.data('detail-id');
                const url = `{{ url('/') }}/${rolePrefix}/peminjaman/detail/${detailId}/reject-item`;

                if (!form.find('textarea[name="alasan"]').val()) {
                    showAlert('error', 'Alasan penolakan wajib diisi.');
                    return;
                }

                submitButton.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        $(modal).modal('hide');
                        showAlert('success', response.message);
                        $(`#status_unit_${detailId}`).html(
                            `<span class="badge bg-danger">Ditolak</span>`);
                        $(`#actions-${detailId}`).html(
                            `<span class="text-danger fst-italic">Ditolak</span>`);
                        $(`#row-${detailId}`).addClass('table-secondary text-muted');
                    },
                    error: function(jqXHR) {
                        showAlert('error', jqXHR.responseJSON?.message || 'Terjadi kesalahan.');
                        submitButton.prop('disabled', false).html('Ya, Tolak Item');
                    }
                });
            });

            // 3. AKSI SERAH TERIMA / HANDOVER (AJAX)
            $('body').on('click', '.action-btn[data-action="handover"]', function() {
                const detailId = this.dataset.detailId;
                const url = `{{ url('/') }}/${rolePrefix}/peminjaman/detail/${detailId}/handover`;
                const actionButton = this;

                Swal.fire({
                    title: 'Konfirmasi Penyerahan Barang?',
                    text: "Ini akan mengubah status barang menjadi 'Dipinjam'.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Serahkan!',
                    cancelButtonText: 'Batal',
                }).then((result) => {
                    if (result.isConfirmed) {
                        $(actionButton).prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm"></span>');
                        $.ajax({
                            url: url,
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': getCsrfToken()
                            },
                            success: function() {
                                showAlert('success', 'Barang berhasil diserahkan!');
                                setTimeout(() => location.reload(), 1500);
                            },
                            error: function(jqXHR) {
                                showAlert('error', jqXHR.responseJSON?.message ||
                                    'Terjadi kesalahan.');
                                $(actionButton).prop('disabled', false).text(
                                    'Serahkan');
                            }
                        });
                    }
                });
            });

            // 4. AKSI PENGEMBALIAN (AJAX dari Modal)
            $('body').on('submit', '.form-return-item', function(e) {
                e.preventDefault();
                const form = $(this);
                const modal = form.closest('.modal');
                const submitButton = form.find('.btn-submit-return');
                const detailId = form.data('detail-id');
                const url = `{{ url('/') }}/${rolePrefix}/peminjaman/detail/${detailId}/return`;

                if (!form.find('select[name="kondisi_setelah_kembali"]').val()) {
                    showAlert('error', 'Kondisi barang saat dikembalikan wajib dipilih.');
                    return;
                }

                submitButton.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: form.serialize(),
                    success: function() {
                        $(modal).modal('hide');
                        showAlert('success', 'Pengembalian barang berhasil diproses!');
                        setTimeout(() => location.reload(), 1500);
                    },
                    error: function(jqXHR) {
                        let errorMsg = jqXHR.responseJSON?.message || 'Terjadi kesalahan.';
                        if (jqXHR.status === 422) {
                            errorMsg = Object.values(jqXHR.responseJSON.errors).join(' ');
                        }
                        showAlert('error', errorMsg);
                        submitButton.prop('disabled', false).html('Simpan Status Pengembalian');
                    }
                });
            });

            // 5. PENAMBAHAN: Validasi sebelum menampilkan modal finalisasi
            const finalizeButton = document.querySelector('[data-bs-target="#finalizeApprovalModal"]');
            if (finalizeButton) {
                finalizeButton.addEventListener('click', function(e) {
                    const pendingItems = document.querySelectorAll(
                        '.action-btn-item[data-action="approve"]');
                    if (pendingItems.length > 0) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Tinjauan Belum Selesai',
                            text: `Masih ada ${pendingItems.length} item yang belum Anda setujui atau tolak. Harap proses semua item terlebih dahulu.`,
                        });
                    }
                });
            }
        });
    </script>
@endpush
