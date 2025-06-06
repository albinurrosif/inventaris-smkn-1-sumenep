@extends('layouts.app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('title', 'Manajemen Peminjaman Aset')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Manajemen Peminjaman Aset</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Peminjaman Aset</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Filter Peminjaman</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.peminjaman.index') }}">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label for="search" class="form-label">Cari</label>
                                    <input type="text" class="form-control" id="search" name="search"
                                        value="{{ request('search') }}" placeholder="Tujuan, Guru, Kode Barang...">
                                </div>
                                <div class="col-md-2">
                                    <label for="status" class="form-label">Status Peminjaman</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">Semua Status</option>
                                        @foreach ($statusList as $statusValue => $statusLabel)
                                            <option value="{{ $statusValue }}"
                                                {{ request('status') == $statusValue ? 'selected' : '' }}>
                                                {{ $statusLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="id_guru" class="form-label">Guru Peminjam</label>
                                    <select class="form-select" id="id_guru" name="id_guru">
                                        <option value="">Semua Guru</option>
                                        @foreach ($guruList as $guru)
                                            <option value="{{ $guru->id }}"
                                                {{ request('id_guru') == $guru->id ? 'selected' : '' }}>
                                                {{ $guru->username }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="tanggal_mulai" class="form-label">Tgl Pengajuan Mulai</label>
                                    <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai"
                                        value="{{ request('tanggal_mulai') }}">
                                </div>
                                <div class="col-md-2">
                                    <label for="tanggal_selesai" class="form-label">Tgl Pengajuan Selesai</label>
                                    <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai"
                                        value="{{ request('tanggal_selesai') }}">
                                </div>
                                <div class="col-md-2">
                                    <label for="status_arsip" class="form-label">Status Arsip</label>
                                    <select class="form-select" id="status_arsip" name="status_arsip">
                                        <option value="aktif"
                                            {{ request('status_arsip', 'aktif') == 'aktif' ? 'selected' : '' }}>Aktif
                                        </option>
                                        <option value="arsip" {{ request('status_arsip') == 'arsip' ? 'selected' : '' }}>
                                            Diarsipkan</option>
                                        <option value="semua" {{ request('status_arsip') == 'semua' ? 'selected' : '' }}>
                                            Semua</option>
                                    </select>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <a href="{{ route('admin.peminjaman.index') }}"
                                        class="btn btn-secondary w-100">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Daftar Peminjaman Aset</h5>
                        {{-- Admin mungkin tidak membuat pengajuan dari sini, tapi bisa jika ada skenario khusus --}}
                        {{-- @can('create', App\Models\Peminjaman::class)
                        <a href="{{ route('admin.peminjaman.create') }}" class="btn btn-success float-end mt-n1"><i data-feather="plus" class="me-2"></i>Ajukan Peminjaman Baru</a>
                    @endcan --}}
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>No.</th>
                                        <th>ID Peminjaman</th>
                                        <th>Guru Peminjam</th>
                                        <th>Tujuan</th>
                                        <th>Tgl Pengajuan</th>
                                        <th>Tgl Rencana Pinjam</th>
                                        <th>Tgl Harus Kembali</th>
                                        <th>Jml Item</th>
                                        <th>Status</th>
                                        <th>Ruangan Tujuan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($peminjamanList as $index => $peminjaman)
                                        <tr class="{{ $peminjaman->trashed() ? 'table-danger' : '' }}">
                                            <td>{{ $peminjamanList->firstItem() + $index }}</td>
                                            <td>PMJ-{{ str_pad($peminjaman->id, 5, '0', STR_PAD_LEFT) }}</td>
                                            <td>{{ $peminjaman->guru->username ?? 'N/A' }}</td>
                                            <td title="{{ $peminjaman->tujuan_peminjaman }}">
                                                {{ Str::limit($peminjaman->tujuan_peminjaman, 30) }}</td>
                                            <td>{{ $peminjaman->tanggal_pengajuan ? $peminjaman->tanggal_pengajuan->format('d M Y, H:i') : '-' }}
                                            </td>
                                            <td>{{ $peminjaman->tanggal_rencana_pinjam ? $peminjaman->tanggal_rencana_pinjam->format('d M Y') : '-' }}
                                            </td>
                                            <td>{{ $peminjaman->tanggal_harus_kembali ? $peminjaman->tanggal_harus_kembali->format('d M Y') : '-' }}
                                            </td>
                                            <td>{{ $peminjaman->detail_peminjaman_count ?? $peminjaman->detailPeminjaman()->count() }}
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ App\Models\Peminjaman::statusColor($peminjaman->status) }}">
                                                    {{ $peminjaman->status }}
                                                </span>
                                                @if ($peminjaman->ada_item_terlambat)
                                                    <span class="badge bg-danger">Terlambat</span>
                                                @endif
                                            </td>
                                            <td>{{ $peminjaman->ruanganTujuanPeminjaman->nama_ruangan ?? '-' }}</td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                        type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i data-feather="more-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        @can('view', $peminjaman)
                                                            <li><a class="dropdown-item"
                                                                    href="{{ route('admin.peminjaman.show', $peminjaman->id) }}"><i
                                                                        data-feather="eye" class="me-2"></i>Lihat Detail</a>
                                                            </li>
                                                        @endcan

                                                        @if (!$peminjaman->trashed())
                                                            @can('update', $peminjaman)
                                                                @if (in_array($peminjaman->status, [
                                                                        App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN,
                                                                        App\Models\Peminjaman::STATUS_DISETUJUI,
                                                                    ]))
                                                                    <li><a class="dropdown-item"
                                                                            href="{{ route('admin.peminjaman.edit', $peminjaman->id) }}"><i
                                                                                data-feather="edit-2"
                                                                                class="me-2"></i>Edit</a></li>
                                                                @endif
                                                            @endcan

                                                            @can('manage', $peminjaman)
                                                                @if ($peminjaman->status === App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN)
                                                                    <li><button class="dropdown-item btn-approve"
                                                                            data-id="{{ $peminjaman->id }}"><i
                                                                                data-feather="check-circle"
                                                                                class="me-2"></i>Setujui</button></li>
                                                                    <li><button class="dropdown-item btn-reject"
                                                                            data-id="{{ $peminjaman->id }}"><i
                                                                                data-feather="x-circle"
                                                                                class="me-2"></i>Tolak</button></li>
                                                                @endif
                                                            @endcan

                                                            @can('cancelByUser', $peminjaman)
                                                                {{-- Admin juga bisa cancel --}}
                                                                @if (in_array($peminjaman->status, [
                                                                        App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN,
                                                                        App\Models\Peminjaman::STATUS_DISETUJUI,
                                                                    ]) &&
                                                                        !$peminjaman->detailPeminjaman()->where('status_unit', App\Models\DetailPeminjaman::STATUS_ITEM_DIAMBIL)->exists())
                                                                    <li><button class="dropdown-item btn-cancel-by-user"
                                                                            data-id="{{ $peminjaman->id }}"><i
                                                                                data-feather="slash"
                                                                                class="me-2"></i>Batalkan Pengajuan</button>
                                                                    </li>
                                                                @endif
                                                            @endcan

                                                            @can('delete', $peminjaman)
                                                                @if (in_array($peminjaman->status, [
                                                                        App\Models\Peminjaman::STATUS_SELESAI,
                                                                        App\Models\Peminjaman::STATUS_DITOLAK,
                                                                        App\Models\Peminjaman::STATUS_DIBATALKAN,
                                                                    ]))
                                                                    <li>
                                                                        <form
                                                                            action="{{ route('admin.peminjaman.destroy', $peminjaman->id) }}"
                                                                            method="POST" class="d-inline form-archive">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit"
                                                                                class="dropdown-item btn-archive"><i
                                                                                    data-feather="archive"
                                                                                    class="me-2"></i>Arsipkan</button>
                                                                        </form>
                                                                    </li>
                                                                @endif
                                                            @endcan
                                                        @else
                                                            {{-- Jika sudah diarsipkan --}}
                                                            @can('restore', $peminjaman)
                                                                <li>
                                                                    <form
                                                                        action="{{ route('admin.peminjaman.restore', $peminjaman->id) }}"
                                                                        method="POST" class="d-inline form-restore">
                                                                        @csrf
                                                                        <button type="submit"
                                                                            class="dropdown-item btn-restore"><i
                                                                                data-feather="rotate-ccw"
                                                                                class="me-2"></i>Pulihkan</button>
                                                                    </form>
                                                                </li>
                                                            @endcan
                                                        @endif
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="text-center">Tidak ada data peminjaman.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $peminjamanList->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal untuk Persetujuan --}}
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="approveModalLabel">Setujui Peminjaman</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Anda yakin ingin menyetujui pengajuan peminjaman ini?</p>
                        <div class="mb-3">
                            <label for="catatan_operator_approve" class="form-label">Catatan Operator (Opsional):</label>
                            <textarea class="form-control" id="catatan_operator_approve" name="catatan_operator_approve" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Ya, Setujui</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal untuk Penolakan --}}
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectModalLabel">Tolak Peminjaman</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Anda yakin ingin menolak pengajuan peminjaman ini?</p>
                        <div class="mb-3">
                            <label for="catatan_operator_reject" class="form-label">Alasan Penolakan <span
                                    class="text-danger">*</span>:</label>
                            <textarea class="form-control" id="catatan_operator_reject" name="catatan_operator_reject" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Ya, Tolak</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal untuk Pembatalan oleh Pengguna/Admin --}}
    <div class="modal fade" id="cancelByUserModal" tabindex="-1" aria-labelledby="cancelByUserModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="cancelByUserForm" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelByUserModalLabel">Batalkan Pengajuan Peminjaman</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Anda yakin ingin membatalkan pengajuan peminjaman ini?</p>
                        <div class="mb-3">
                            <label for="alasan_pembatalan" class="form-label">Alasan Pembatalan (Opsional):</label>
                            <textarea class="form-control" id="alasan_pembatalan" name="alasan_pembatalan" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-warning">Ya, Batalkan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tombol Setujui
            const approveButtons = document.querySelectorAll('.btn-approve');
            approveButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const peminjamanId = this.dataset.id;
                    const approveForm = document.getElementById('approveForm');
                    approveForm.action = `{{ url('admin/peminjaman') }}/${peminjamanId}/approve`;
                    const approveModal = new bootstrap.Modal(document.getElementById(
                        'approveModal'));
                    approveModal.show();
                });
            });

            // Tombol Tolak
            const rejectButtons = document.querySelectorAll('.btn-reject');
            rejectButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const peminjamanId = this.dataset.id;
                    const rejectForm = document.getElementById('rejectForm');
                    rejectForm.action = `{{ url('admin/peminjaman') }}/${peminjamanId}/reject`;
                    const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
                    rejectModal.show();
                });
            });

            // Tombol Batalkan oleh User/Admin
            const cancelByUserButtons = document.querySelectorAll('.btn-cancel-by-user');
            cancelByUserButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const peminjamanId = this.dataset.id;
                    const cancelForm = document.getElementById('cancelByUserForm');
                    cancelForm.action =
                        `{{ route('admin.peminjaman.cancelByUser', '') }}/${peminjamanId}`; // Menggunakan route name
                    const cancelModal = new bootstrap.Modal(document.getElementById(
                        'cancelByUserModal'));
                    cancelModal.show();
                });
            });

            // Konfirmasi untuk Arsip
            const archiveForms = document.querySelectorAll('.form-archive');
            archiveForms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    Swal.fire({
                        title: 'Anda yakin?',
                        text: "Data peminjaman ini akan diarsipkan!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, arsipkan!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            // Konfirmasi untuk Pulihkan
            const restoreForms = document.querySelectorAll('.form-restore');
            restoreForms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    Swal.fire({
                        title: 'Anda yakin?',
                        text: "Data peminjaman ini akan dipulihkan!",
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, pulihkan!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
@endpush
