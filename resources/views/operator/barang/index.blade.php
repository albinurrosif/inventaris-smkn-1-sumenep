@extends('layouts.app')

@section('title', 'Daftar Jenis Barang (Lingkup Anda)')

@push('styles')
    {{-- Jika menggunakan CDN untuk Choices.js atau ingin menambahkan style khusus --}}
    {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css"/> --}}
@endpush

@section('content')
    {{-- Notifikasi untuk kegagalan import (Umumnya tidak untuk Operator) --}}
    @if (session('failures'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let failureMessages = '<ul class="text-start ps-3">';
                @foreach (session('failures') as $failure)
                    failureMessages +=
                        `<li class="mb-1">Baris {{ $failure->row() }}: {{ implode(', ', $failure->errors()) }}</li>`;
                @endforeach
                failureMessages += '</ul>';

                Swal.fire({
                    icon: 'error',
                    title: 'Import Gagal',
                    html: failureMessages,
                    showConfirmButton: true,
                    position: 'center',
                    customClass: {
                        htmlContainer: 'text-start'
                    }
                });
            });
        </script>
    @endif

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Daftar Jenis Barang (Lingkup Operasional Anda)</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('operator.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Daftar Jenis Barang</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Filter --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter & Pencarian Jenis Barang</h5>
            </div>
            <div class="card-body">
                {{-- Form action ke route general 'barang.index' karena controller yang sama menangani kedua role --}}
                <form method="GET" action="{{ route('barang.index') }}" id="filterFormJenisBarangOperator">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="id_kategori_filter_operator" class="form-label mb-1">Kategori</label>
                            <select name="id_kategori" id="id_kategori_filter_operator" class="form-control"
                                data-choices-removeItemButton="true"
                                onchange="document.getElementById('filterFormJenisBarangOperator').submit()">
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
                            <label for="id_ruangan_filter_operator" class="form-label mb-1">Ruangan (Unit)</label>
                            <select name="id_ruangan" id="id_ruangan_filter_operator" class="form-control"
                                data-choices-removeItemButton="true"
                                onchange="document.getElementById('filterFormJenisBarangOperator').submit()">
                                <option value="">-- Semua Ruangan Lingkup Anda --</option>
                                @foreach ($ruanganList as $ruanganItem)
                                    {{-- $ruanganList sudah difilter untuk operator di controller --}}
                                    <option value="{{ $ruanganItem->id }}"
                                        {{ ($ruanganId ?? '') == $ruanganItem->id ? 'selected' : '' }}>
                                        {{ $ruanganItem->nama_ruangan }} ({{ $ruanganItem->kode_ruangan }})
                                    </option>
                                @endforeach
                                <option value="tanpa-ruangan"
                                    {{ ($ruanganId ?? '') == 'tanpa-ruangan' ? 'selected' : '' }}>
                                    Tanpa Ruangan (Dipegang Personal/Baru)
                                </option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="search_filter_operator" class="form-label mb-1">Pencarian</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="search" id="search_filter_operator" class="form-control"
                                    placeholder="Nama, Kode, Merk/Model..." value="{{ $searchTerm ?? '' }}">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-md-2 d-grid">
                            <label for="btn_reset_filter_barang_operator" class="form-label mb-1">&nbsp;</label>
                            <a href="{{ route('barang.index') }}" id="btn_reset_filter_barang_operator"
                                class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-sync-alt me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tombol Aksi Global & Tambah --}}
        <div class="mb-3 d-flex justify-content-end align-items-center">
            <div class="d-flex align-items-center gap-2">
                @can('export', App\Models\BarangQrCode::class)
                    {{-- Operator boleh export data unit --}}
                    <a href="{{ route('barang-qr-code.export-excel', array_merge(request()->query(), ['search' => $searchTerm ?? null, 'id_ruangan' => $ruanganId ?? null, 'id_kategori' => $kategoriId ?? null])) }}"
                        class="btn btn-outline-success btn-sm">
                        <i class="mdi mdi-file-excel me-1"></i>Export Excel (Unit)
                    </a>
                    {{-- Jika ingin export PDF unit, bisa ditambahkan seperti di admin.barang.index --}}
                    {{-- <a href="{{ route('barang-qr-code.export-pdf', array_merge(request()->query(), ['search' => $searchTerm ?? null, 'id_ruangan' => $ruanganId ?? null, 'id_kategori' => $kategoriId ?? null, 'pisah_per_ruangan' => false])) }}"
                        class="btn btn-outline-danger btn-sm">
                        <i class="mdi mdi-file-pdf-box me-1"></i> Export PDF (Unit Semua)
                    </a>
                    <a href="{{ route('barang-qr-code.export-pdf', array_merge(request()->query(), ['search' => $searchTerm ?? null, 'id_ruangan' => $ruanganId ?? null, 'id_kategori' => $kategoriId ?? null, 'pisah_per_ruangan' => true])) }}"
                        class="btn btn-danger btn-sm">
                        <i class="mdi mdi-file-pdf-box me-1"></i> Export PDF (Unit Per Ruangan Anda)
                    </a> --}}
                @endcan

                {{-- Tombol Tambah Jenis Barang --}}
                {{-- Sesuai BarangPolicy, Operator diizinkan membuat Barang (Induk) --}}
                {{-- BarangController->create() juga sudah menghandle view path untuk operator --}}
                @can('create', App\Models\Barang::class)
                    <a href="{{ route('barang.create') }}" class="btn btn-success btn-sm">
                        <i class="mdi mdi-plus me-1"></i> Tambah Jenis Barang
                    </a>
                @endcan
            </div>
        </div>

        {{-- Card Tabel Data --}}
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="mdi mdi-format-list-bulleted me-2"></i>Data Jenis Barang</h4>
            </div>
            <div class="card-body">
                @if ($operatorTidakAdaRuangan ?? false)
                    <div class="alert alert-warning text-center" role="alert">
                        Anda adalah Operator dan saat ini tidak ditugaskan untuk mengelola ruangan manapun. <br> Tidak ada
                        jenis barang yang dapat ditampilkan sesuai lingkup Anda. Silakan hubungi Admin.
                    </div>
                @else
                    <div class="table-responsive">
                        <table id="barangTableOperator"
                            class="table table-hover table-striped dt-responsive align-middle nowrap w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama Barang</th>
                                    <th>Kode</th>
                                    <th>Kategori</th>
                                    <th>Merk/Model</th>
                                    <th>Tahun</th>
                                    <th class="text-center">Jml. Unit Aktif (Lingkup Anda)</th>
                                    <th class="text-end">Harga Induk (Rp)</th>
                                    <th>Sumber Induk</th>
                                    <th style="width: 80px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($barangs as $index => $item)
                                    <tr>
                                        <td>{{ $barangs->firstItem() + $index }}</td>
                                        <td>
                                            {{-- Menggunakan route general 'barang.show', BarangController akan menentukan view path berdasarkan role --}}
                                            <a href="{{ route('barang.show', $item->id) }}"
                                                class="fw-medium">{{ $item->nama_barang }}</a>
                                            <small class="d-block text-muted">
                                                {{ $item->menggunakan_nomor_seri ? 'Perlu No. Seri Unit' : 'Tidak Perlu No. Seri Unit' }}
                                            </small>
                                        </td>
                                        <td>{{ $item->kode_barang }}</td>
                                        <td>{{ $item->kategori->nama_kategori ?? '-' }}</td>
                                        <td>{{ $item->merk_model ?? '-' }}</td>
                                        <td>{{ $item->tahun_pembuatan ?? '-' }}</td>
                                        <td class="text-center">{{ $item->active_qr_codes_count }}</td>
                                        <td class="text-end">
                                            {{ $item->harga_perolehan_induk ? number_format($item->harga_perolehan_induk, 0, ',', '.') : '-' }}
                                        </td>
                                        <td>{{ $item->sumber_perolehan_induk ?? '-' }}</td>
                                        <td>
                                            <div class="d-flex gap-1 justify-content-center">
                                                {{-- Operator bisa view jika diizinkan oleh BarangPolicy@view --}}
                                                @can('view', $item)
                                                    <a href="{{ route('barang.show', $item->id) }}"
                                                        class="btn btn-outline-info btn-sm" title="Lihat Detail & Unit">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endcan
                                                {{-- Operator tidak diizinkan edit/delete Jenis Barang (master) berdasarkan BarangPolicy --}}
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            @if ($operatorTidakAdaRuangan ?? false)
                                                <i class="fas fa-folder-open fs-3 text-muted mb-2"></i><br>
                                                Anda tidak memiliki akses ke jenis barang manapun karena tidak ada ruangan
                                                yang
                                                dikelola.
                                            @else
                                                <i class="fas fa-folder-open fs-3 text-muted mb-2"></i><br>
                                                Jenis barang tidak ditemukan. Silakan coba dengan filter lain atau tambahkan
                                                jenis barang baru.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{-- @if ($barangs->hasPages())
                        <div class="mt-3 d-flex justify-content-center">
                            {{ $barangs->appends(request()->query())->links() }}
                        </div>
                    @endif --}}
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Pastikan jQuery sudah di-load sebelum script ini, biasanya di layouts.app --}}
    {{-- Pastikan DataTables juga sudah di-load --}}
    {{-- <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script> --}} {{-- Load Choices.js jika belum global --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inisialisasi Choices.js untuk filter Kategori
            const kategoriFilterElOperator = document.getElementById('id_kategori_filter_operator');
            if (kategoriFilterElOperator) {
                new Choices(kategoriFilterElOperator, {
                    removeItemButton: true,
                    searchPlaceholderValue: "Cari kategori...",
                    allowHTML: true,
                    noResultsText: 'Tidak ada hasil ditemukan',
                    noChoicesText: 'Tidak ada pilihan untuk dipilih'
                });
            }

            // Inisialisasi Choices.js untuk filter Ruangan
            const ruanganFilterElOperator = document.getElementById('id_ruangan_filter_operator');
            if (ruanganFilterElOperator) {
                new Choices(ruanganFilterElOperator, {
                    removeItemButton: true,
                    searchPlaceholderValue: "Cari ruangan...",
                    allowHTML: true,
                    noResultsText: 'Tidak ada hasil ditemukan',
                    noChoicesText: 'Tidak ada pilihan untuk dipilih'
                });
            }

            // DataTable Initialization
            if ($.fn.DataTable.isDataTable('#barangTableOperator')) {
                $('#barangTableOperator').DataTable().destroy();
            }
            // Periksa apakah tabel ada dan memiliki baris data (bukan hanya header atau pesan kosong)
            if ($('#barangTableOperator tbody tr').length > 0 && !$(
                    '#barangTableOperator tbody tr td[colspan="10"]').length) {
                $('#barangTableOperator').DataTable({
                    responsive: true,
                    dom: 'lrtip', // Menampilkan Length menu, Table, Info, Paging. Search box global DataTable disembunyikan.
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json',
                        lengthMenu: "Tampilkan _MENU_ entri per halaman",
                        zeroRecords: "Tidak ada data yang cocok",
                        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                        infoEmpty: "Tidak ada entri tersedia",
                        infoFiltered: "(difilter dari _MAX_ total entri)"
                    },
                    order: [1, 'asc'], // Default sort by Nama Barang (kolom ke-2)
                    // paging: false, // Paging dikendalikan oleh Laravel Paginator
                    // info: false, // Info jumlah entri juga dikendalikan oleh Laravel Paginator
                    // searching: false // Pencarian global DataTable dimatikan, karena sudah ada filter custom di atas tabel
                });
            }
            // Tidak ada logika JavaScript untuk tombol hapus jenis barang karena Operator tidak memiliki hak ini
        });
    </script>
@endpush
