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
                <form action="{{ route($rolePrefix . 'barang-qr-code.index') }}" method="GET" id="filterFormUnitQr">
                    <div class="row g-3">

                        {{-- FILTER UTAMA BARU --}}
                        <div class="col-md-3">
                            <label for="filter_utama" class="form-label">Tampilkan</label>
                            <select class="form-select" name="filter_utama" id="filter_utama" onchange="this.form.submit()">
                                @foreach ($filterOptions as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ $request->input('filter_utama', 'aktif') == $key ? 'selected' : '' }}>
                                        {{ $value }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="id_ruangan_filter_qr" class="form-label">Ruangan</label>
                            <select name="id_ruangan" id="id_ruangan_filter_qr" class="form-select form-select-sm">
                                <option value="">-- Semua Ruangan --</option>

                                {{-- Loop ini sekarang akan menerima daftar ruangan yang sudah difilter untuk Operator --}}
                                @foreach ($ruanganList as $ruangan)
                                    <option value="{{ $ruangan->id }}" @selected(request('id_ruangan') == $ruangan->id)>
                                        {{ $ruangan->nama_ruangan }}
                                    </option>
                                @endforeach

                                {{-- Opsi ini tetap ada untuk kasus khusus --}}
                                <option value="tanpa-ruangan" @selected(request('id_ruangan') == 'tanpa-ruangan')>
                                    Tanpa Ruangan/Personal
                                </option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="id_barang_filter_qr" class="form-label">Jenis Barang Induk</label>
                            <select name="id_barang" id="id_barang_filter_qr" class="form-select form-select-sm">
                                <option value="">-- Semua Jenis Barang --</option>
                                @foreach ($barangList as $barang)
                                    <option value="{{ $barang->id }}" @selected(request('id_barang') == $barang->id)>
                                        {{ $barang->nama_barang }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="search_input_qr" class="form-label">Cari Kode/Seri</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" name="search"
                                    placeholder="Kode, No Seri, Nama..." value="{{ request('search') }}">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                                <a href="{{ route($rolePrefix . 'barang-qr-code.index') }}"
                                    class="btn btn-outline-secondary" title="Reset Filter"><i class="fas fa-undo"></i></a>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        {{-- Tombol Aksi Global & Tambah --}}
        <div class="mb-3 d-flex justify-content-between align-items-center">
            {{-- Tombol kiri --}}
            <div class="d-flex align-items-center gap-2 start-0">
                @can('printQr', App\Models\BarangQrCode::class)
                    <button type="button" class="btn btn-outline-info btn-sm" id="btnPrintSelectedQr" data-bs-toggle="tooltip"
                        title="Pilih QR Code dihalaman ini untuk dicetak" disabled>
                        <i class="mdi mdi-printer me-1"></i> Cetak QR Terpilih
                    </button>
                @endcan
            </div>

            {{-- Tombol kanan --}}
            <div class="d-flex align-items-center gap-2">
                {{-- @can('export', App\Models\BarangQrCode::class)
                    <a href="{{ route($rolePrefix . 'barang-qr-code.export-excel', request()->query()) }}"
                        class="btn btn-outline-success btn-sm" data-bs-toggle="tooltip" title="Export Excel">
                        <i class="mdi mdi-file-excel me-1"></i> Export Excel
                    </a>
                    <a href="{{ route($rolePrefix . 'barang-qr-code.export-pdf', array_merge(request()->query(), ['pisah_per_ruangan' => false])) }}"
                        class="btn btn-outline-danger btn-sm" data-bs-toggle="tooltip" title="Export PDF semua unit">
                        <i class="mdi mdi-file-pdf-box me-1"></i> Export PDF (Semua)
                    </a>
                    <a href="{{ route($rolePrefix . 'barang-qr-code.export-pdf', array_merge(request()->query(), ['pisah_per_ruangan' => true])) }}"
                        class="btn btn-danger btn-sm" data-bs-toggle="tooltip" title="Export PDF per Ruangan">
                        <i class="mdi mdi-file-pdf-box me-1"></i> Export PDF (Per Ruangan)
                    </a>
                @endcan --}}
                @can('create', App\Models\Barang::class)
                    <a href="{{ route($rolePrefix . 'barang.index') }}" class="btn btn-primary btn-sm" data-bs-toggle="tooltip"
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
                <form id="formBatchAction" action="{{ route($rolePrefix . 'barang-qr-code.print-multiple') }}"
                    method="POST" target="_blank">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-hover table-striped dt-responsive align-middle nowrap w-100"
                            id="dataTableUnitQr">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 1%;"><input class="form-check-input" type="checkbox"
                                            id="checkAllUnits"></th>
                                    <th>Kode Inventaris</th>
                                    <th>Nama Barang</th>
                                    <th>No. Seri Pabrik</th> {{-- <-- DIKEMBALIKAN --}}
                                    <th>Lokasi/Pemegang</th>
                                    <th class="text-center">Kondisi</th>
                                    <th class="text-center">Status Unit</th>
                                    <th>Tgl. Perolehan</th> {{-- <-- DIKEMBALIKAN --}}
                                    <th>Catatan Arsip</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($qrCodes as $unit)
                                    <tr class="{{ $unit->trashed() ? 'table-light text-muted' : '' }}">
                                        <td class="text-center"><input class="form-check-input unit-checkbox"
                                                type="checkbox" name="qr_code_ids[]" value="{{ $unit->id }}"></td>
                                        <td>
                                            <a href="{{ route($rolePrefix . 'barang-qr-code.show', $unit->id) }}"
                                                class="fw-medium">{{ $unit->kode_inventaris_sekolah ?? 'N/A' }}</a>
                                        </td>
                                        <td>
                                            @if ($unit->barang)
                                                <a
                                                    href="{{ route($rolePrefix . 'barang.show', $unit->barang->id) }}">{{ Str::limit($unit->barang->nama_barang, 30) }}</a>
                                                <br><small>{{ $unit->barang->merk_model ?? '' }}</small>
                                            @else
                                                <span class="text-danger">Induk Barang Hilang</span>
                                            @endif
                                        </td>
                                        {{-- PENYESUAIAN: Menampilkan kembali data No. Seri Pabrik --}}
                                        <td>{{ $unit->no_seri_pabrik ?? '-' }}</td>
                                        <td>
                                            @if ($unit->trashed())
                                                <span class="badge bg-secondary">Tidak Ditempatkan</span>
                                            @elseif ($unit->pemegangPersonal)
                                                <span class="badge bg-primary text-wrap"><i class="fas fa-user me-1"></i>
                                                    {{ $unit->pemegangPersonal->username }}</span>
                                            @elseif ($unit->ruangan)
                                                <span class="badge bg-info text-dark text-wrap"><i
                                                        class="fas fa-map-marker-alt me-1"></i>
                                                    {{ $unit->ruangan->nama_ruangan }}</span>
                                            @else
                                                <span class="badge bg-secondary text-wrap">N/A</span>
                                            @endif
                                        </td>
                                        <td class="text-center"><span
                                                class="badge {{ \App\Models\BarangQrCode::getKondisiColor($unit->kondisi) }}">{{ $unit->kondisi }}</span>
                                        </td>
                                        <td class="text-center">

                                            <span
                                                class="badge {{ \App\Models\BarangQrCode::getStatusColor($unit->status) }}">{{ $unit->status }}</span>

                                        </td>

                                        {{-- PENYESUAIAN: Menampilkan kembali Tanggal Perolehan --}}
                                        <td>{{ $unit->tanggal_perolehan_unit ? \Carbon\Carbon::parse($unit->tanggal_perolehan_unit)->isoFormat('DD MMM YYYY') : '-' }}
                                        </td>

                                        <td>
                                            @if ($unit->trashed() && $unit->arsip)
                                                <span class="badge bg-danger"
                                                    title="{{ $unit->arsip->alasan_penghapusan }}">{{ $unit->arsip->jenis_penghapusan }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route($rolePrefix . 'barang-qr-code.show', $unit->id) }}"
                                                class="btn btn-outline-primary btn-sm" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">Tidak ada data yang ditemukan.</td>
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
