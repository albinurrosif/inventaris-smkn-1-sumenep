@extends('layouts.app')

@section('title', 'Manajemen Data Ruangan')

@php
    $rolePrefix = Auth::user()->getRolePrefix();
@endphp

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        #tabelRuangan th,
        #tabelRuangan td {
            vertical-align: middle;
        }

        .table-danger-light td {
            background-color: #f8d7da !important;
            /* Warna yang lebih lembut untuk baris yang diarsipkan */
            color: #58151c;
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
                    <h4 class="mb-sm-0">Manajemen Data Ruangan</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Ruangan</li>

                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">

            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="card-title mb-0 flex-grow-1"><i class="fas fa-door-open me-2"></i>Data Ruangan</h5>
                @can('create', App\Models\Ruangan::class)
                    <button type="button" id="btnTambahRuangan" class="btn btn-primary btn-md" data-bs-toggle="modal"
                        data-bs-target="#modalTambahRuangan" title="Tambah ruangan baru">
                        <i class="mdi mdi-plus me-1"></i> Tambah Ruangan
                    </button>
                @endcan
            </div>

            <div class="card-body">

                @if ($errors->storeRuanganErrors->any() && old('form_type') === 'create')
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const modalTambahRuangan = new bootstrap.Modal(document.getElementById('modalTambahRuangan'));
                            modalTambahRuangan.show();
                        });
                    </script>
                @endif
                @if ($errors->updateRuanganErrors->any() && old('form_type') === 'edit' && session('error_ruangan_id'))
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const ruanganIdOnError = '{{ session('error_ruangan_id') }}';
                            if (ruanganIdOnError) {
                                const editButton = document.querySelector(`.btn-edit-ruangan[data-id="${ruanganIdOnError}"]`);
                                if (editButton) {
                                    // Swal.fire({
                                    //     icon: 'error',
                                    //     title: 'Validasi Gagal',
                                    //     html: 'Periksa kembali data yang Anda masukkan pada form edit.',
                                    //     showConfirmButton: true
                                    // });
                                    editButton.click();
                                }
                            } else {
                                // Swal.fire({
                                //     icon: 'error',
                                //     title: 'Validasi Gagal',
                                //     html: 'Periksa kembali data yang Anda masukkan.',
                                //     showConfirmButton: true
                                // });
                                const modalEditRuangan = new bootstrap.Modal(document.getElementById('modalEditRuangan'));
                                modalEditRuangan.show();

                            }
                        });
                    </script>
                @endif

                {{-- Filter Form --}}
                <form method="GET" action="{{ route($rolePrefix . 'ruangan.index') }}" class="mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label for="search_ruangan" class="form-label">Pencarian</label>
                            <input type="text" name="search" id="search_ruangan" class="form-control form-control-sm"
                                placeholder="Cari nama atau kode ruangan..." value="{{ $searchTerm ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label for="status_filter_ruangan" class="form-label">Status</label>
                            <select name="status" id="status_filter_ruangan" class="form-select form-select-sm">
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
                                Filter</button>
                        </div>
                        @if ($searchTerm || ($statusFilter != 'aktif' && $statusFilter != null))
                            <div class="col-md-2">
                                <a href="{{ route($rolePrefix . 'ruangan.index') }}"
                                    class="btn btn-outline-secondary btn-sm w-100" title="Reset Filter">
                                    <i class="fas fa-times"></i> Reset
                                </a>
                            </div>
                        @endif
                    </div>
                </form>
                @if ($request->search && isset($ruangans) && $ruangans->count() > 0 && $statusFilter == 'aktif')
                    <div class="alert alert-info py-2">
                        Menampilkan hasil pencarian untuk: <strong>"{{ $request->search }}"</strong> pada ruangan aktif.
                        <a href="{{ route($rolePrefix . 'ruangan.index') }}" class="btn-link ms-2">Tampilkan Semua Aktif</a>
                    </div>
                @endif

                <div class="table-responsive">
                    <table id="tabelRuangan" class="table table-bordered dt-responsive nowrap w-100 table-hover">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 5%;">No</th>
                                <th>Kode Ruangan</th>
                                <th>Nama Ruangan</th>
                                <th>Operator</th>
                                <th class="text-center">Jumlah Unit Barang</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 10%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ruangans as $index => $ruangan)
                                <tr class="{{ $ruangan->trashed() ? 'table-danger-light' : '' }}">
                                    <td class="text-center">{{ $ruangans->firstItem() + $index }}</td>
                                    <td>
                                        <span class="badge bg-dark">{{ $ruangan->kode_ruangan }}</span>
                                    </td>
                                    <td>
                                        @can('view', $ruangan)
                                            <a href="{{ route($rolePrefix . 'ruangan.show', $ruangan->id) }}"
                                                class="fw-medium">
                                                {{ $ruangan->nama_ruangan }}
                                            </a>
                                        @else
                                            {{ $ruangan->nama_ruangan }}
                                        @endcan
                                        @if ($ruangan->trashed())
                                            <small class="d-block text-danger fst-italic">(Diarsipkan pada:
                                                {{ $ruangan->deleted_at->isoFormat('DD MMM YY, HH:mm') }})</small>
                                        @endif
                                    </td>
                                    <td>{{ $ruangan->operator->username ?? '-' }}</td>
                                    <td class="text-center">
                                        <span
                                            class="badge bg-info">{{ $ruangan->barang_qr_codes_count ?? $ruangan->barangQrCodes->count() }}
                                            unit</span>
                                    </td>
                                    <td class="text-center">
                                        @if ($ruangan->trashed())
                                            <span class="badge bg-secondary">Diarsipkan</span>
                                        @else
                                            <span class="badge bg-success">Aktif</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            @if ($ruangan->trashed())
                                                @can('restore', $ruangan)
                                                    <form action="{{ route($rolePrefix . 'ruangan.restore', $ruangan->id) }}"
                                                        method="POST" class="d-inline form-restore-ruangan">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm btn-restore"
                                                            data-bs-toggle="tooltip"
                                                            title="Pulihkan {{ $ruangan->nama_ruangan }}">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                                {{-- @can('forceDelete', $ruangan)
                                                    <button type="button" class="btn btn-outline-danger btn-sm btn-force-delete-ruangan"
                                                        data-id="{{ $ruangan->id }}" data-nama="{{ $ruangan->nama_ruangan }}"
                                                        data-bs-toggle="tooltip" title="Hapus Permanen">
                                                        <i class="fas fa-skull-crossbones"></i>
                                                    </button>
                                                @endcan --}}
                                            @else
                                                @can('update', $ruangan)
                                                    <button type="button" class="btn btn-warning btn-sm btn-edit-ruangan"
                                                        data-ruangan='{!! json_encode($ruangan->only(['id', 'nama_ruangan', 'kode_ruangan', 'id_operator'])) !!}' data-id="{{ $ruangan->id }}"
                                                        {{-- Tambahkan data-id untuk JS --}} data-bs-toggle="modal"
                                                        data-bs-target="#modalEditRuangan"
                                                        title="Edit {{ $ruangan->nama_ruangan }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                @endcan
                                                @can('delete', $ruangan)
                                                    <button type="button" class="btn btn-danger btn-sm btn-delete-ruangan"
                                                        data-id="{{ $ruangan->id }}"
                                                        data-nama="{{ $ruangan->nama_ruangan }}"
                                                        data-item-count="{{ $ruangan->barang_qr_codes_count ?? 0 }}"
                                                        data-bs-toggle="tooltip" title="Hapus {{ $ruangan->nama_ruangan }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">
                                        @if ($request->search || $statusFilter === 'arsip')
                                            Tidak ada ruangan yang cocok dengan kriteria filter Anda.
                                        @else
                                            Belum ada data ruangan. Silakan tambahkan ruangan baru.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- @if ($ruangans instanceof \Illuminate\Pagination\LengthAwarePaginator && $ruangans->hasPages())
                    <div class="mt-3 d-flex justify-content-end">
                        {{ $ruangans->appends(request()->query())->links() }}
                    </div>
                @endif --}}
            </div>
        </div>
    </div>

    @can('create', App\Models\Ruangan::class)
        @include('pages.ruangan.partials.modal-create', ['operators' => $operators])
    @endcan
    @if ($ruangans->first(fn($r) => Gate::allows('update', $r) && !$r->trashed()))
        {{-- Modal edit hanya untuk yg bisa diupdate dan tidak trashed --}}
        @include('pages.ruangan.partials.modal-edit', ['operators' => $operators])
    @endif

    <form id="formDeleteRuangan" method="POST" style="display: none;">@csrf @method('DELETE')</form>
    {{-- <form id="formForceDeleteRuangan" method="POST" style="display: none;">@csrf @method('DELETE')</form> --}}

