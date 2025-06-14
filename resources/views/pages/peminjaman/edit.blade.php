@extends('layouts.app')

@php
    // Variabel helper ini sangat berguna, kita pertahankan
    $rolePrefix = Auth::user()->getRolePrefix();
    $isGuru = Auth::user()->hasRole(\App\Models\User::ROLE_GURU);
    // Pengecekan apakah form bisa diedit oleh GURU
    $isManageableByGuru =
        $isGuru && in_array($peminjaman->status, [\App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN]);
@endphp

@section('title', 'Edit Pengajuan Peminjaman #' . $peminjaman->id)

@push('styles')
    {{-- (Tidak ada perubahan di sini) --}}
@endpush

@section('content')
    <div class="container-fluid">
        {{-- Page Title & Breadcrumb (Tidak ada perubahan) --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Edit Peminjaman #{{ $peminjaman->id }}</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route($rolePrefix . 'dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route($rolePrefix . 'peminjaman.index') }}">Peminjaman</a></li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route($rolePrefix . 'peminjaman.update', $peminjaman->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                {{-- Kolom Kiri: Daftar Barang yang Dipinjam --}}
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">1. Barang yang Diajukan</h5>
                            @if ($isManageableByGuru)
                                {{-- Di dalam card-header halaman edit --}}
                                <a href="{{ route('guru.katalog.index', ['peminjaman_id' => $peminjaman->id]) }}"
                                    class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i> Tambah/Ubah Barang dari Katalog
                                </a>
                            @endif
                        </div>
                        <div class="card-body">
                            {{-- Tampilkan daftar barang yang sudah ada di peminjaman ini --}}
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Barang</th>
                                            <th>Kode Unit</th>
                                            @if ($isManageableByGuru)
                                                <th class="text-end">Aksi</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($peminjaman->detailPeminjaman as $detail)
                                            <tr>
                                                <td>
                                                    {{ $detail->barangQrCode->barang->nama_barang }}<br>
                                                    <small class="text-muted">Lokasi:
                                                        {{ optional($detail->barangQrCode->ruangan)->nama_ruangan ?? 'N/A' }}</small>
                                                </td>
                                                <td>{{ $detail->barangQrCode->kode_inventaris_sekolah }}</td>
                                                @if ($isManageableByGuru)
                                                    <td class="text-end">
                                                        {{-- Tombol hapus item dari pengajuan (memerlukan route baru) --}}
                                                        <button type="button" class="btn btn-sm btn-outline-danger py-0"
                                                            data-bs-toggle="modal" data-bs-target="#universalConfirmModal"
                                                            data-message="Hapus '{{ $detail->barangQrCode->barang->nama_barang }}' dari pengajuan ini?"
                                                            data-form-id="form-remove-item-{{ $detail->id }}">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </td>
                                                @endif
                                            </tr>
                                            {{-- Ini akan mengirimkan kembali ID barang yang sudah ada --}}
                                            <input type="hidden" name="id_barang_qr_code[]"
                                                value="{{ $detail->id_barang_qr_code }}">

                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">Tidak ada barang dalam
                                                    pengajuan ini.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kolom Kanan: Detail Pengajuan --}}
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">2. Detail Pengajuan</h5>
                        </div>
                        <div class="card-body">
                            {{-- Semua field ini akan 'disabled' jika $isManageableByGuru bernilai false --}}
                            <div class="mb-3">
                                <label for="tujuan_peminjaman" class="form-label">Tujuan Peminjaman</label>
                                <textarea class="form-control" name="tujuan_peminjaman" rows="3"
                                    @if (!$isManageableByGuru) disabled @endif>{{ old('tujuan_peminjaman', $peminjaman->tujuan_peminjaman) }}</textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tanggal_rencana_pinjam" class="form-label">Tgl. Pinjam</label>
                                    <input type="date" class="form-control" name="tanggal_rencana_pinjam"
                                        value="{{ old('tanggal_rencana_pinjam', $peminjaman->tanggal_rencana_pinjam->format('Y-m-d')) }}"
                                        @if (!$isManageableByGuru) disabled @endif>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="tanggal_harus_kembali" class="form-label">Tgl. Kembali</label>
                                    <input type="date" class="form-control" name="tanggal_harus_kembali"
                                        value="{{ old('tanggal_harus_kembali', $peminjaman->tanggal_harus_kembali->format('Y-m-d')) }}"
                                        @if (!$isManageableByGuru) disabled @endif>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="id_ruangan_tujuan_peminjaman" class="form-label">Ruangan Tujuan</label>
                                <select class="form-select" name="id_ruangan_tujuan_peminjaman"
                                    @if (!$isManageableByGuru) disabled @endif>
                                    <option value="">-- Tidak ada ruangan spesifik --</option>
                                    @foreach ($ruanganTujuanList as $ruangan)
                                        <option value="{{ $ruangan->id }}"
                                            {{ old('id_ruangan_tujuan_peminjaman', $peminjaman->id_ruangan_tujuan_peminjaman) == $ruangan->id ? 'selected' : '' }}>
                                            {{ $ruangan->nama_ruangan }} ({{ $ruangan->kode_ruangan }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="catatan_peminjam" class="form-label">Catatan Peminjam</label>
                                <textarea class="form-control" name="catatan_peminjam" rows="2" @if (!$isManageableByGuru) disabled @endif>{{ old('catatan_peminjam', $peminjaman->catatan_peminjam) }}</textarea>
                            </div>

                            {{-- HANYA UNTUK ADMIN/OPERATOR --}}
                            @can('manage', $peminjaman)
                                <div class="mb-3">
                                    <label for="catatan_operator" class="form-label">Catatan Operator</label>
                                    <textarea class="form-control" id="catatan_operator" name="catatan_operator" rows="2">{{ old('catatan_operator', $peminjaman->catatan_operator) }}</textarea>
                                </div>
                            @endcan
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <a href="{{ route($rolePrefix . 'peminjaman.show', $peminjaman->id) }}"
                            class="btn btn-light me-2">Batal</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan
                            Perubahan</button>
                    </div>
                </div>
            </div>
        </form>

        {{-- Form tersembunyi untuk menghapus item --}}
        @if ($isManageableByGuru)
            @foreach ($peminjaman->detailPeminjaman as $detail)
                <form id="form-remove-item-{{ $detail->id }}"
                    action="{{ route('guru.peminjaman.removeItem', ['peminjaman' => $peminjaman->id, 'detailPeminjaman' => $detail->id]) }}"
                    method="POST" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        @endif

    </div>
@endsection

@push('scripts')
    {{-- JavaScript untuk modal universal sudah ada di layouts.app, tidak perlu script tambahan di sini --}}
@endpush
