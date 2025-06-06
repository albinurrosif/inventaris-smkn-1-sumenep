{{-- File: resources/views/operator/peminjaman/index.blade.php --}}
@extends('layouts.app') {{-- Sesuaikan dengan layout operator Anda --}}

@section('title', 'Manajemen Peminjaman Aset (Operator)')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Peminjaman Aset (Operator)</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('operator.dashboard') }}">Dashboard</a></li>
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
                        <form method="GET" action="{{ route('operator.peminjaman.index') }}">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="search" class="form-label">Cari</label>
                                    <input type="text" class="form-control" id="search" name="search"
                                        value="{{ request('search') }}" placeholder="Tujuan, Guru, Kode Barang...">
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status Peminjaman</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">Semua Status</option>
                                        @foreach ($statusList as $statusValue => $statusLabel)
                                            {{-- Operator mungkin hanya relevan dengan status tertentu --}}
                                            @if (in_array($statusValue, [
                                                    App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN,
                                                    App\Models\Peminjaman::STATUS_DISETUJUI,
                                                    App\Models\Peminjaman::STATUS_SEDANG_DIPINJAM,
                                                    App\Models\Peminjaman::STATUS_TERLAMBAT,
                                                    App\Models\Peminjaman::STATUS_MENUNGGU_VERIFIKASI_KEMBALI,
                                                    App\Models\Peminjaman::STATUS_SELESAI,
                                                ]))
                                                <option value="{{ $statusValue }}"
                                                    {{ request('status') == $statusValue ? 'selected' : '' }}>
                                                    {{ $statusLabel }}
                                                </option>
                                            @endif
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
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
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
                                        <tr>
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
                                                @if ($peminjaman->ada_item_terlambat && $peminjaman->status !== App\Models\Peminjaman::STATUS_SELESAI)
                                                    <span class="badge bg-danger ms-1">Terlambat</span>
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
                                                                    href="{{ route('operator.peminjaman.show', $peminjaman->id) }}"><i
                                                                        data-feather="eye" class="me-2"></i>Lihat Detail</a>
                                                            </li>
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

                                                        @can('update', $peminjaman)
                                                            {{-- Untuk update catatan operator --}}
                                                            @if (in_array($peminjaman->status, [
                                                                    App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN,
                                                                    App\Models\Peminjaman::STATUS_DISETUJUI,
                                                                    App\Models\Peminjaman::STATUS_SEDANG_DIPINJAM,
                                                                ]))
                                                                <li><a class="dropdown-item"
                                                                        href="{{ route('operator.peminjaman.edit', $peminjaman->id) }}"><i
                                                                            data-feather="edit-3" class="me-2"></i>Edit
                                                                        Catatan</a></li>
                                                            @endif
                                                        @endcan
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="text-center">Tidak ada data peminjaman yang relevan.
                                            </td>
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

    {{-- Modal untuk Persetujuan (Sama seperti di Admin) --}}
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
                        <p>Anda yakin ingin menyetujui pengajuan peminjaman ID: <strong
                                id="approvePeminjamanIdTextOp"></strong>?</p>
                        <div class="mb-3">
                            <label for="catatan_operator_approve_op" class="form-label">Catatan Operator
                                (Opsional):</label>
                            <textarea class="form-control" id="catatan_operator_approve_op" name="catatan_operator_approve" rows="3"></textarea>
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

    {{-- Modal untuk Penolakan (Sama seperti di Admin) --}}
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
                        <p>Anda yakin ingin menolak pengajuan peminjaman ID: <strong
                                id="rejectPeminjamanIdTextOp"></strong>?</p>
                        <div class="mb-3">
                            <label for="catatan_operator_reject_op" class="form-label">Alasan Penolakan <span
                                    class="text-danger">*</span>:</label>
                            <textarea class="form-control" id="catatan_operator_reject_op" name="catatan_operator_reject" rows="3"
                                required></textarea>
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
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tombol Setujui
            const approveButtonsOp = document.querySelectorAll('.btn-approve');
            approveButtonsOp.forEach(button => {
                button.addEventListener('click', function() {
                    const peminjamanId = this.dataset.id;
                    const approveFormOp = document.getElementById('approveForm');
                    if (approveFormOp) approveFormOp.action =
                        `{{ url('operator/peminjaman') }}/${peminjamanId}/approve`; // Sesuaikan URL
                    const approvePeminjamanIdTextOp = document.getElementById(
                        'approvePeminjamanIdTextOp');
                    if (approvePeminjamanIdTextOp) approvePeminjamanIdTextOp.textContent =
                        `PMJ-${String(peminjamanId).padStart(5, '0')}`;
                    const approveModalOp = new bootstrap.Modal(document.getElementById(
                        'approveModal'));
                    approveModalOp.show();
                });
            });

            // Tombol Tolak
            const rejectButtonsOp = document.querySelectorAll('.btn-reject');
            rejectButtonsOp.forEach(button => {
                button.addEventListener('click', function() {
                    const peminjamanId = this.dataset.id;
                    const rejectFormOp = document.getElementById('rejectForm');
                    if (rejectFormOp) rejectFormOp.action =
                        `{{ url('operator/peminjaman') }}/${peminjamanId}/reject`; // Sesuaikan URL
                    const rejectPeminjamanIdTextOp = document.getElementById(
                        'rejectPeminjamanIdTextOp');
                    if (rejectPeminjamanIdTextOp) rejectPeminjamanIdTextOp.textContent =
                        `PMJ-${String(peminjamanId).padStart(5, '0')}`;
                    const rejectModalOp = new bootstrap.Modal(document.getElementById(
                        'rejectModal'));
                    rejectModalOp.show();
                });
            });
        });
    </script>
@endpush
