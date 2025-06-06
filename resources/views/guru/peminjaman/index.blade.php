{{-- File: resources/views/guru/peminjaman/index.blade.php --}}
@extends('layouts.app') {{-- Sesuaikan dengan layout guru Anda --}}

@section('title', 'Daftar Pengajuan Peminjaman Saya')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Pengajuan Peminjaman Saya</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('guru.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Peminjaman Saya</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Filter Pengajuan</h5>
                            @can('create', App\Models\Peminjaman::class)
                                <a href="{{ route('guru.peminjaman.create') }}" class="btn btn-success">
                                    <i data-feather="plus" class="me-2"></i>Buat Pengajuan Baru
                                </a>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('guru.peminjaman.index') }}">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="search_guru" class="form-label">Cari Tujuan</label>
                                    <input type="text" class="form-control" id="search_guru" name="search"
                                        value="{{ request('search') }}" placeholder="Masukkan tujuan peminjaman...">
                                </div>
                                <div class="col-md-3">
                                    <label for="status_guru" class="form-label">Status Pengajuan</label>
                                    <select class="form-select" id="status_guru" name="status">
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
                                    <label for="tanggal_mulai_guru" class="form-label">Tgl Pengajuan Dari</label>
                                    <input type="date" class="form-control" id="tanggal_mulai_guru" name="tanggal_mulai"
                                        value="{{ request('tanggal_mulai') }}">
                                </div>
                                <div class="col-md-2">
                                    <label for="tanggal_selesai_guru" class="form-label">Sampai Tgl</label>
                                    <input type="date" class="form-control" id="tanggal_selesai_guru"
                                        name="tanggal_selesai" value="{{ request('tanggal_selesai') }}">
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
                        <h5 class="card-title mb-0">Daftar Pengajuan</h5>
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
                                        <th>ID Pengajuan</th>
                                        <th>Tujuan</th>
                                        <th>Tgl Pengajuan</th>
                                        <th>Tgl Rencana Pinjam</th>
                                        <th>Tgl Harus Kembali</th>
                                        <th>Jml Item</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($peminjamanList as $index => $peminjaman)
                                        <tr>
                                            <td>{{ $peminjamanList->firstItem() + $index }}</td>
                                            <td>PMJ-{{ str_pad($peminjaman->id, 5, '0', STR_PAD_LEFT) }}</td>
                                            <td title="{{ $peminjaman->tujuan_peminjaman }}">
                                                {{ Str::limit($peminjaman->tujuan_peminjaman, 35) }}</td>
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
                                            <td>
                                                <div class="d-flex gap-2">
                                                    @can('view', $peminjaman)
                                                        <a href="{{ route('guru.peminjaman.show', $peminjaman->id) }}"
                                                            class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip"
                                                            title="Lihat Detail">
                                                            <i data-feather="eye"></i>
                                                        </a>
                                                    @endcan
                                                    @can('update', $peminjaman)
                                                        @if ($peminjaman->status === App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN)
                                                            <a href="{{ route('guru.peminjaman.edit', $peminjaman->id) }}"
                                                                class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip"
                                                                title="Edit Pengajuan">
                                                                <i data-feather="edit-2"></i>
                                                            </a>
                                                        @endif
                                                    @endcan
                                                    @can('cancelByUser', $peminjaman)
                                                        @if (in_array($peminjaman->status, [
                                                                App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN,
                                                                App\Models\Peminjaman::STATUS_DISETUJUI,
                                                            ]) &&
                                                                !$peminjaman->detailPeminjaman()->where('status_unit', App\Models\DetailPeminjaman::STATUS_ITEM_DIAMBIL)->exists())
                                                            <button class="btn btn-sm btn-outline-danger btn-cancel-by-user"
                                                                data-id="{{ $peminjaman->id }}" data-bs-toggle="tooltip"
                                                                title="Batalkan Pengajuan">
                                                                <i data-feather="slash"></i>
                                                            </button>
                                                        @endif
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center">Anda belum memiliki pengajuan
                                                peminjaman.</td>
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

    {{-- Modal untuk Pembatalan oleh Pengguna --}}
    <div class="modal fade" id="cancelByUserModalGuru" tabindex="-1" aria-labelledby="cancelByUserModalGuruLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="cancelByUserFormGuru" method="POST"> {{-- Action akan di-set oleh JS --}}
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelByUserModalGuruLabel">Batalkan Pengajuan Peminjaman</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Anda yakin ingin membatalkan pengajuan peminjaman ID: <strong
                                id="cancelPeminjamanIdTextGuru"></strong>?</p>
                        <div class="mb-3">
                            <label for="alasan_pembatalan_guru" class="form-label">Alasan Pembatalan (Opsional):</label>
                            <textarea class="form-control" id="alasan_pembatalan_guru" name="alasan_pembatalan" rows="3"></textarea>
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
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })

            const cancelByUserButtonsGuru = document.querySelectorAll('.btn-cancel-by-user');
            const cancelByUserModalElGuru = document.getElementById('cancelByUserModalGuru');
            const cancelByUserModalGuru = cancelByUserModalElGuru ? new bootstrap.Modal(cancelByUserModalElGuru) :
                null;
            const cancelByUserFormGuru = document.getElementById('cancelByUserFormGuru');
            const cancelPeminjamanIdTextGuru = document.getElementById('cancelPeminjamanIdTextGuru');

            cancelByUserButtonsGuru.forEach(button => {
                button.addEventListener('click', function() {
                    const peminjamanId = this.dataset.id;
                    if (cancelByUserFormGuru) cancelByUserFormGuru.action =
                        `{{ route('guru.peminjaman.cancelByUser', ['peminjaman' => ':peminjamanId']) }}`
                        .replace(':peminjamanId', peminjamanId);
                    if (cancelPeminjamanIdTextGuru) cancelPeminjamanIdTextGuru.textContent =
                        `PMJ-${String(peminjamanId).padStart(5, '0')}`;
                    if (cancelByUserModalGuru) cancelByUserModalGuru.show();
                });
            });
        });
    </script>
@endpush