@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if ($.fn.DataTable.isDataTable('#tabelRuangan')) {
                $('#tabelRuangan').DataTable().destroy();
            }
            if ($('#tabelRuangan tbody tr').length > 0 && !$('#tabelRuangan tbody tr td[colspan="7"]').length) {
                $('#tabelRuangan').DataTable({
                    responsive: true,
                    paging: true,
                    searching: false,
                    info: true,
                    ordering: true,
                    order: [
                        [2, 'asc']
                    ],
                    columnDefs: [{
                            targets: [0, 6],
                            orderable: false,
                            searchable: false
                        },
                        {
                            targets: [4, 5],
                            className: 'text-center'
                        }
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

            const modalTambahRuanganElement = document.getElementById('modalTambahRuangan');
            if (modalTambahRuanganElement) {
                const modalTambah = new bootstrap.Modal(modalTambahRuanganElement);
                const btnTambahRuangan = document.getElementById('btnTambahRuangan');
                if (btnTambahRuangan) {
                    btnTambahRuangan.addEventListener('click', () => {
                        const formTambah = modalTambahRuanganElement.querySelector('form');
                        if (formTambah) {
                            formTambah.reset();
                            formTambah.action = "{{ route($rolePrefix . 'ruangan.store') }}";
                        }
                        formTambah.querySelectorAll('.is-invalid').forEach(el => el.classList.remove(
                            'is-invalid'));
                        formTambah.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
                        modalTambah.show();
                    });
                }
                @if ($errors->storeRuanganErrors->any() && old('form_type') === 'create')
                    modalTambah.show();
                @endif
            }

            const modalEditRuanganElement = document.getElementById('modalEditRuangan');
            if (modalEditRuanganElement) {
                const modalEdit = new bootstrap.Modal(modalEditRuanganElement);
                document.querySelectorAll('.btn-edit-ruangan').forEach(button => {
                    button.addEventListener('click', () => {
                        const data = JSON.parse(button.dataset.ruangan);
                        const form = modalEditRuanganElement.querySelector(
                            '#formEditRuanganAction');
                        const titleSpan = modalEditRuanganElement.querySelector(
                            '#editNamaRuanganTitleModal');

                        if (form) {
                            form.action =
                                `{{ route($rolePrefix . 'ruangan.update', ['ruangan' => ':id']) }}`
                                .replace(':id', data.id);
                            form.querySelector('#edit_modal_nama_ruangan').value = data
                                .nama_ruangan || '';
                            form.querySelector('#edit_modal_kode_ruangan').value = data
                                .kode_ruangan || '';
                            form.querySelector('#edit_modal_id_operator').value = data
                                .id_operator || '';
                        }
                        if (titleSpan) {
                            titleSpan.textContent = data.nama_ruangan || '';
                        } else {
                            modalEditRuanganElement.querySelector('.modal-title').textContent =
                                'Edit Ruangan: ' + (data.nama_ruangan || '');
                        }
                        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove(
                            'is-invalid'));
                        form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent =
                            '');
                        modalEdit.show();
                    });
                });
                @if ($errors->updateRuanganErrors->any() && old('form_type') === 'edit' && session('error_ruangan_id'))
                    const editButtonForError = document.querySelector(
                        `.btn-edit-ruangan[data-id="{{ session('error_ruangan_id') }}"]`);
                    if (editButtonForError) {
                        modalEdit.show();
                    }
                @endif
            }

            document.querySelectorAll('.btn-delete-ruangan').forEach(button => {
                button.addEventListener('click', function() {
                    const ruanganId = this.getAttribute('data-id');
                    const ruanganNama = this.getAttribute('data-nama');
                    const itemCount = parseInt(this.getAttribute('data-item-count')) || 0;
                    const formDelete = document.getElementById('formDeleteRuangan');

                    let textWarning =
                        `Anda yakin ingin mengarsipkan ruangan <strong>"${ruanganNama}"</strong>?`;
                    if (itemCount > 0) {
                        Swal.fire({
                            title: 'Tidak Dapat Mengarsipkan',
                            html: `Ruangan <strong>"${ruanganNama}"</strong> tidak dapat diarsipkan karena masih memiliki <strong>${itemCount} unit barang</strong> aktif. Harap pindahkan atau arsipkan unit barang tersebut terlebih dahulu.`,
                            icon: 'error',
                            confirmButtonText: 'Mengerti'
                        });
                        return;
                    }
                    textWarning +=
                        `<br><br>Tindakan ini akan memindahkan ruangan ke arsip dan dapat dipulihkan nanti. Lanjutkan?`;

                    Swal.fire({
                        title: 'Konfirmasi Arsipkan Ruangan',
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
                                    `{{ route($rolePrefix . 'ruangan.destroy', ['ruangan' => ':id']) }}`
                                    .replace(':id', ruanganId);
                                formDelete.submit();
                            }
                        }
                    });
                });
            });

            // Handle Restore Confirmation
            document.querySelectorAll('.form-restore-ruangan').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const ruanganNama = this.closest('tr').querySelector(
                        'a.fw-medium, td:nth-child(3)').textContent.trim();
                    Swal.fire({
                        title: 'Konfirmasi Pulihkan Ruangan',
                        html: `Anda yakin ingin memulihkan ruangan <strong>"${ruanganNama}"</strong>?`,
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
