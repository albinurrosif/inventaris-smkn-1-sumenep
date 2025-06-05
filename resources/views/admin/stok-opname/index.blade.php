@php
    // Menggunakan konstanta peran dari model User
    use App\Models\User;
    use App\Models\StokOpname; // Untuk konstanta status
@endphp

@extends('layouts.app')

@section('title', 'Manajemen Stok Opname')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        #tabelStokOpname th,
        #tabelStokOpname td {
            vertical-align: middle;
            font-size: 0.85rem;
        }

        .table-danger-light td {
            background-color: #fdeeee !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Manajemen Stok Opname</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Stok Opname</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="card-title mb-0 flex-grow-1"><i class="fas fa-clipboard-check me-2"></i>Data Sesi Stok Opname
                </h5>
                @can('create', App\Models\StokOpname::class)
                    <a href="{{ route('admin.stok-opname.create') }}" class="btn btn-primary btn-md"
                        title="Buat Sesi Stok Opname Baru">
                        <i class="mdi mdi-plus me-1"></i> Buat Sesi SO
                    </a>
                @endcan
            </div>

            <div class="card-body">

                <form method="GET" action="{{ route('admin.stok-opname.index') }}" class="mb-4">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label for="search_so" class="form-label">Pencarian</label>
                            <input type="text" name="search" id="search_so" class="form-control form-control-sm"
                                placeholder="Catatan, nama/kode ruangan..." value="{{ $request->search ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label for="id_ruangan_filter_so" class="form-label">Ruangan</label>
                            <select name="id_ruangan" id="id_ruangan_filter_so"
                                class="form-select form-select-sm select2-filter">
                                <option value="">-- Semua Ruangan --</option>
                                @foreach ($ruanganList as $ruangan)
                                    <option value="{{ $ruangan->id }}"
                                        {{ ($request->id_ruangan ?? '') == $ruangan->id ? 'selected' : '' }}>
                                        {{ $ruangan->nama_ruangan }} ({{ $ruangan->kode_ruangan }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if (Auth::user()->hasRole(App\Models\User::ROLE_ADMIN))
                            <div class="col-md-2">
                                <label for="id_operator_filter_so" class="form-label">Operator</label>
                                <select name="id_operator" id="id_operator_filter_so"
                                    class="form-select form-select-sm select2-filter">
                                    <option value="">-- Semua Operator --</option>
                                    @foreach ($operatorList as $operator)
                                        <option value="{{ $operator->id }}"
                                            {{ ($request->id_operator ?? '') == $operator->id ? 'selected' : '' }}>
                                            {{ $operator->username }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-2">
                            <label for="status_filter_so" class="form-label">Status Sesi</label>
                            <select name="status" id="status_filter_so" class="form-select form-select-sm">
                                <option value="">-- Semua Status --</option>
                                @foreach ($statusList as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ ($request->status ?? '') == $key ? 'selected' : '' }}>{{ $value }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status_arsip_filter_so" class="form-label">Status Arsip</label>
                            <select name="status_arsip" id="status_arsip_filter_so" class="form-select form-select-sm"
                                onchange="this.form.submit()">
                                <option value="aktif"
                                    {{ ($request->status_arsip ?? 'aktif') == 'aktif' ? 'selected' : '' }}>
                                    Aktif</option>
                                <option value="arsip" {{ ($request->status_arsip ?? '') == 'arsip' ? 'selected' : '' }}>
                                    Diarsipkan</option>
                                <option value="semua" {{ ($request->status_arsip ?? '') == 'semua' ? 'selected' : '' }}>
                                    Semua
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="tanggal_mulai_filter_so" class="form-label">Tgl. Opname Mulai</label>
                            <input type="date" name="tanggal_mulai" id="tanggal_mulai_filter_so"
                                class="form-control form-control-sm" value="{{ $request->tanggal_mulai ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label for="tanggal_selesai_filter_so" class="form-label">Tgl. Opname Sampai</label>
                            <input type="date" name="tanggal_selesai" id="tanggal_selesai_filter_so"
                                class="form-control form-control-sm" value="{{ $request->tanggal_selesai ?? '' }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary btn-sm w-100" type="submit"><i class="fas fa-filter"></i>
                                Filter</button>
                        </div>
                        @if (
                            $request->search ||
                                $request->status ||
                                $request->id_ruangan ||
                                $request->id_operator ||
                                $request->tanggal_mulai ||
                                $request->tanggal_selesai ||
                                ($request->status_arsip != 'aktif' && $request->status_arsip != null))
                            <div class="col-md-2 d-flex align-items-end">
                                <a href="{{ route('admin.stok-opname.index') }}"
                                    class="btn btn-outline-secondary btn-sm w-100" title="Reset Filter">
                                    <i class="fas fa-times"></i> Reset
                                </a>
                            </div>
                        @endif
                    </div>
                </form>

                <div class="table-responsive">
                    <table id="tabelStokOpname"
                        class="table table-sm table-bordered table-hover dt-responsive nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">No</th>
                                <th>Tgl. Opname</th>
                                <th>Ruangan</th>
                                <th>Operator</th>
                                <th class="text-center">Status Sesi</th>
                                <th>Catatan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stokOpnameList as $index => $so)
                                <tr class="{{ $so->trashed() ? 'table-danger-light' : '' }}">
                                    <td class="text-center">{{ $stokOpnameList->firstItem() + $index }}</td>
                                    <td data-sort="{{ $so->tanggal_opname }}">
                                        {{ \Carbon\Carbon::parse($so->tanggal_opname)->isoFormat('DD MMM YYYY') }}</td>
                                    <td>{{ optional($so->ruangan)->nama_ruangan }}</td>
                                    <td>{{ optional($so->operator)->username }}</td>
                                    <td class="text-center">
                                        @php
                                            $statusClass = match (strtolower($so->status ?? '')) {
                                                strtolower(App\Models\StokOpname::STATUS_DRAFT) => 'secondary',
                                                strtolower(App\Models\StokOpname::STATUS_SELESAI) => 'success',
                                                strtolower(App\Models\StokOpname::STATUS_DIBATALKAN) => 'danger',
                                                default => 'light text-dark',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">{{ $so->status }}</span>
                                        @if ($so->trashed())
                                            <span class="badge bg-dark ms-1">Diarsipkan</span>
                                        @endif
                                    </td>
                                    <td data-bs-toggle="tooltip" title="{{ $so->catatan }}">
                                        {{ Str::limit($so->catatan, 50) }}</td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            @if ($so->trashed())
                                                @can('restore', $so)
                                                    <form action="{{ route('admin.stok-opname.restore', $so->id) }}"
                                                        method="POST" class="d-inline form-restore-so">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm btn-restore"
                                                            data-bs-toggle="tooltip" title="Pulihkan Sesi SO">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            @else
                                                @can('view', $so)
                                                    <a href="{{ route('admin.stok-opname.show', $so->id) }}"
                                                        class="btn btn-info btn-sm" data-bs-toggle="tooltip"
                                                        title="Lihat Detail & Proses">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endcan
                                                @if ($so->status === App\Models\StokOpname::STATUS_DRAFT)
                                                    @can('update', $so)
                                                        <a href="{{ route('admin.stok-opname.edit', $so->id) }}"
                                                            class="btn btn-warning btn-sm" data-bs-toggle="tooltip"
                                                            title="Edit Sesi">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endcan
                                                @endif
                                                @can('delete', $so)
                                                    @if (in_array($so->status, [App\Models\StokOpname::STATUS_DRAFT, App\Models\StokOpname::STATUS_DIBATALKAN]))
                                                        <button type="button" class="btn btn-danger btn-sm btn-delete-so"
                                                            data-id="{{ $so->id }}"
                                                            data-ruangan="{{ optional($so->ruangan)->nama_ruangan }}"
                                                            data-tanggal="{{ \Carbon\Carbon::parse($so->tanggal_opname)->isoFormat('DD MMM YYYY') }}"
                                                            data-bs-toggle="tooltip" title="Arsipkan Sesi SO">
                                                            <i class="fas fa-archive"></i>
                                                        </button>
                                                    @endif
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">
                                        Tidak ada data sesi stok opname.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($stokOpnameList instanceof \Illuminate\Pagination\LengthAwarePaginator && $stokOpnameList->hasPages())
                    <div class="mt-3 d-flex justify-content-end">
                        {{ $stokOpnameList->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <form id="formDeleteStokOpname" method="POST" style="display: none;">@csrf @method('DELETE')</form>

@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#tabelStokOpname')) {
                $('#tabelStokOpname').DataTable().destroy();
            }
            if ($('#tabelStokOpname tbody tr').length > 0 && !$('#tabelStokOpname tbody tr td[colspan="7"]')
                .length) {
                $('#tabelStokOpname').DataTable({
                    responsive: true,
                    paging: true, // Paginasi sudah ditangani Laravel
                    searching: false, // Filter sudah ada di atas tabel
                    info: true, // Info jumlah entri juga bisa dihandle Laravel
                    ordering: true,
                    order: [
                        [1, 'desc'] // Default order by Tanggal Opname descending
                    ],
                    columnDefs: [{
                        targets: [0, 6], // Kolom No dan Aksi tidak bisa diorder
                        orderable: false,
                        searchable: false
                    }],
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

            $('.select2-filter').select2({
                theme: "bootstrap-5",
                width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' :
                    'style',
                placeholder: $(this).data('placeholder') || "-- Pilih --",
                allowClear: true
            });

            // Handle Delete Confirmation
            document.querySelectorAll('.btn-delete-so').forEach(button => {
                button.addEventListener('click', function() {
                    const soId = this.getAttribute('data-id');
                    const ruanganNama = this.getAttribute('data-ruangan');
                    const tanggal = this.getAttribute('data-tanggal'); // Sudah diformat
                    const formDelete = document.getElementById('formDeleteStokOpname');

                    Swal.fire({
                        title: 'Konfirmasi Arsipkan Sesi SO',
                        html: `Anda yakin ingin mengarsipkan sesi Stok Opname untuk ruangan <strong>"${ruanganNama}"</strong> pada tanggal <strong>${tanggal}</strong>?`,
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
                                    `{{ route('admin.stok-opname.destroy', ['stokOpname' => ':id']) }}`
                                    .replace(':id', soId);
                                formDelete.submit();
                            }
                        }
                    });
                });
            });

            // Handle Restore Confirmation
            document.querySelectorAll('.form-restore-so').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    // Ambil data dari elemen yang lebih stabil, misal data attribute di tombol jika ada
                    const ruanganNama = this.closest('tr').querySelector('td:nth-child(3)')
                        .textContent.trim();
                    const tanggal = this.closest('tr').querySelector('td:nth-child(2)').textContent
                        .trim(); // Sudah diformat
                    Swal.fire({
                        title: 'Konfirmasi Pulihkan Sesi SO',
                        html: `Anda yakin ingin memulihkan sesi Stok Opname untuk ruangan <strong>"${ruanganNama}"</strong> pada tanggal <strong>${tanggal}</strong>? Detail pemeriksaan juga akan dipulihkan.`,
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
