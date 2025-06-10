@extends('layouts.app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('title', 'Daftar Unit Barang (QR Code)')

@push('styles')
    <style>
        /* Custom styles untuk Choices.js agar konsisten dengan ukuran form-select-sm */
        .choices__inner {
            min-height: calc(1.5em + .5rem + 2px);
            /* Mirip form-select-sm */
            padding: .25rem .5rem;
            font-size: .875rem;
            line-height: 1.5;
        }

        .choices[data-type*="select-one"] .choices__inner {
            padding-bottom: .25rem;
        }

        .choices__list--dropdown .choices__item--selectable,
        .choices__list--dropdown .choices__item--choice {
            font-size: .875rem;
            padding: .35rem .75rem;
        }

        .choices__input {
            font-size: .875rem;
        }

        .badge {
            font-size: 0.8em;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .page-item.active .page-link {
            background-color: #556ee6;
            /* Warna primary Minia */
            border-color: #556ee6;
        }

        .page-link {
            color: #556ee6;
        }

        .page-link:hover {
            color: #485ec4;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        {{-- Page Title & Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Daftar Unit Barang (QR Code)</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Unit Barang (QR)</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Filter & Pencarian --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter & Pencarian Unit Barang</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.barang-qr-code.index') }}" method="GET" id="filterFormUnitQr">
                    <div class="row g-3 align-items-end">
                        <!-- Ruangan Filter -->
                        <div class="col-md-3">
                            <label for="id_ruangan_filter_qr" class="form-label mb-1">Ruangan</label>
                            <select name="id_ruangan" id="id_ruangan_filter_qr"
                                class="form-select form-select-sm select2-basic" onchange="this.form.submit()">
                                <option value="">-- Semua Ruangan --</option>
                                @foreach ($ruanganList as $ruangan)
                                    <option value="{{ $ruangan->id }}"
                                        {{ ($request->id_ruangan ?? '') == $ruangan->id ? 'selected' : '' }}>
                                        {{ $ruangan->nama_ruangan }}
                                    </option>
                                @endforeach
                                <option value="tanpa-ruangan"
                                    {{ ($request->id_ruangan ?? '') == 'tanpa-ruangan' ? 'selected' : '' }}>
                                    Tanpa Ruangan/Personal
                                </option>
                            </select>
                        </div>

                        <!-- Jenis Barang Filter -->
                        <div class="col-md-3">
                            <label for="id_barang_filter_qr" class="form-label mb-1">Jenis Barang Induk</label>
                            <select name="id_barang" id="id_barang_filter_qr"
                                class="form-select form-select-sm select2-basic" onchange="this.form.submit()">
                                <option value="">-- Semua Jenis Barang --</option>
                                @foreach ($barangList as $barang)
                                    <option value="{{ $barang->id }}"
                                        {{ ($request->id_barang ?? '') == $barang->id ? 'selected' : '' }}>
                                        {{ $barang->nama_barang }} ({{ $barang->kode_barang }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Kondisi Filter -->
                        <div class="col-md-3">
                            <label for="kondisi_filter_qr" class="form-label mb-1">Kondisi</label>
                            <select name="kondisi" id="kondisi_filter_qr" class="form-select form-select-sm select2-basic"
                                onchange="this.form.submit()">
                                <option value="">-- Semua Kondisi --</option>
                                @foreach ($kondisiOptions as $kondisi)
                                    <option value="{{ $kondisi }}"
                                        {{ ($request->kondisi ?? '') == $kondisi ? 'selected' : '' }}>
                                        {{ $kondisi }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-md-3">
                            <label for="status_filter_qr" class="form-label mb-1">Status</label>
                            <select name="status" id="status_filter_qr" class="form-select form-select-sm select2-basic"
                                onchange="this.form.submit()">
                                <option value="">-- Semua Status --</option>
                                @foreach ($statusOptions as $status)
                                    <option value="{{ $status }}"
                                        {{ ($request->status ?? '') == $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Search Input -->
                        <div class="col-md-3">
                            <label for="search_input_qr" class="form-label mb-1">Cari Unit</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="search_input_qr" name="search"
                                    placeholder="Kode Unit, No Seri, Nama Barang..." value="{{ $request->search ?? '' }}">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Reset Button -->
                        <div class="col-md-2 d-flex align-items-end">
                            <a href="{{ route('admin.barang-qr-code.index') }}"
                                class="btn btn-outline-secondary btn-sm w-100">
                                <i class="fas fa-undo me-1"></i> Reset Filter
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tombol Aksi Global & Tambah --}}
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <!-- Left Side Buttons -->
            <div class="d-flex align-items-center gap-2 start-0">
                @can('printQr', App\Models\BarangQrCode::class)
                    <button type="button" class="btn btn-outline-info btn-sm" id="btnPrintSelectedQr" data-bs-toggle="tooltip"
                        title="Pilih QR Code dihalaman ini untuk dicetak" disabled>
                        <i class="mdi mdi-printer me-1"></i> Cetak QR Terpilih
                    </button>
                @endcan
            </div>

            <!-- Right Side Buttons -->
            <div class="d-flex align-items-center gap-2">
                @can('export', App\Models\BarangQrCode::class)
                    <a href="{{ route('admin.barang-qr-code.export-excel', request()->query()) }}"
                        class="btn btn-outline-success btn-sm" data-bs-toggle="tooltip" title="Export Excel">
                        <i class="mdi mdi-file-excel me-1"></i> Export Excel
                    </a>
                    <a href="{{ route('admin.barang-qr-code.export-pdf', array_merge(request()->query(), ['pisah_per_ruangan' => false])) }}"
                        class="btn btn-outline-danger btn-sm" data-bs-toggle="tooltip" title="Export PDF semua unit">
                        <i class="mdi mdi-file-pdf-box me-1"></i> Export PDF (Semua)
                    </a>
                    <a href="{{ route('admin.barang-qr-code.export-pdf', array_merge(request()->query(), ['pisah_per_ruangan' => true])) }}"
                        class="btn btn-danger btn-sm" data-bs-toggle="tooltip" title="Export PDF per Ruangan">
                        <i class="mdi mdi-file-pdf-box me-1"></i> Export PDF (Per Ruangan)
                    </a>
                @endcan
                @can('create', App\Models\Barang::class)
                    <a href="{{ route('admin.barang.index') }}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip"
                        title="Kelola Jenis Barang">
                        <i class="mdi mdi-plus-circle-outline me-1"></i> Tambah Unit (via Jenis Barang)
                    </a>
                @endcan
            </div>
        </div>

        {{-- Card Daftar Unit Barang --}}
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="mdi mdi-qrcode-scan me-2"></i>Data Unit Barang Individual</h4>
            </div>
            <div class="card-body">
                <form id="formBatchAction" action="{{ route('admin.barang-qr-code.print-multiple') }}" method="POST"
                    target="_blank">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-hover table-striped dt-responsive align-middle nowrap w-100"
                            id="dataTableUnitQr">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 1%;" class="text-center">
                                        <input class="form-check-input" type="checkbox" id="checkAllUnits"
                                            title="Pilih Semua" data-bs-toggle="tooltip"
                                            style="
            border-box:5px solid rgba(0, 166, 255, 0.5);">
                                    </th>
                                    <th>Kode Inventaris</th>
                                    <th>Nama Barang (Jenis)</th>
                                    <th>No. Seri Pabrik</th>
                                    <th>Lokasi/Pemegang</th>
                                    <th class="text-center">Kondisi</th>
                                    <th class="text-center">Status</th>
                                    <th>Tgl. Perolehan</th>
                                    <th style="width: 80px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($qrCodes as $index => $unit)
                                    <tr>
                                        <td class="text-center">
                                            <input class="form-check-input unit-checkbox" type="checkbox"
                                                name="qr_code_ids[]" value="{{ $unit->id }}">
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.barang-qr-code.show', $unit->id) }}"
                                                class="fw-medium">
                                                {{ $unit->kode_inventaris_sekolah ?? 'N/A' }}
                                            </a>
                                            @if ($unit->arsip && $unit->arsip->status_arsip !== \App\Models\ArsipBarang::STATUS_ARSIP_DIPULIHKAN)
                                                <span class="badge bg-danger ms-1">Diarsip</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($unit->barang)
                                                <a
                                                    href="{{ route('admin.barang.show', $unit->barang->id) }}">{{ Str::limit($unit->barang->nama_barang, 30) }}</a>
                                                <br><small
                                                    class="text-muted">{{ $unit->barang->kategori?->nama_kategori ?? '-' }}</small>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>{{ $unit->no_seri_pabrik ?? '-' }}</td>
                                        <td>
                                            @if ($unit->id_pemegang_personal && $unit->pemegangPersonal)
                                                <span class="badge bg-primary text-wrap"><i class="fas fa-user me-1"></i>
                                                    {{ $unit->pemegangPersonal->username }}</span>
                                            @elseif($unit->id_ruangan && $unit->ruangan)
                                                <span class="badge bg-info text-dark text-wrap"><i
                                                        class="fas fa-map-marker-alt me-1"></i>
                                                    {{ $unit->ruangan->nama_ruangan }}</span>
                                            @else
                                                <span class="badge bg-secondary text-wrap">Belum Ditempatkan</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span @class([
                                                'badge',
                                                'text-wrap',
                                                'bg-success' => $unit->kondisi == \App\Models\BarangQrCode::KONDISI_BAIK,
                                                'bg-warning text-dark' =>
                                                    $unit->kondisi == \App\Models\BarangQrCode::KONDISI_KURANG_BAIK,
                                                'bg-danger' =>
                                                    $unit->kondisi == \App\Models\BarangQrCode::KONDISI_RUSAK_BERAT,
                                                'bg-dark' => $unit->kondisi == \App\Models\BarangQrCode::KONDISI_HILANG,
                                                'bg-secondary' => !in_array(
                                                    $unit->kondisi,
                                                    \App\Models\BarangQrCode::getValidKondisi()),
                                            ])>
                                                {{ $unit->kondisi }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span @class([
                                                'badge',
                                                'text-wrap',
                                                'bg-success' => $unit->status == \App\Models\BarangQrCode::STATUS_TERSEDIA,
                                                'bg-info text-dark' =>
                                                    $unit->status == \App\Models\BarangQrCode::STATUS_DIPINJAM,
                                                'bg-warning text-dark' =>
                                                    $unit->status == \App\Models\BarangQrCode::STATUS_DALAM_PEMELIHARAAN,
                                                'bg-secondary' => !in_array(
                                                    $unit->status,
                                                    \App\Models\BarangQrCode::getValidStatus()),
                                            ])>
                                                {{ $unit->status }}
                                            </span>
                                        </td>
                                        <td>{{ $unit->tanggal_perolehan_unit ? \Carbon\Carbon::parse($unit->tanggal_perolehan_unit)->isoFormat('DD MMM YY') : '-' }}
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 justify-content-center">
                                                @can('view', $unit)
                                                    <a href="{{ route('admin.barang-qr-code.show', $unit->id) }}"
                                                        class="btn btn-outline-primary btn-sm" title="Lihat Detail (KIB)">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-folder-open fs-3 text-muted mb-2"></i><br>
                                            Tidak ada data unit barang yang ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>

            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const choicesConfig = {
                removeItemButton: true,
                searchPlaceholderValue: "Ketik untuk mencari...",

                allowHTML: true,
                noResultsText: 'Tidak ada hasil ditemukan',
                noChoicesText: 'Tidak ada pilihan untuk dipilih',
                shouldSort: false, // Biarkan urutan dari server
            };

            const ruanganFilterQr = document.getElementById('id_ruangan_filter_qr');
            if (ruanganFilterQr) new Choices(ruanganFilterQr, choicesConfig);

            const barangFilterQr = document.getElementById('id_barang_filter_qr');
            if (barangFilterQr) new Choices(barangFilterQr, choicesConfig);

            const kondisiFilterQr = document.getElementById('kondisi_filter_qr');
            if (kondisiFilterQr) new Choices(kondisiFilterQr, {
                ...choicesConfig,
                searchEnabled: false,
                removeItemButton: false
            }); // Tanpa search untuk kondisi

            const statusFilterQr = document.getElementById('status_filter_qr');
            if (statusFilterQr) new Choices(statusFilterQr, {
                ...choicesConfig,
                searchEnabled: false,
                removeItemButton: false
            }); // Tanpa search untuk status

            // Checkbox logic
            $('#checkAllUnits').on('click', function() {
                $('.unit-checkbox').prop('checked', $(this).prop('checked')).trigger('change');
            });

            $('.unit-checkbox').on('change', function() {
                if ($('.unit-checkbox:checked').length === $('.unit-checkbox').length && $('.unit-checkbox')
                    .length > 0) {
                    $('#checkAllUnits').prop('checked', true);
                } else {
                    $('#checkAllUnits').prop('checked', false);
                }
                togglePrintButton();
            });

            function togglePrintButton() {
                $('#btnPrintSelectedQr').prop('disabled', $('.unit-checkbox:checked').length === 0);
            }
            togglePrintButton();

            $('#btnPrintSelectedQr').on('click', function() {
                if ($('.unit-checkbox:checked').length > 0) {
                    $('#formBatchAction').submit();
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Tidak Ada Unit Dipilih',
                        text: 'Pilih minimal satu unit barang untuk dicetak QR Code-nya.',
                        confirmButtonColor: '#556ee6' // Primary color Minia
                    });
                }
            });

            // DataTable Initialization (Minimal)
            if ($.fn.DataTable.isDataTable('#dataTableUnitQr')) {
                $('#dataTableUnitQr').DataTable().destroy();
            }
            // Inisialisasi hanya jika ada data dan bukan baris 'colspan' (tidak ada data)
            if ($('#dataTableUnitQr tbody tr').length > 0 && !$('#dataTableUnitQr tbody tr td[colspan="9"]')
                .length) {
                $('#dataTableUnitQr').DataTable({
                    responsive: true,
                    // dom: 'rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>', // Menyembunyikan LengthMenu
                    dom: 'lrtip', // Lebih minimalis: table, info, pagination. Length menu dihilangkan.
                    language: { // Opsi untuk melokalisasi DataTables ke Bahasa Indonesia
                        sEmptyTable: "Tidak ada data yang tersedia pada tabel ini",
                        sProcessing: "Sedang memproses...",
                        sLengthMenu: "Tampilkan _MENU_ entri",
                        sZeroRecords: "Tidak ditemukan data yang sesuai",
                        sInfo: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                        sInfoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
                        sInfoFiltered: "(disaring dari _MAX_ entri keseluruhan)",
                        sInfoPostFix: "",
                        sSearch: "Cari:",
                        sUrl: "",
                        oPaginate: {
                            sFirst: "Pertama",
                            sPrevious: "Sebelumnya",
                            sNext: "Selanjutnya",
                            sLast: "Terakhir"
                        }
                    },
                    order: [1, 'asc'], // Server-side ordering
                    // paging: false,    // Use Laravel's pagination
                    // info: false,      // Use Laravel's pagination info
                    // searching: false, // Use custom search form
                    // lengthChange: false // Menyembunyikan opsi "Show X entries"
                });
            }
        });
    </script>
@endpush
