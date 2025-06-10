@extends('layouts.app')

@section('title', 'Daftar Peminjaman Barang')

@push('styles')
    {{-- Anda bisa menambahkan style khusus untuk Choices.js atau elemen lain di sini jika perlu --}}
    <style>
        .choices__inner {
            min-height: calc(1.5em + .5rem + 2px);
            padding: .25rem .5rem;
            font-size: .875rem;
            line-height: 1.5;
        }

        .badge {
            font-size: 0.8em;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }
    </style>
@endpush
@php
    // Helper untuk mendapatkan prefix route berdasarkan role user yang login.
    $rolePrefix = Auth::user()->getRolePrefix();
@endphp

@section('content')
    <div class="container-fluid">
        {{-- Page Title & Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Daftar Peminjaman Barang</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Peminjaman</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Filter & Pencarian --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter & Pencarian</h5>
            </div>
            <div class="card-body">
                <form action="{{ route($rolePrefix . 'peminjaman.index') }}" method="GET">
                    <div class="row g-3">
                        {{-- Filter Status Peminjaman --}}
                        <div class="col-md-3">
                            <label for="status_filter" class="form-label">Status Peminjaman</label>
                            <select name="status" id="status_filter" class="form-select form-select-sm"
                                onchange="this.form.submit()">
                                <option value="">-- Semua Status --</option>
                                @foreach ($statusList as $status)
                                    <option value="{{ $status }}"
                                        {{ ($request->status ?? '') == $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Filter Peminjam (Hanya untuk Admin) --}}
                        @if (Auth::user()->hasRole(\App\Models\User::ROLE_ADMIN))
                            <div class="col-md-3">
                                <label for="guru_filter" class="form-label">Peminjam (Guru)</label>
                                <select name="id_guru" id="guru_filter" class="form-select form-select-sm"
                                    onchange="this.form.submit()">
                                    <option value="">-- Semua Guru --</option>
                                    @foreach ($guruList as $guru)
                                        <option value="{{ $guru->id }}"
                                            {{ ($request->id_guru ?? '') == $guru->id ? 'selected' : '' }}>
                                            {{ $guru->username }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- Filter Tanggal --}}
                        <div class="col-md-4">
                            <label for="tanggal_mulai" class="form-label">Tanggal Pengajuan</label>
                            <div class="input-group input-group-sm">
                                <input type="date" class="form-control" name="tanggal_mulai"
                                    value="{{ $request->tanggal_mulai ?? '' }}">
                                <span class="input-group-text">s/d</span>
                                <input type="date" class="form-control" name="tanggal_selesai"
                                    value="{{ $request->tanggal_selesai ?? '' }}">
                            </div>
                        </div>

                        {{-- Tombol Filter & Reset --}}
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="btn-group w-100">
                                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                                <a href="{{ route($rolePrefix . 'peminjaman.index') }}"
                                    class="btn btn-outline-secondary btn-sm">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tombol Aksi & Informasi --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                {{-- Informasi tambahan jika ada --}}
            </div>
            <div>
                {{-- Tombol Tambah Pengajuan (Hanya untuk Guru) --}}
                @can('create', App\Models\Peminjaman::class)
                    <a href="{{ route('guru.peminjaman.create') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus-circle-outline me-1"></i> Buat Pengajuan Peminjaman
                    </a>
                @endcan
            </div>
        </div>

        {{-- Tabel Daftar Peminjaman --}}
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Peminjam</th>
                                <th>Tujuan</th>
                                <th>Tgl Pengajuan</th>
                                <th>Tgl Harus Kembali</th>
                                <th class="text-center">Jumlah Item</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($peminjamanList as $peminjaman)
                                <tr>
                                    <td>#{{ $peminjaman->id }}</td>
                                    <td>{{ optional($peminjaman->guru)->username ?? 'N/A' }}</td>
                                    <td>{{ Str::limit($peminjaman->tujuan_peminjaman, 40) }}</td>
                                    <td>{{ $peminjaman->tanggal_pengajuan->isoFormat('DD MMM YYYY, HH:mm') }}</td>
                                    <td>{{ $peminjaman->tanggal_harus_kembali ? $peminjaman->tanggal_harus_kembali->isoFormat('DD MMM YYYY') : '-' }}
                                    </td>
                                    <td class="text-center">{{ $peminjaman->detail_peminjaman_count }}</td>
                                    <td class="text-center">
                                        <span
                                            class="badge {{ \App\Models\Peminjaman::statusColor($peminjaman->status) }}">{{ $peminjaman->status }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2 justify-content-center">
                                            @can('view', $peminjaman)
                                                <a href="{{ route($rolePrefix . 'peminjaman.show', $peminjaman->id) }}"
                                                    class="btn btn-outline-info btn-sm" title="Lihat Detail"
                                                    data-bs-toggle="tooltip">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            @endcan
                                            @can('update', $peminjaman)
                                                <a href="{{ route($rolePrefix . 'peminjaman.edit', $peminjaman->id) }}"
                                                    class="btn btn-outline-warning btn-sm" title="Edit Pengajuan"
                                                    data-bs-toggle="tooltip">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan
                                            @can('delete', $peminjaman)
                                                <button type="button" class="btn btn-outline-danger btn-sm" title="Arsipkan"
                                                    data-bs-toggle="modal" data-bs-target="#archiveModal{{ $peminjaman->id }}">
                                                    <i class="fas fa-archive"></i>
                                                </button>
                                                {{-- @include('admin.peminjaman._modal_archive') --}}
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-folder-open fs-3 text-muted mb-2"></i><br>
                                        Tidak ada data peminjaman yang ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-3">
                    {{ $peminjamanList->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Inisialisasi Choices.js untuk select filter jika ada
        document.addEventListener('DOMContentLoaded', function() {
            const config = {
                removeItemButton: true,
                shouldSort: false
            };
            const statusFilter = document.getElementById('status_filter');
            if (statusFilter) new Choices(statusFilter, {
                ...config,
                searchEnabled: false
            });

            const guruFilter = document.getElementById('guru_filter');
            if (guruFilter) new Choices(guruFilter, config);
        });
    </script>
@endpush
