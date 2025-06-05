@extends('layouts.app')

@section('title', 'Manajemen User')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        #tabelUser th,
        #tabelUser td {
            vertical-align: middle;
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
                    <h4 class="mb-sm-0">Manajemen Pengguna Sistem</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Manajemen User</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="card-title mb-0 flex-grow-1"><i class="fas fa-users me-2"></i>Data Pengguna</h5>
                @can('create', App\Models\User::class)
                    <button type="button" id="btnTambahUser" class="btn btn-primary btn-md" data-bs-toggle="modal"
                        data-bs-target="#modalTambahUser" title="Tambah pengguna baru">
                        <i class="mdi mdi-plus me-1"></i> Tambah User
                    </button>
                @endcan
            </div>

            <div class="card-body">

                @if ($errors->storeUserErrors->any() && old('form_type') === 'create')
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const modalTambahUser = new bootstrap.Modal(document.getElementById('modalTambahUser'));
                            modalTambahUser.show();
                        });
                    </script>
                @endif
                @if ($errors->updateUserErrors->any() && old('form_type') === 'edit')
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const userIdToEdit = '{{ old('error_user_id') }}'; // Pastikan controller mengirim ini
                            if (userIdToEdit) {
                                const editButton = document.querySelector(`.btn-edit-user[data-id="${userIdToEdit}"]`);
                                if (editButton) {
                                    // Untuk auto-open modal edit dengan error, JS bisa lebih kompleks
                                    // atau cukup tampilkan pesan error global.
                                    // Contoh: Panggil klik pada tombol edit untuk memicu modal
                                    // editButton.click(); // Ini akan mengisi form dengan data lama dari server jika ada
                                    // Untuk sekarang, kita tampilkan error global saja
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Validasi Gagal',
                                        html: 'Periksa kembali data yang Anda masukkan pada form edit.',
                                        showConfirmButton: true
                                    });
                                }
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Validasi Gagal',
                                    html: 'Periksa kembali data yang Anda masukkan.',
                                    showConfirmButton: true
                                });
                            }
                        });
                    </script>
                @endif

                {{-- Filter Pencarian dan Role --}}
                <form method="GET" action="{{ route('admin.users.index') }}" class="mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label for="search_user" class="form-label">Pencarian</label>
                            <input type="text" name="search" id="search_user" class="form-control form-control-sm"
                                placeholder="Cari username atau email..." value="{{ $searchTerm ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label for="role_filter_user" class="form-label">Role</label>
                            <select name="role" id="role_filter_user" class="form-select form-select-sm">
                                <option value="">-- Semua Role --</option>
                                @foreach ($roles as $roleValue)
                                    <option value="{{ $roleValue }}"
                                        {{ ($roleFilter ?? '') == $roleValue ? 'selected' : '' }}>{{ $roleValue }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status_filter_user" class="form-label">Status</label>
                            <select name="status" id="status_filter_user" class="form-select form-select-sm">
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
                        @if ($searchTerm || $roleFilter || ($statusFilter != 'aktif' && $statusFilter != null))
                            <div class="col-md-12 mt-2 text-end">
                                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm"
                                    title="Reset Filter">
                                    <i class="fas fa-times"></i> Reset Semua Filter
                                </a>
                            </div>
                        @endif
                    </div>
                </form>

                <div class="table-responsive">
                    <table id="tabelUser" class="table table-bordered dt-responsive nowrap w-100 table-hover">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 5%;">No</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th class="text-center">Role</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Dibuat Pada</th>
                                <th class="text-center" style="width: 10%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $index => $user)
                                <tr class="{{ $user->trashed() ? 'table-danger bg-light-danger' : '' }}">
                                    <td class="text-center">{{ $users->firstItem() + $index }}</td>
                                    <td>
                                        @can('view', $user)
                                            <a href="{{ route('admin.users.show', $user->id) }}"
                                                class="fw-medium">{{ $user->username }}</a>
                                        @else
                                            {{ $user->username }}
                                        @endcan
                                        @if ($user->trashed())
                                            <small class="d-block text-danger">(Diarsipkan pada:
                                                {{ $user->deleted_at->isoFormat('DD MMM YYYY, HH:mm') }})</small>
                                        @endif
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td class="text-center">
                                        <span
                                            class="badge bg-{{ strtolower($user->role) == 'admin' ? 'danger' : (strtolower($user->role) == 'operator' ? 'warning text-dark' : 'success') }}">
                                            {{ $user->role }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($user->trashed())
                                            <span class="badge bg-secondary">Diarsipkan</span>
                                        @else
                                            <span class="badge bg-success">Aktif</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $user->created_at->isoFormat('DD MMM YYYY, HH:mm') }}</td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            @if ($user->trashed())
                                                @can('restore', $user)
                                                    <form action="{{ route('admin.users.restore', $user->id) }}" method="POST"
                                                        class="d-inline form-restore-user">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm btn-restore"
                                                            data-bs-toggle="tooltip" title="Pulihkan {{ $user->username }}">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                                {{-- Tombol Force Delete jika diperlukan --}}
                                                {{-- @can('forceDelete', $user)
                                                    <button type="button" class="btn btn-outline-danger btn-sm btn-force-delete-user"
                                                        data-id="{{ $user->id }}" data-username="{{ $user->username }}"
                                                        data-bs-toggle="tooltip" title="Hapus Permanen {{ $user->username }}">
                                                        <i class="fas fa-skull-crossbones"></i>
                                                    </button>
                                                @endcan --}}
                                            @else
                                                @can('update', $user)
                                                    <button type="button" class="btn btn-warning btn-sm btn-edit-user"
                                                        data-user='{!! json_encode($user->only(['id', 'username', 'email', 'role'])) !!}' data-id="{{ $user->id }}"
                                                        data-bs-toggle="modal" data-bs-target="#modalEditUser"
                                                        title="Edit {{ $user->username }}">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                @endcan
                                                @can('delete', $user)
                                                    @if (Auth::id() !== $user->id)
                                                        <button type="button" class="btn btn-danger btn-sm btn-delete-user"
                                                            data-id="{{ $user->id }}"
                                                            data-username="{{ $user->username }}" data-bs-toggle="tooltip"
                                                            title="Hapus {{ $user->username }}">
                                                            <i class="fas fa-trash"></i>
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
                                        @if ($searchTerm || $roleFilter || $statusFilter === 'arsip')
                                            Tidak ada pengguna yang cocok dengan kriteria filter Anda.
                                        @else
                                            Belum ada data pengguna. Silakan tambahkan pengguna baru.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($users instanceof \Illuminate\Pagination\LengthAwarePaginator && $users->hasPages())
                    <div class="mt-3 d-flex justify-content-end">
                        {{ $users->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @can('create', App\Models\User::class)
        @include('admin.users.partials.modal-create', ['roles' => $roles])
    @endcan
    @if ($users->first(fn($u) => Gate::allows('update', $u)))
        @include('admin.users.partials.modal-edit', ['roles' => $roles])
    @endif

    <form id="formDeleteUser" method="POST" style="display: none;">@csrf @method('DELETE')</form>
    {{-- Form untuk force delete jika ada --}}
    {{-- <form id="formForceDeleteUser" method="POST" style="display: none;">@csrf @method('DELETE')</form> --}}

@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if ($.fn.DataTable.isDataTable('#tabelUser')) {
                $('#tabelUser').DataTable().destroy();
            }
            if ($('#tabelUser tbody tr').length > 0 && !$('#tabelUser tbody tr td[colspan="7"]').length) {
                $('#tabelUser').DataTable({
                    responsive: true,
                    paging: true,
                    searching: false,
                    info: true,
                    ordering: true,
                    order: [
                        [1, 'asc']
                    ],
                    columnDefs: [{
                            targets: [0, 6],
                            orderable: false,
                            searchable: false
                        },
                        {
                            targets: [3, 4, 5],
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

            const modalTambahUserElement = document.getElementById('modalTambahUser');
            if (modalTambahUserElement) {
                const modalTambah = new bootstrap.Modal(modalTambahUserElement);
                const btnTambahUser = document.getElementById('btnTambahUser');
                if (btnTambahUser) {
                    btnTambahUser.addEventListener('click', () => {
                        const formTambah = modalTambahUserElement.querySelector('form');
                        if (formTambah) {
                            formTambah.reset();
                            formTambah.action = "{{ route('admin.users.store') }}";
                        }
                        formTambah.querySelectorAll('.is-invalid').forEach(el => el.classList.remove(
                            'is-invalid'));
                        formTambah.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
                        modalTambah.show();
                    });
                }
                // Jika ada error validasi dari server saat create, buka kembali modal
                @if ($errors->storeUserErrors->any() && old('form_type') === 'create')
                    modalTambah.show();
                @endif
            }

            const modalEditUserElement = document.getElementById('modalEditUser');
            if (modalEditUserElement) {
                const modalEdit = new bootstrap.Modal(modalEditUserElement);
                document.querySelectorAll('.btn-edit-user').forEach(button => {
                    button.addEventListener('click', () => {
                        const data = JSON.parse(button.dataset.user);
                        const form = modalEditUserElement.querySelector('#formEditUserAction');
                        const titleSpan = modalEditUserElement.querySelector(
                            '#editUsernameTitleModal');

                        if (form) {
                            form.action = `{{ route('admin.users.update', ['user' => ':id']) }}`
                                .replace(':id', data.id);
                            form.querySelector('#edit_modal_username').value = data.username || '';
                            form.querySelector('#edit_modal_email').value = data.email || '';
                            form.querySelector('#edit_modal_role').value = data.role || '';
                            form.querySelector('#edit_modal_password').value = '';
                            form.querySelector('#edit_modal_password_confirmation').value = '';
                        }
                        if (titleSpan) {
                            titleSpan.textContent = data.username || '';
                        } else {
                            modalEditUserElement.querySelector('.modal-title').textContent =
                                'Edit User: ' + (data.username || '');
                        }
                        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove(
                            'is-invalid'));
                        form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent =
                            '');
                        modalEdit.show();
                    });
                });
                // Jika ada error validasi dari server saat update, buka kembali modal yang sesuai
                @if ($errors->updateUserErrors->any() && old('form_type') === 'edit' && session('error_user_id'))
                    const editButtonForError = document.querySelector(
                        `.btn-edit-user[data-id="{{ session('error_user_id') }}"]`);
                    if (editButtonForError) {
                        // Untuk mengisi ulang data lama dan error, idealnya controller mengirimkan kembali old input
                        // atau kita bisa memicu event klik pada tombol yang sesuai.
                        // Ini akan membuka modal dengan data dari server (jika ada old input)
                        // editButtonForError.click(); // Ini mungkin menyebabkan loop jika error tidak di-clear
                        modalEdit.show(); // Cara lebih aman, tapi form mungkin tidak terisi old input
                    }
                @endif
            }

            // Handle Delete Confirmation
            document.querySelectorAll('.btn-delete-user').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const username = this.getAttribute('data-username');
                    const formDelete = document.getElementById('formDeleteUser');

                    Swal.fire({
                        title: 'Konfirmasi Hapus Pengguna',
                        html: `Anda yakin ingin menghapus pengguna <strong>"${username}"</strong>? <br><small class="text-danger">Tindakan ini akan memindahkan pengguna ke arsip dan dapat dipulihkan nanti.</small>`,
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
                                    `{{ route('admin.users.destroy', ['user' => ':id']) }}`
                                    .replace(':id', userId);
                                formDelete.submit();
                            }
                        }
                    });
                });
            });

            // Handle Restore Confirmation
            document.querySelectorAll('.form-restore-user').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const username = this.closest('tr').querySelector(
                        'a.fw-medium, td:nth-child(2)').textContent.trim(); // Ambil username
                    Swal.fire({
                        title: 'Konfirmasi Pulihkan Pengguna',
                        html: `Anda yakin ingin memulihkan pengguna <strong>"${username}"</strong>?`,
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
