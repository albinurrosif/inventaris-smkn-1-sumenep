@php
    // Menggunakan konstanta peran dari model User
    use App\Models\User;
@endphp

@extends('layouts.app')

@section('title', 'Detail User - ' . $user->username)

@push('styles')
    {{-- Tambahkan CSS khusus jika diperlukan --}}
    <style>
        .dl-horizontal dt {
            white-space: normal;
            font-weight: normal;
            /* Membuat dt tidak terlalu tebal */
        }

        .card-profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, .1);
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Detail Pengguna: {{ $user->username }}</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Manajemen User</a></li>
                            <li class="breadcrumb-item active">{{ $user->username }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baris Tombol Aksi Utama --}}
        <div class="row mb-3">
            <div class="col-12 text-end">
                <div class="d-flex gap-2 justify-content-end">
                    @if ($user->trashed())
                        @can('restore', $user)
                            <form action="{{ route('admin.users.restore', $user->id) }}" method="POST"
                                class="d-inline form-restore-user-show">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm btn-restore" data-bs-toggle="tooltip"
                                    title="Pulihkan pengguna {{ $user->username }}">
                                    <i class="fas fa-undo me-1"></i> Pulihkan User
                                </button>
                            </form>
                        @endcan
                    @else
                        @can('update', $user)
                            {{-- Tombol Edit bisa langsung ke halaman edit atau memicu modal jika ada --}}
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit me-1"></i> Edit User
                            </a>
                        @endcan
                        @can('delete', $user)
                            @if (Auth::id() !== $user->id)
                                <button type="button" class="btn btn-danger btn-sm btn-delete-user-show"
                                    data-id="{{ $user->id }}" data-username="{{ $user->username }}" data-bs-toggle="tooltip"
                                    title="Arsipkan {{ $user->username }}">
                                    <i class="fas fa-archive me-1"></i> Arsipkan User
                                </button>
                            @endif
                        @endcan
                    @endif
                    <a href="{{ route('admin.users.index', ['status' => $user->trashed() ? 'arsip' : 'aktif']) }}"
                        class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>

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

        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body text-center">
                        {{-- Placeholder untuk foto profil, bisa diganti dengan foto asli jika ada --}}
                        <img src="https://placehold.co/120x120/EBF4FF/737A91?text={{ strtoupper(substr($user->username, 0, 1)) }}"
                            alt="Foto Profil {{ $user->username }}" class="rounded-circle mb-3 card-profile-img">
                        <h5 class="card-title mb-1">{{ $user->username }}</h5>
                        <p class="text-muted mb-2">{{ $user->email }}</p>
                        @php
                            $roleClass = match (strtolower($user->role)) {
                                'admin' => 'danger',
                                'operator' => 'warning text-dark',
                                default => 'success',
                            };
                        @endphp
                        <span class="badge bg-{{ $roleClass }} fs-6">{{ $user->role }}</span>
                        @if ($user->trashed())
                            <p class="mt-2 mb-0"><span class="badge bg-secondary fs-6">Diarsipkan</span></p>
                            <small class="text-muted">Diarsipkan pada:
                                {{ $user->deleted_at->isoFormat('DD MMM YYYY, HH:mm') }}</small>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0"><i class="fas fa-user-tag me-2"></i>Informasi Akun</h6>
                    </div>
                    <div class="card-body">
                        <dl class="row dl-horizontal">
                            <dt class="col-sm-4">ID Pengguna</dt>
                            <dd class="col-sm-8">{{ $user->id }}</dd>

                            <dt class="col-sm-4">Username</dt>
                            <dd class="col-sm-8">{{ $user->username }}</dd>

                            <dt class="col-sm-4">Alamat Email</dt>
                            <dd class="col-sm-8">{{ $user->email }}</dd>

                            <dt class="col-sm-4">Peran (Role)</dt>
                            <dd class="col-sm-8">{{ $user->role }}</dd>

                            <dt class="col-sm-4">Email Terverifikasi Pada</dt>
                            <dd class="col-sm-8">
                                {{ $user->email_verified_at ? $user->email_verified_at->isoFormat('DD MMMM YYYY, HH:mm') : 'Belum diverifikasi' }}
                            </dd>

                            <dt class="col-sm-4">Terdaftar Pada</dt>
                            <dd class="col-sm-8">{{ $user->created_at->isoFormat('DD MMMM YYYY, HH:mm') }}</dd>

                            <dt class="col-sm-4">Terakhir Diperbarui</dt>
                            <dd class="col-sm-8">{{ $user->updated_at->isoFormat('DD MMMM YYYY, HH:mm') }}</dd>

                            @if ($user->trashed())
                                <dt class="col-sm-4 text-danger">Diarsipkan Pada</dt>
                                <dd class="col-sm-8 text-danger">{{ $user->deleted_at->isoFormat('DD MMMM YYYY, HH:mm') }}
                                </dd>
                            @endif
                        </dl>
                    </div>
                </div>

                {{-- Bagian untuk menampilkan informasi terkait lainnya (opsional) --}}
                @if ($user->role == User::ROLE_OPERATOR && $user->ruanganYangDiKelola()->count() > 0)
                    <div class="card mt-3">
                        <div class="card-header bg-light">
                            <h6 class="card-title mb-0"><i class="fas fa-person-booth me-2"></i>Ruangan yang Dikelola</h6>
                        </div>
                        <div class="card-body p-2">
                            <ul class="list-group list-group-flush">
                                @foreach ($user->ruanganYangDiKelola as $ruangan)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <a
                                            href="{{ route('admin.ruangan.show', $ruangan->id) }}">{{ $ruangan->nama_ruangan }}</a>
                                        <span class="badge bg-primary rounded-pill">{{ $ruangan->kode_ruangan }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @if ($user->barangQrCodesYangDipegang()->whereNull('deleted_at')->count() > 0)
                    <div class="card mt-3">
                        <div class="card-header bg-light">
                            <h6 class="card-title mb-0"><i class="fas fa-hands-holding me-2"></i>Unit Barang yang Dipegang
                                Personal (Aktif)</h6>
                        </div>
                        <div class="card-body p-2">
                            <ul class="list-group list-group-flush">
                                @foreach ($user->barangQrCodesYangDipegang()->whereNull('deleted_at')->take(5)->get() as $item)
                                    {{-- Batasi jumlah untuk tampilan ringkas --}}
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <a
                                                href="{{ route('admin.barang-qr-code.show', $item->id) }}">{{ $item->barang->nama_barang ?? 'N/A' }}</a>
                                            <small class="d-block text-muted">{{ $item->kode_inventaris_sekolah }}</small>
                                        </div>
                                        <span class="badge bg-info">{{ $item->kondisi }}</span>
                                    </li>
                                @endforeach
                                @if ($user->barangQrCodesYangDipegang()->whereNull('deleted_at')->count() > 5)
                                    <li class="list-group-item text-center">
                                        <small><a href="#">Lihat semua...</a></small> {{-- Tambahkan link ke daftar lengkap jika perlu --}}
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- Form Delete (disembunyikan, digunakan oleh tombol Arsipkan User) --}}
    <form id="formDeleteUserShow" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle Delete (Arsipkan) Confirmation dari halaman show
            document.querySelectorAll('.btn-delete-user-show').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const username = this.getAttribute('data-username');
                    const formDelete = document.getElementById('formDeleteUserShow');

                    Swal.fire({
                        title: 'Konfirmasi Arsipkan Pengguna',
                        html: `Anda yakin ingin mengarsipkan pengguna <strong>"${username}"</strong>? <br><small class="text-danger">Pengguna akan dipindahkan ke arsip dan dapat dipulihkan nanti.</small>`,
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

            // Handle Restore Confirmation dari halaman show
            document.querySelectorAll('.form-restore-user-show').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const username = "{{ $user->username }}"; // Ambil username dari variabel $user
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
