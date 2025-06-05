@extends('layouts.app')

@section('title', 'Rekapitulasi Stok Barang')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        #tabelRekapStok th,
        #tabelRekapStok td {
            vertical-align: middle;
            font-size: 0.85rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single {
            height: calc(1.5em + .5rem + 2px) !important;
            padding: .25rem .5rem !important;
            font-size: .875rem !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Rekapitulasi Stok Barang</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Rekap Stok</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter Rekap Stok</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.rekap-stok.index') }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label for="search_rekap" class="form-label">Nama/Kode Barang</label>
                            <input type="text" name="search" id="search_rekap" class="form-control form-control-sm"
                                placeholder="Cari barang..." value="{{ $searchTerm ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label for="id_ruangan_filter" class="form-label">Ruangan</label>
                            <select name="id_ruangan" id="id_ruangan_filter"
                                class="form-select form-select-sm select2-filter">
                                <option value="">-- Semua Ruangan --</option>
                                @foreach ($ruanganList as $ruangan)
                                    <option value="{{ $ruangan->id }}"
                                        {{ ($ruanganFilter ?? '') == $ruangan->id ? 'selected' : '' }}>
                                        {{ $ruangan->nama_ruangan }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="id_kategori_filter" class="form-label">Kategori Barang</label>
                            <select name="id_kategori" id="id_kategori_filter"
                                class="form-select form-select-sm select2-filter">
                                <option value="">-- Semua Kategori --</option>
                                @foreach ($kategoriList as $kategori)
                                    <option value="{{ $kategori->id }}"
                                        {{ ($kategoriFilter ?? '') == $kategori->id ? 'selected' : '' }}>
                                        {{ $kategori->nama_kategori }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="periode_rekap_filter" class="form-label">Periode (Bulan-Tahun)</label>
                            <input type="month" name="periode_rekap" id="periode_rekap_filter"
                                class="form-control form-control-sm" value="{{ $periodeFilter ?? '' }}">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button class="btn btn-primary btn-sm w-100" type="submit"><i class="fas fa-filter"></i>
                                Terapkan Filter</button>
                            @if ($searchTerm || $ruanganFilter || $kategoriFilter || $periodeFilter)
                                <a href="{{ route('admin.rekap-stok.index') }}"
                                    class="btn btn-outline-secondary btn-sm w-100" title="Reset Filter">
                                    <i class="fas fa-times"></i> Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-clipboard-list me-2"></i>Daftar Rekap Stok</h5>
                {{-- Tombol untuk generate rekap manual bisa ditambahkan di sini jika ada metodenya di controller --}}
                {{-- @can('create', App\Models\RekapStok::class)
                    <form action="{{ route('admin.rekap-stok.generate') }}" method="POST"> @csrf
                        <button type="submit" class="btn btn-info btn-sm">Generate Rekap Bulan Ini</button>
                    </form>
                @endcan --}}
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table id="tabelRekapStok" class="table table-sm table-bordered table-hover dt-responsive nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">No</th>
                                <th>Periode Rekap</th>
                                <th>Ruangan</th>
                                <th>Kode Barang</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th class="text-center">Stok Sistem</th>
                                <th class="text-center">Stok Fisik Terakhir</th>
                                <th class="text-center">Selisih</th>
                                <th>Catatan</th>
                                {{-- <th class="text-center">Aksi</th> --}}
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rekapStokList as $index => $rekap)
                                @php
                                    $selisih =
                                        $rekap->jumlah_fisik_terakhir !== null
                                            ? $rekap->jumlah_fisik_terakhir - $rekap->jumlah_tercatat_sistem
                                            : null;
                                    $selisihClass = '';
                                    if ($selisih !== null) {
                                        if ($selisih < 0) {
                                            $selisihClass = 'text-danger fw-bold';
                                        }
                                        // Kurang
                                        elseif ($selisih > 0) {
                                            $selisihClass = 'text-warning fw-bold';
                                        } // Lebih
                                    }
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $rekapStokList->firstItem() + $index }}</td>
                                    <td data-sort="{{ $rekap->periode_rekap }}">
                                        {{ \Carbon\Carbon::parse($rekap->periode_rekap)->isoFormat('MMMM YYYY') }}</td>
                                    <td>{{ optional($rekap->ruangan)->nama_ruangan ?? 'N/A' }}</td>
                                    <td><code>{{ optional($rekap->barang)->kode_barang ?? 'N/A' }}</code></td>
                                    <td>
                                        <a href="{{ route('barang.show', optional($rekap->barang)->id) }}" target="_blank">
                                            {{ optional($rekap->barang)->nama_barang ?? 'N/A' }}
                                        </a>
                                    </td>
                                    <td><span
                                            class="badge bg-secondary">{{ optional(optional($rekap->barang)->kategori)->nama_kategori ?? '-' }}</span>
                                    </td>
                                    <td class="text-center">{{ $rekap->jumlah_tercatat_sistem }}</td>
                                    <td class="text-center">{{ $rekap->jumlah_fisik_terakhir ?? '-' }}</td>
                                    <td class="text-center {{ $selisihClass }}">
                                        {{ $selisih !== null ? ($selisih > 0 ? '+' . $selisih : $selisih) : '-' }}
                                    </td>
                                    <td data-bs-toggle="tooltip" title="{{ $rekap->catatan }}">
                                        {{ Str::limit($rekap->catatan, 50) }}</td>
                                    {{-- <td class="text-center">
                                        @can('view', $rekap)
                                        <a href="{{ route('admin.rekap-stok.show', $rekap->id) }}" class="btn btn-info btn-sm" data-bs-toggle="tooltip" title="Detail Rekap">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @endcan
                                    </td> --}}
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">
                                        Tidak ada data rekap stok yang cocok dengan kriteria filter Anda.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($rekapStokList instanceof \Illuminate\Pagination\LengthAwarePaginator && $rekapStokList->hasPages())
                    <div class="mt-3 d-flex justify-content-end">
                        {{ $rekapStokList->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> {{-- Jika belum ada --}}

    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#tabelRekapStok')) {
                $('#tabelRekapStok').DataTable().destroy();
            }
            if ($('#tabelRekapStok tbody tr').length > 0 && !$('#tabelRekapStok tbody tr td[colspan="10"]')
                .length) {
                $('#tabelRekapStok').DataTable({
                    responsive: true,
                    paging: false,
                    searching: false,
                    info: false,
                    ordering: true,
                    order: [
                        [1, 'desc'],
                        [2, 'asc'],
                        [4, 'asc']
                    ], // Default sort by Periode (desc), Ruangan, Nama Barang
                    columnDefs: [{
                        targets: [0],
                        orderable: false,
                        searchable: false
                    }],
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                    },
                });
            }

            $('.select2-filter').select2({
                theme: "bootstrap-5",
                width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' :
                    'style',
                placeholder: $(this).data('placeholder') || "-- Pilih --",
                allowClear: true
            });

            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endpush
