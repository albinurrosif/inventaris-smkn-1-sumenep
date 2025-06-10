@extends('layouts.app')

@section('title', 'Laporan Inventaris Barang')

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
                    <h4 class="mb-sm-0">Laporan Inventaris Barang</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Laporan Inventaris</li>
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
                <form action="{{ route($rolePrefix . 'laporan.inventaris') }}" method="GET">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="id_ruangan" class="form-label">Ruangan</label>
                            <select name="id_ruangan" id="id_ruangan" class="form-select form-select-sm select2-filter">
                                <option value="">-- Semua Ruangan --</option>
                                @foreach ($ruanganList as $ruangan)
                                    <option value="{{ $ruangan->id }}"
                                        {{ $request->id_ruangan == $ruangan->id ? 'selected' : '' }}>
                                        {{ $ruangan->nama_ruangan }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="id_kategori" class="form-label">Kategori</label>
                            <select name="id_kategori" id="id_kategori" class="form-select form-select-sm select2-filter">
                                <option value="">-- Semua Kategori --</option>
                                @foreach ($kategoriList as $kategori)
                                    <option value="{{ $kategori->id }}"
                                        {{ $request->id_kategori == $kategori->id ? 'selected' : '' }}>
                                        {{ $kategori->nama_kategori }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="kondisi" class="form-label">Kondisi</label>
                            <select name="kondisi" id="kondisi" class="form-select form-select-sm select2-filter">
                                <option value="">-- Semua Kondisi --</option>
                                @foreach ($kondisiList as $kondisi)
                                    <option value="{{ $kondisi }}"
                                        {{ $request->kondisi == $kondisi ? 'selected' : '' }}>
                                        {{ $kondisi }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="tahun_perolehan" class="form-label">Tahun Perolehan</label>
                            <input type="number" name="tahun_perolehan" id="tahun_perolehan"
                                class="form-control form-control-sm" placeholder="Contoh: 2023"
                                value="{{ $request->tahun_perolehan }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="btn-group w-100">
                                <button type="submit" class="btn btn-primary btn-sm"><i
                                        class="fas fa-search me-1"></i>Terapkan</button>
                                <a href="{{ route($rolePrefix . 'laporan.inventaris') }}"
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
                <h5 class="card-title mb-0"><i class="fas fa-file-alt me-2"></i>Hasil Laporan</h5>
                <div>
                    {{-- TODO: Tambahkan route untuk export PDF dan Excel --}}
                    <a href="{{ route($rolePrefix . 'laporan.inventaris.excel', request()->query()) }}"
                        class="btn btn-success btn-sm"><i class="fas fa-file-excel me-1"></i>Export Excel</a>
                    <a href="{{ route($rolePrefix . 'laporan.inventaris.pdf', request()->query()) }}" target="_blank"
                        class="btn btn-danger btn-sm">
                        <i class="fas fa-file-pdf me-1"></i>Cetak PDF
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">No</th>
                                <th>Kode Inventaris</th>
                                <th>Nama Barang</th>
                                <th>Merk / Model</th>
                                <th>No. Seri</th>
                                <th>Lokasi / Pemegang</th>
                                <th>Tgl. Perolehan</th>
                                <th class="text-end">Harga (Rp)</th>
                                <th class="text-center">Kondisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($inventaris as $index => $item)
                                <tr>
                                    <td class="text-center">{{ $inventaris->firstItem() + $index }}</td>
                                    <td><code>{{ $item->kode_inventaris_sekolah }}</code></td>
                                    <td>{{ optional($item->barang)->nama_barang }}</td>
                                    <td>{{ optional($item->barang)->merk_model ?? '-' }}</td>
                                    <td>{{ $item->no_seri_pabrik ?? '-' }}</td>
                                    <td>
                                        @if ($item->ruangan)
                                            <span class="badge bg-info text-dark">{{ $item->ruangan->nama_ruangan }}</span>
                                        @elseif($item->pemegangPersonal)
                                            <span class="badge bg-primary">{{ $item->pemegangPersonal->username }}</span>
                                        @else
                                            <span class="badge bg-secondary">N/A</span>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($item->tanggal_perolehan_unit)->isoFormat('DD MMM YYYY') }}
                                    </td>
                                    <td class="text-end">{{ number_format($item->harga_perolehan_unit, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        <span
                                            class="badge {{ \App\Models\BarangQrCode::getKondisiColor($item->kondisi) }}">{{ $item->kondisi }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        Tidak ada data yang cocok dengan filter yang diterapkan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- Pagination Links --}}
                <div class="d-flex justify-content-end mt-3">
                    {{ $inventaris->links() }}
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
