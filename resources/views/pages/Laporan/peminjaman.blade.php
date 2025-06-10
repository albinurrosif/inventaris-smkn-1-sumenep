@extends('layouts.app')

@section('title', 'Laporan Peminjaman Barang')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        .table th,
        .table td {
            vertical-align: middle;
            font-size: 0.85rem;
        }
    </style>
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
                    <h4 class="mb-sm-0">Laporan Peminjaman</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Laporan Peminjaman</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Filter --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter Laporan Peminjaman</h5>
            </div>
            <div class="card-body">
                <form action="{{ route($rolePrefix . 'laporan.peminjaman') }}" method="GET">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select form-select-sm select2-filter">
                                <option value="">-- Semua Status --</option>
                                @foreach ($statusList as $status)
                                    <option value="{{ $status }}" {{ $request->status == $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if (Auth::user()->hasRole(\App\Models\User::ROLE_ADMIN))
                            <div class="col-md-3">
                                <label for="id_guru" class="form-label">Peminjam</label>
                                <select name="id_guru" id="id_guru" class="form-select form-select-sm select2-filter">
                                    <option value="">-- Semua Peminjam --</option>
                                    @foreach ($guruList as $guru)
                                        <option value="{{ $guru->id }}"
                                            {{ $request->id_guru == $guru->id ? 'selected' : '' }}>
                                            {{ $guru->username }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <label class="form-label">Rentang Tanggal Pengajuan</label>
                            <div class="input-group input-group-sm">
                                <input type="date" class="form-control" name="tanggal_mulai"
                                    value="{{ $request->tanggal_mulai ?? '' }}">
                                <span class="input-group-text">s/d</span>
                                <input type="date" class="form-control" name="tanggal_selesai"
                                    value="{{ $request->tanggal_selesai ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="btn-group w-100">
                                <button type="submit" class="btn btn-primary btn-sm"><i
                                        class="fas fa-search me-1"></i>Filter</button>
                                <a href="{{ route($rolePrefix . 'laporan.peminjaman') }}"
                                    class="btn btn-outline-secondary btn-sm" title="Reset Filter"><i
                                        class="fas fa-undo"></i></a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Card Hasil Laporan --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-file-invoice me-2"></i>Hasil Laporan Peminjaman</h5>
                <div>
                    {{-- TODO: Tambahkan route untuk export --}}
                    <a href="{{ route($rolePrefix . 'laporan.peminjaman.excel', request()->query()) }}"
                        class="btn btn-success btn-sm"><i class="fas fa-file-excel me-1"></i>Export Excel</a>
                    <a href="{{ route($rolePrefix . 'laporan.peminjaman.pdf', request()->query()) }}" target="_blank"
                        class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf me-1"></i>Cetak PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Peminjam</th>
                                <th>Tujuan</th>
                                <th>Tgl. Pengajuan</th>
                                <th>Tgl. Kembali</th>
                                <th class="text-center">Item</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($peminjamanList as $peminjaman)
                                <tr>
                                    <td>
                                        <a href="{{ route($rolePrefix . 'peminjaman.show', $peminjaman->id) }}"
                                            class="fw-bold">#{{ $peminjaman->id }}</a>
                                    </td>
                                    <td>{{ optional($peminjaman->guru)->username }}</td>
                                    <td title="{{ $peminjaman->tujuan_peminjaman }}">
                                        {{ Str::limit($peminjaman->tujuan_peminjaman, 40) }}</td>
                                    <td>{{ $peminjaman->tanggal_pengajuan->isoFormat('DD MMM YY') }}</td>
                                    <td>{{ optional($peminjaman->tanggal_harus_kembali)->isoFormat('DD MMM YY') }}</td>
                                    <td class="text-center">{{ $peminjaman->detail_peminjaman_count }}</td>
                                    <td class="text-center">
                                        <span
                                            class="badge {{ \App\Models\Peminjaman::statusColor($peminjaman->status) }}">{{ $peminjaman->status }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">Tidak ada data peminjaman yang cocok.</td>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2-filter').select2({
                theme: "bootstrap-5",
                width: '100%',
            });
        });
    </script>
@endpush
