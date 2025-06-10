@extends('layouts.app')

@section('title', 'Laporan Pemeliharaan Aset')

@push('styles')
    {{-- Anda bisa menambahkan style untuk Select2 di sini jika mau --}}
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
                    <h4 class="mb-sm-0">Laporan Pemeliharaan</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Laporan Pemeliharaan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Filter --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter Laporan</h5>
            </div>
            <div class="card-body">
                <form action="{{ route($rolePrefix . 'laporan.pemeliharaan') }}" method="GET">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="status_pengajuan" class="form-label">Status Pengajuan</label>
                            <select name="status_pengajuan" id="status_pengajuan" class="form-select form-select-sm">
                                <option value="">-- Semua --</option>
                                @foreach ($statusPengajuanList as $status)
                                    <option value="{{ $status }}"
                                        {{ $request->status_pengajuan == $status ? 'selected' : '' }}>{{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status_pengerjaan" class="form-label">Status Pengerjaan</label>
                            <select name="status_pengerjaan" id="status_pengerjaan" class="form-select form-select-sm">
                                <option value="">-- Semua --</option>
                                @foreach ($statusPengerjaanList as $status)
                                    <option value="{{ $status }}"
                                        {{ $request->status_pengerjaan == $status ? 'selected' : '' }}>{{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if (Auth::user()->hasRole(\App\Models\User::ROLE_ADMIN))
                            <div class="col-md-2">
                                <label for="id_pelapor" class="form-label">Pelapor</label>
                                <select name="id_pelapor" id="id_pelapor" class="form-select form-select-sm">
                                    <option value="">-- Semua --</option>
                                    @foreach ($userList as $user)
                                        <option value="{{ $user->id }}"
                                            {{ $request->id_pelapor == $user->id ? 'selected' : '' }}>{{ $user->username }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <label class="form-label">Rentang Tanggal Laporan</label>
                            <div class="input-group input-group-sm">
                                <input type="date" class="form-control" name="tanggal_mulai"
                                    value="{{ $request->tanggal_mulai ?? '' }}">
                                <span class="input-group-text">s/d</span>
                                <input type="date" class="form-control" name="tanggal_selesai"
                                    value="{{ $request->tanggal_selesai ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-12 text-end">
                            <button type="submit" class="btn btn-primary btn-sm">Terapkan Filter</button>
                            <a href="{{ route($rolePrefix . 'laporan.pemeliharaan') }}"
                                class="btn btn-outline-secondary btn-sm">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Card Hasil Laporan --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-file-alt me-2"></i>Hasil Laporan Pemeliharaan</h5>
                <a href="{{ route($rolePrefix . 'laporan.pemeliharaan.excel', request()->query()) }}"
                    class="btn btn-success btn-sm"><i class="fas fa-file-excel me-1"></i>Export Excel</a>
                <a href="{{ route($rolePrefix . 'laporan.pemeliharaan.pdf', request()->query()) }}" target="_blank"
                    class="btn btn-danger btn-sm"><i class="fas fa-file-pdf me-1"></i>Cetak PDF</a>

                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Barang</th>
                                <th>Kerusakan</th>
                                <th>Tgl. Lapor</th>
                                <th>Pelapor</th>
                                <th>PIC</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pemeliharaanList as $item)
                                <tr>
                                    <td><a href="{{ route($rolePrefix . 'pemeliharaan.show', $item->id) }}"
                                            class="fw-bold">#{{ $item->id }}</a></td>
                                    <td>
                                        {{ optional(optional($item->barangQrCode)->barang)->nama_barang ?? 'N/A' }}
                                        <br>
                                        <small
                                            class="text-muted">{{ optional($item->barangQrCode)->kode_inventaris_sekolah ?? '' }}</small>
                                    </td>
                                    <td title="{{ $item->catatan_pengajuan }}">
                                        {{ Str::limit($item->catatan_pengajuan, 50) }}</td>
                                    <td>{{ $item->tanggal_pengajuan->isoFormat('DD MMM YY') }}</td>
                                    <td>{{ optional($item->pengaju)->username }}</td>
                                    <td>{{ optional($item->operatorPengerjaan)->username ?? '-' }}</td>
                                    <td class="text-center">
                                        <span
                                            class="badge text-wrap {{ \App\Models\Pemeliharaan::statusColor($item->status_pemeliharaan) }}">{{ $item->status_pemeliharaan }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">Tidak ada data laporan pemeliharaan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end mt-3">{{ $pemeliharaanList->links() }}</div>
            </div>
        </div>
    </div>
@endsection
