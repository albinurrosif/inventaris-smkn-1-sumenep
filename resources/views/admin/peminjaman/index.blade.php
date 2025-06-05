@extends('layouts.app')

{{-- Judul halaman disesuaikan untuk Admin/Operator --}}
@section('title', 'Manajemen Daftar Peminjaman')

@section('content')
    <div class="container-fluid">
        <h4 class="mb-3">Manajemen Daftar Peminjaman</h4>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                    <h4 class="card-title mb-0">Data Semua Peminjaman</h4>
                    {{-- Tombol aksi spesifik Admin/Operator bisa ditambahkan di sini jika perlu,
                         misalnya tombol "Export Laporan Peminjaman" --}}
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        {{-- Contoh Tombol Export (jika ada fiturnya)
                        <a href="{{ route('peminjaman.export') }}" class="btn btn-outline-success btn-md">
                            <i class="mdi mdi-file-excel me-1"></i> Export Data
                        </a>
                        --}}
                    </div>
                </div>

                {{-- Form filter menggunakan route utama peminjaman --}}
                <form method="GET" action="{{ route('peminjaman.index') }}" id="filterForm" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="status_peminjaman" class="form-label">Status Peminjaman:</label>
                        <select name="status" id="status_peminjaman" class="form-select"> {{-- Ganti name ke 'status' sesuai controller --}}
                            <option value="">Semua Status</option>
                            @foreach ($statusOptions as $statusValue => $statusText)
                                <option value="{{ $statusValue }}"
                                    {{ request('status') == $statusValue ? 'selected' : '' }}>
                                    {{ $statusText }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="peminjam_id" class="form-label">Peminjam (Guru):</label>
                        <select name="peminjam_id" id="peminjam_id" class="form-select">
                            <option value="">Semua Guru</option>
                            {{-- Anda perlu mengirimkan $daftarGuru dari controller --}}
                            {{-- @foreach ($daftarGuru as $guru)
                                <option value="{{ $guru->id }}"
                                    {{ request('peminjam_id') == $guru->id ? 'selected' : '' }}>
                                    {{ $guru->username }}
                                </option>
                            @endforeach --}}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="search" class="form-label">Pencarian:</label>
                        <input type="text" name="search" id="search" class="form-control"
                            value="{{ request('search') }}" placeholder="Tujuan, nama barang, kode inventaris">
                    </div>
                    <div class="col-md-3">
                        <label class="d-block" style="visibility: hidden;">Tombol Aksi</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-filter-outline me-1"></i> Filter
                            </button>
                            <a href="{{ route('peminjaman.index') }}" class="btn btn-secondary">
                                <i class="mdi mdi-refresh me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" width="5%">#</th>
                                <th>Tgl Pengajuan</th>
                                <th>Peminjam (Guru)</th>
                                <th>Tujuan Peminjaman</th>
                                <th>Status</th>
                                <th>Jumlah Barang</th>
                                <th>Tgl Rencana Pinjam</th>
                                <th>Tgl Rencana Kembali</th>
                                {{-- <th>Ruangan Tujuan</th> --}}
                                <th>Diproses Oleh</th>
                                <th class="text-center" width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($peminjamans as $peminjaman)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration + $peminjamans->firstItem() - 1 }}</td>
                                    <td>{{ \Carbon\Carbon::parse($peminjaman->tanggal_pengajuan)->format('d/m/Y H:i') }}
                                    </td>
                                    <td>{{ $peminjaman->guru->username ?? 'N/A' }}</td>
                                    <td>
                                        <div class="fw-bold">{{ Str::limit($peminjaman->tujuan_peminjaman, 40) }}</div>
                                        @if ($peminjaman->catatan_peminjam)
                                            <small class="text-muted" data-bs-toggle="tooltip"
                                                title="{{ $peminjaman->catatan_peminjam }}">
                                                {{ Str::limit($peminjaman->catatan_peminjam, 30) }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        {{-- Menggunakan konstanta status dari Model Peminjaman untuk badge --}}
                                        @php
                                            $statusClass = '';
                                            switch ($peminjaman->status) {
                                                case \App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN:
                                                    $statusClass = 'bg-warning text-dark';
                                                    break;
                                                case \App\Models\Peminjaman::STATUS_DISETUJUI:
                                                case \App\Models\Peminjaman::STATUS_SEDANG_DIPINJAM:
                                                case \App\Models\Peminjaman::STATUS_SELESAI:
                                                    $statusClass = 'bg-success';
                                                    break;
                                                case \App\Models\Peminjaman::STATUS_DITOLAK:
                                                case \App\Models\Peminjaman::STATUS_TERLAMBAT:
                                                    $statusClass = 'bg-danger';
                                                    break;
                                                case \App\Models\Peminjaman::STATUS_DIBATALKAN:
                                                    $statusClass = 'bg-secondary';
                                                    break;
                                                case \App\Models\Peminjaman::STATUS_MENUNGGU_VERIFIKASI_KEMBALI:
                                                case \App\Models\Peminjaman::STATUS_SEBAGIAN_DIAJUKAN_KEMBALI:
                                                    $statusClass = 'bg-info';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-light text-dark';
                                                    break;
                                            }
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ $peminjaman->status }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark">{{ $peminjaman->detailPeminjaman->count() }}
                                            unit</span>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($peminjaman->tanggal_rencana_pinjam)->format('d/m/Y') }}
                                    </td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($peminjaman->tanggal_rencana_kembali)->format('d/m/Y') }}
                                        @if (
                                            \Carbon\Carbon::parse($peminjaman->tanggal_rencana_kembali)->isPast() &&
                                                $peminjaman->status == \App\Models\Peminjaman::STATUS_SEDANG_DIPINJAM)
                                            <br><small class="text-danger fw-bold">Terlambat</small>
                                        @endif
                                    </td>
                                    {{-- <td>{{ $peminjaman->ruanganTujuanPeminjaman->nama_ruangan ?? '-' }}</td> --}}
                                    <td>
                                        @if ($peminjaman->operatorProses)
                                            {{ $peminjaman->operatorProses->username }}
                                        @elseif($peminjaman->disetujui_oleh)
                                            {{ \App\Models\User::find($peminjaman->disetujui_oleh)->username ?? 'N/A' }}
                                        @elseif($peminjaman->ditolak_oleh)
                                            {{ \App\Models\User::find($peminjaman->ditolak_oleh)->username ?? 'N/A' }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{-- Tombol aksi utama adalah ke halaman detail/kelola --}}
                                        <a href="{{ route('peminjaman.show', $peminjaman->id) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="mdi mdi-pencil-box-outline"></i> Kelola
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="mdi mdi-inbox-outline mdi-48px text-muted mb-2"></i>
                                            <h6 class="text-muted">Tidak ada data peminjaman</h6>
                                            <p class="text-muted mb-0">Belum ada pengajuan peminjaman di sistem atau sesuai
                                                filter Anda.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($peminjamans->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $peminjamans->appends(request()->query())->links() }} {{-- Mempertahankan query string filter saat paginasi --}}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // Auto submit form on select change
            document.getElementById('status_peminjaman').addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });

            // Jika Anda menambahkan filter peminjam_id
            // document.getElementById('peminjam_id').addEventListener('change', function() {
            //     document.getElementById('filterForm').submit();
            // });

            // Add search functionality with debounce
            let searchTimeout;
            const searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        // Submit jika panjang karakter >= 3 atau jika field kosong (untuk clear search)
                        if (this.value.length >= 3 || this.value.length === 0) {
                            document.getElementById('filterForm').submit();
                        }
                    }, 700); // Waktu tunggu 700ms sebelum submit
                });
            }
        });
    </script>
@endpush
