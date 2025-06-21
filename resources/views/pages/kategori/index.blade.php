@extends('layouts.app')

@section('title', 'Manajemen Kategori Barang')

@php
    $rolePrefix = Auth::user()->getRolePrefix();
@endphp

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        #tabelKategori th,
        #tabelKategori td {
            vertical-align: middle;
        }

        .table-danger-light td {
            /* Lebih spesifik untuk menghindari konflik */
            background-color: #fdeeee !important;
        }

        .nav-tabs .nav-link.active {
            color: #495057;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
            font-weight: bold;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Manajemen Kategori Barang</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Kategori Barang</li>

                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="card-title mb-0 flex-grow-1"><i class="fas fa-tags me-2"></i>Data Kategori Barang</h5>
                @can('create', App\Models\KategoriBarang::class)
                    <button type="button" id="btnTambahKategori" class="btn btn-primary btn-md" data-bs-toggle="modal"
                        data-bs-target="#modalTambahKategori" title="Tambah kategori baru">
                        <i class="mdi mdi-plus me-1"></i> Tambah Kategori
                    </button>
                @endcan
            </div>

            <div class="card-body">

                @if (isset($errors) && $errors->storeKategoriErrors->any() && old('form_type') === 'create')
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const modalTambahKategori = new bootstrap.Modal(document.getElementById('modalTambahKategori'));
                            modalTambahKategori.show();
                        });
                    </script>
                @endif
                @if (isset($errors) &&
                        $errors->updateKategoriErrors->any() &&
                        old('form_type') === 'edit' &&
                        session('error_kategori_id'))
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const kategoriIdToEdit = '{{ session('error_kategori_id') }}';
                            if (kategoriIdToEdit) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Validasi Gagal',
                                    html: 'Periksa kembali data yang Anda masukkan pada form edit.',
                                    showConfirmButton: true
                                });
                            }
                        });
                    </script>
                @endif
                @if (session('failures'))
                    <script>
                        // Script SweetAlert untuk session('failures') sudah ada, tidak perlu diulang
                    </script>
                @endif

                <form method="GET" action="{{ route($rolePrefix . 'kategori-barang.index') }}" class="mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label for="search_kategori" class="form-label">Pencarian</label>
                            <input type="text" name="search" id="search_kategori" class="form-control form-control-sm"
                                placeholder="Cari nama kategori atau slug..." value="{{ $searchTerm ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label for="status_filter_kategori" class="form-label">Status</label>
                            <select name="status" id="status_filter_kategori" class="form-select form-select-sm"
                                onchange="this.form.submit()">
                                <option value="aktif" {{ ($statusFilter ?? 'aktif') == 'aktif' ? 'selected' : '' }}>Aktif
                                </option>
                                <option value="arsip" {{ ($statusFilter ?? '') == 'arsip' ? 'selected' : '' }}>Diarsipkan
                                </option>
                                <option value="semua" {{ ($statusFilter ?? '') == 'semua' ? 'selected' : '' }}>Semua
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary btn-sm w-100" type="submit"><i class="fas fa-filter"></i>
                                Filter/Cari</button>
                        </div>
                        @if ($searchTerm || ($statusFilter != 'aktif' && $statusFilter != null))
                            <div class="col-md-2">
                                <a href="{{ route($rolePrefix . 'kategori-barang.index') }}"
                                    class="btn btn-outline-secondary btn-sm w-100" title="Reset Filter">
                                    <i class="fas fa-times"></i> Reset
                                </a>
                            </div>
                        @endif
                    </div>
                </form>
                @if ($searchTerm && isset($kategoriBarangList) && $kategoriBarangList->count() > 0 && $statusFilter == 'aktif')
                    <div class="alert alert-info py-2">
                        Menampilkan hasil pencarian untuk: <strong>"{{ $searchTerm }}"</strong> pada kategori aktif.
                        <a href="{{ route($rolePrefix . 'kategori-barang.index') }}" class="btn-link ms-2">Tampilkan Semua
                            Aktif</a>
                    </div>
                @endif

                <div class="table-responsive">
                    <table id="tabelKategori" class="table table-bordered dt-responsive nowrap w-100 table-hover">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 5%;">No</th>
                                <th>Nama Kategori</th>
                                <th class="text-center">Jml. Jenis Barang (Induk)</th>
                                <th class="text-center">Total Unit Fisik</th>
                                <th class="text-center">Unit Tersedia</th>
                                <th class="text-end">Estimasi Nilai Total (Rp)</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 10%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($kategoriBarangList as $index => $kategori)
                                <tr class="{{ $kategori->trashed() ? 'table-danger-light' : '' }}">
                                    <td class="text-center">{{ $kategoriBarangList->firstItem() + $index }}</td>
                                    <td>
                                        @can('view', $kategori)
                                            <a href="{{ route($rolePrefix . 'kategori-barang.show', $kategori->id) }}"
                                                class="fw-medium">{{ $kategori->nama_kategori }}</a>
                                        @else
                                            {{ $kategori->nama_kategori }}
                                        @endcan
                                        @if ($kategori->trashed())
                                            <small class="d-block text-danger fst-italic">(Diarsipkan pada:
                                                {{ $kategori->deleted_at->isoFormat('DD MMM YY, HH:mm') }})</small>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $kategori->jumlah_item_induk ?? 0 }}</td>
                                    <td class="text-center">{{ $kategori->agregat_total_unit ?? 0 }}</td>
                                    <td class="text-center">{{ $kategori->agregat_unit_tersedia ?? 0 }}</td>
                                    <td class="text-end">
                                        {{ number_format($kategori->agregat_nilai_total ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if ($kategori->trashed())
                                            <span class="badge bg-secondary">Diarsipkan</span>
                                        @else
                                            <span class="badge bg-success">Aktif</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            @if ($kategori->trashed())
                                                @can('restore', $kategori)
                                                    <form
                                                        action="{{ route($rolePrefix . 'kategori-barang.restore', $kategori->id) }}"
                                                        method="POST" class="d-inline form-restore-kategori">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm btn-restore"
                                                            data-bs-toggle="tooltip"
                                                            title="Pulihkan {{ $kategori->nama_kategori }}">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            @else
                                                @can('update', $kategori)
                                                    <button type="button" class="btn btn-outline-warning btn-sm btn-edit-kategori"
                                                        data-kategori='@json($kategori->only(['id', 'nama_kategori']))'
                                                        data-id="{{ $kategori->id }}" data-bs-toggle="modal"
                                                        data-bs-target="#modalEditKategori"
                                                        title="Edit {{ $kategori->nama_kategori }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                @endcan
                                                @can('delete', $kategori)
                                                    <button type="button" class="btn btn-outline-danger btn-sm btn-delete-kategori"
                                                        data-id="{{ $kategori->id }}"
                                                        data-nama="{{ $kategori->nama_kategori }}"
                                                        data-item-count="{{ $kategori->jumlah_item_induk ?? 0 }}"
                                                        data-bs-toggle="tooltip"
                                                        title="Arsipkan {{ $kategori->nama_kategori }}">
                                                        <i class="fas fa-archive"></i>
                                                    </button>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">
                                        @if ($searchTerm || $statusFilter === 'arsip' || ($statusFilter === 'semua' && !$kategoriBarangList->count()))
                                            Tidak ada kategori barang yang cocok dengan kriteria filter Anda.
                                        @else
                                            Tidak ada data kategori barang. Silakan tambahkan kategori baru.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($kategoriBarangList instanceof \Illuminate\Pagination\LengthAwarePaginator && $kategoriBarangList->hasPages())
                    <div class="mt-3 d-flex justify-content-end">
                        {{ $kategoriBarangList->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @can('create', App\Models\KategoriBarang::class)
        @include('pages.kategori.partials.modal-create')
    @endcan
    @if ($kategoriBarangList->first(fn($k) => Gate::allows('update', $k) && !$k->trashed()))
        @include('pages.kategori.partials.modal-edit')
    @endif

    <form id="formDeleteKategori" method="POST" style="display: none;">@csrf @method('DELETE')</form>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if ($.fn.DataTable.isDataTable('#tabelKategori')) {
                $('#tabelKategori').DataTable().destroy();
            }
            if ($('#tabelKategori tbody tr').length > 0 && !$('#tabelKategori tbody tr td[colspan="8"]')
                .length) { // colspan disesuaikan
                $('#tabelKategori').DataTable({
                    responsive: true,
                    paging: false,
                    searching: false,
                    info: false,
                    ordering: true,
                    order: [
                        [1, 'asc']
                    ],
                    columnDefs: [{
                            targets: [0, 7],
                            orderable: false,
                            searchable: false
                        }, // Aksi di kolom ke-8 (index 7)
                        {
                            targets: [2, 3, 4, 6],
                            className: 'text-center'
                        }, // Jml, Total Unit, Unit Tersedia, Status
                        {
                            targets: [5],
                            className: 'text-end'
                        } // Estimasi Nilai
                    ],
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
                });
            }

            const modalTambahKategoriElement = document.getElementById('modalTambahKategori');
            if (modalTambahKategoriElement) {
                const modalTambah = new bootstrap.Modal(modalTambahKategoriElement);
                const btnTambahKategori = document.getElementById('btnTambahKategori');
                if (btnTambahKategori) {
                    btnTambahKategori.addEventListener('click', () => {
                        const formTambah = modalTambahKategoriElement.querySelector('form');
                        if (formTambah) {
                            formTambah.reset();
                            formTambah.action = "{{ route($rolePrefix . 'kategori-barang.store') }}";
                        }
                        formTambah.querySelectorAll('.is-invalid').forEach(el => el.classList.remove(
                            'is-invalid'));
                        formTambah.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
                        modalTambah.show();
                    });
                }
                @if (isset($errors) && $errors->storeKategoriErrors->any() && old('form_type') === 'create')
                    modalTambah.show();
                @endif
            }

            const modalEditKategoriElement = document.getElementById('modalEditKategori');
            if (modalEditKategoriElement) {
                const modalEdit = new bootstrap.Modal(modalEditKategoriElement);
                document.querySelectorAll('.btn-edit-kategori').forEach(button => {
                    button.addEventListener('click', () => {
                        const data = JSON.parse(button.dataset.kategori);
                        const form = modalEditKategoriElement.querySelector(
                            '#formEditKategoriAction');
                        const titleSpan = modalEditKategoriElement.querySelector(
                            '#editNamaKategoriTitleModal');
                        if (form) {
                            form.action =
                                `{{ route($rolePrefix . 'kategori-barang.update', ['kategori_barang' => ':id']) }}`
                                .replace(':id', data.id);
                            form.querySelector('#edit_modal_nama_kategori').value = data
                                .nama_kategori || '';
                            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove(
                                'is-invalid'));
                            form.querySelectorAll('.invalid-feedback').forEach(el => el
                                .textContent = '');
                        }
                        if (titleSpan) {
                            titleSpan.textContent = data.nama_kategori || '';
                        } else {
                            modalEditKategoriElement.querySelector('.modal-title').textContent =
                                'Edit Kategori: ' + (data.nama_kategori || '');
                        }
                        modalEdit.show();
                    });
                });
                @if (isset($errors) &&
                        $errors->updateKategoriErrors->any() &&
                        old('form_type') === 'edit' &&
                        session('error_kategori_id'))
                    const editButtonForError = document.querySelector(
                        `.btn-edit-kategori[data-id="{{ session('error_kategori_id') }}"]`);
                    if (editButtonForError) {
                        modalEdit.show();
                    }
                @endif
            }

            document.querySelectorAll('.btn-delete-kategori').forEach(button => {
                button.addEventListener('click', function() {
                    const kategoriId = this.getAttribute('data-id');
                    const kategoriNama = this.getAttribute('data-nama');
                    const itemCount = parseInt(this.getAttribute('data-item-count')) || 0;
                    const formDelete = document.getElementById('formDeleteKategori');

                    let textWarning =
                        `Anda yakin ingin mengarsipkan kategori <strong>"${kategoriNama}"</strong>?`;
                    if (itemCount > 0) {
                        textWarning +=
                            `<br><br><strong class="text-danger">PERHATIAN:</strong> Kategori ini masih memiliki <strong>${itemCount} jenis barang</strong> aktif yang terkait. Mengarsipkan kategori ini juga akan mengarsipkan jenis barang tersebut.`;
                    }
                    textWarning +=
                        `<br><br>Tindakan ini akan memindahkan kategori ke arsip dan dapat dipulihkan nanti. Lanjutkan?`;

                    Swal.fire({
                        title: 'Konfirmasi Arsipkan Kategori',
                        html: textWarning,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, Arsipkan!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            if (formDelete) {
                                formDelete.action =
                                    `{{ route($rolePrefix . 'kategori-barang.destroy', ['kategori_barang' => ':id']) }}`
                                    .replace(':id', kategoriId);
                                formDelete.submit();
                            }
                        }
                    });
                });
            });

            // Handle Restore Confirmation
            document.querySelectorAll('.form-restore-kategori').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const kategoriNama = this.closest('tr').querySelector(
                        'a.fw-medium, td:nth-child(2)').textContent.trim();
                    Swal.fire({
                        title: 'Konfirmasi Pulihkan Kategori',
                        html: `Anda yakin ingin memulihkan kategori <strong>"${kategoriNama}"</strong> beserta jenis barang terkait yang mungkin ikut terarsip?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Pulihkan!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endpush
