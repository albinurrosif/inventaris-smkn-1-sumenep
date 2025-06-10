@extends('layouts.app')

@section('title', 'Daftar Barang di Ruangan Anda')

@push('styles')
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    {{-- Judul disesuaikan untuk Operator --}}
                    <h4 class="mb-sm-0">Daftar Barang</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            {{-- Route disesuaikan ke dashboard operator --}}
                            <li class="breadcrumb-item"><a href="{{ route('operator.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Daftar Barang</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Filter --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter & Pencarian Barang</h5>
            </div>
            <div class="card-body">
                {{-- Form action mengarah ke route operator --}}
                <form method="GET" action="{{ route('operator.barang.index') }}" id="filterFormJenisBarang">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="id_kategori_filter" class="form-label mb-1">Kategori</label>
                            <select name="id_kategori" id="id_kategori_filter" class="form-control"
                                data-choices-removeItemButton="true"
                                onchange="document.getElementById('filterFormJenisBarang').submit()">
                                <option value="">-- Semua Kategori --</option>
                                @foreach ($kategoriList as $kategori)
                                    <option value="{{ $kategori->id }}"
                                        {{ ($kategoriId ?? '') == $kategori->id ? 'selected' : '' }}>
                                        {{ $kategori->nama_kategori }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="id_ruangan_filter" class="form-label mb-1">Ruangan Anda</label>
                            <select name="id_ruangan" id="id_ruangan_filter" class="form-control"
                                data-choices-removeItemButton="true"
                                onchange="document.getElementById('filterFormJenisBarang').submit()">
                                <option value="">-- Semua Ruangan Anda --</option>
                                @foreach ($ruanganList as $ruanganItem)
                                    <option value="{{ $ruanganItem->id }}"
                                        {{ ($ruanganId ?? '') == $ruanganItem->id ? 'selected' : '' }}>
                                        {{ $ruanganItem->nama_ruangan }} ({{ $ruanganItem->kode_ruangan }})
                                    </option>
                                @endforeach
                                <option value="tanpa-ruangan" {{ ($ruanganId ?? '') == 'tanpa-ruangan' ? 'selected' : '' }}>
                                    Tanpa Ruangan (Dipegang Personal)
                                </option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="search_filter" class="form-label mb-1">Pencarian</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="search" id="search_filter" class="form-control"
                                    placeholder="Nama, Kode, Merk/Model..." value="{{ $searchTerm ?? '' }}">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-md-2 d-grid">
                            <label for="btn_reset_filter_barang" class="form-label mb-1">&nbsp;</label>
                            {{-- Arahkan reset ke route operator --}}
                            <a href="{{ route('operator.barang.index') }}" id="btn_reset_filter_barang"
                                class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-sync-alt me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tombol Aksi Global --}}
        <div class="mb-3 d-flex justify-content-end align-items-center">
            <div class="d-flex align-items-center gap-2">
                {{-- Tombol Create dan Import Dihilangkan untuk Operator --}}
                @can('export', App\Models\BarangQrCode::class)
                    {{-- Logika Export dipertahankan --}}
                    <a href="{{-- route('operator.barang-qr-code.export-excel') --}}" class="btn btn-outline-success btn-sm">
                        <i class="mdi mdi-file-excel me-1"></i>Export Excel
                    </a>
                    <a href="{{-- route('operator.barang-qr-code.export-pdf') --}}" class="btn btn-outline-danger btn-sm">
                        <i class="mdi mdi-file-pdf-box me-1"></i> Export PDF
                    </a>
                @endcan
            </div>
        </div>

        {{-- Card Tabel Data --}}
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="mdi mdi-format-list-bulleted me-2"></i>Data Barang di Ruangan Anda
                </h4>
            </div>
            <div class="card-body">
                @if ($operatorTidakAdaRuangan ?? false)
                    <div class="alert alert-warning text-center" role="alert">
                        Anda saat ini tidak ditugaskan untuk mengelola ruangan manapun. <br> Tidak ada
                        jenis barang yang dapat ditampilkan. Silakan hubungi Admin.
                    </div>
                @endif
                <div class="table-responsive">
                    <table id="barangTable" class="table table-hover table-striped dt-responsive align-middle nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nama Barang</th>
                                <th>Kode</th>
                                <th>Kategori</th>
                                <th>Merk/Model</th>
                                <th>Tahun</th>
                                <th class="text-center">Jml. Unit di Ruangan Anda</th>
                                <th style="width: 120px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($barangs as $index => $item)
                                <tr>
                                    <td>{{ $barangs->firstItem() + $index }}</td>
                                    <td>
                                        {{-- Link ke route operator.barang.show --}}
                                        <a href="{{ route('operator.barang.show', $item->id) }}"
                                            class="fw-medium">{{ $item->nama_barang }}</a>
                                    </td>
                                    <td>{{ $item->kode_barang }}</td>
                                    <td>{{ $item->kategori->nama_kategori ?? '-' }}</td>
                                    <td>{{ $item->merk_model ?? '-' }}</td>
                                    <td>{{ $item->tahun_pembuatan ?? '-' }}</td>
                                    <td class="text-center">{{ $item->active_qr_codes_count }}</td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            {{-- Aksi untuk Operator hanya VIEW --}}
                                            @can('view', $item)
                                                <a href="{{ route('operator.barang.show', $item->id) }}"
                                                    class="btn btn-outline-info btn-sm" title="Lihat Detail & Unit">
                                                    <i class="fas fa-eye"></i> Lihat Unit
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        @if ($operatorTidakAdaRuangan ?? false)
                                            <i class="fas fa-folder-open fs-3 text-muted mb-2"></i><br>
                                            Anda tidak memiliki akses ke jenis barang manapun.
                                        @else
                                            <i class="fas fa-folder-open fs-3 text-muted mb-2"></i><br>
                                            Tidak ada barang yang cocok dengan filter di ruangan yang Anda kelola.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- Paginasi --}}
                @if ($barangs->hasPages())
                    <div class="mt-3 d-flex justify-content-end">
                        {{ $barangs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Script untuk Choices.js bisa tetap sama, tidak perlu diubah --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('id_kategori_filter')) {
                new Choices(document.getElementById('id_kategori_filter'), {
                    removeItemButton: true,
                    searchPlaceholderValue: "Cari..."
                });
            }
            if (document.getElementById('id_ruangan_filter')) {
                new Choices(document.getElementById('id_ruangan_filter'), {
                    removeItemButton: true,
                    searchPlaceholderValue: "Cari..."
                });
            }
        });
    </script>
@endpush
