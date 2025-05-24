@extends('layouts.app')

@section('title', 'Daftar Peminjaman')

@section('content')
    <div class="container-fluid">
        <h4 class="mb-3">Data Peminjaman</h4>

        <!-- Ringkasan Card -->
        {{-- <div class="row mb-4">
            <div class="col-xl-2 col-md-4 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Peminjaman</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $ringkasan['total'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="mdi mdi-book-multiple fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 mb-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Menunggu Verifikasi
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $ringkasan['menunggu_verifikasi'] }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="mdi mdi-clock-outline fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 mb-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Disetujui</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $ringkasan['disetujui'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="mdi mdi-check-circle-outline fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 mb-3">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Ditolak</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $ringkasan['ditolak'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="mdi mdi-close-circle-outline fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 mb-3">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Belum Dikembalikan</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $ringkasan['belum_dikembalikan'] }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="mdi mdi-calendar-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 mb-3">
                <div class="card border-left-danger shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Terlambat</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $ringkasan['terlambat'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="mdi mdi-alert-circle-outline fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}

        <div class="card">
            <div class="card-body table-responsive">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                    <h4 class="card-title mb-0">Data Peminjaman</h4>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <a href="{{ route('admin.peminjaman.report') }}" class="btn btn-outline-primary btn-md me-2">
                            <i class="mdi mdi-chart-bar me-1"></i> Laporan
                        </a>
                        {{-- <a href="{{ route('admin.peminjaman.overdue') }}" class="btn btn-outline-danger btn-md me-2">
                            <i class="mdi mdi-alert-circle me-1"></i> Peminjaman Terlambat
                        </a> --}}
                        <div class="dropdown">
                            <button class="btn btn-outline-success dropdown-toggle" type="button" id="exportDropdown"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="mdi mdi-file-export me-1"></i> Export
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                                <li><a class="dropdown-item" href="">PDF</a></li>
                                <li><a class="dropdown-item" href="">Excel</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.peminjaman.index') }}" id="filterForm" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="ruangan_asal" class="form-label">Ruangan Asal:</label>
                        <select name="ruangan_asal" id="ruangan_asal" class="form-select">
                            <option value="">Semua</option>
                            @foreach ($ruangan as $r)
                                <option value="{{ $r->id }}"
                                    {{ request('ruangan_asal') == $r->id ? 'selected' : '' }}>
                                    {{ $r->nama_ruangan }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="ruangan_tujuan" class="form-label">Ruangan Tujuan:</label>
                        <select name="ruangan_tujuan" id="ruangan_tujuan" class="form-select">
                            <option value="">Semua</option>
                            @foreach ($ruangan as $r)
                                <option value="{{ $r->id }}"
                                    {{ request('ruangan_tujuan') == $r->id ? 'selected' : '' }}>
                                    {{ $r->nama_ruangan }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="peminjam" class="form-label">Peminjam:</label>
                        <input type="text" name="peminjam" id="peminjam" class="form-control"
                            value="{{ request('peminjam') }}" placeholder="Nama atau ID peminjam">
                    </div>
                    <div class="col-md-3">
                        <label for="barang" class="form-label">Barang:</label>
                        <input type="text" name="barang" id="barang" class="form-control"
                            value="{{ request('barang') }}" placeholder="Nama atau kode barang">
                    </div>
                    <div class="col-md-3">
                        <label for="status_persetujuan" class="form-label">Status Persetujuan:</label>
                        <select name="status_persetujuan" id="status_persetujuan" class="form-select">
                            <option value="">Semua</option>
                            @foreach ($statusPersetujuan as $status)
                                <option value="{{ $status }}"
                                    {{ request('status_persetujuan') == $status ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status_pengambilan" class="form-label">Status Pengambilan:</label>
                        <select name="status_pengambilan" id="status_pengambilan" class="form-select">
                            <option value="">Semua</option>
                            @foreach ($statusPengambilan as $status)
                                <option value="{{ $status }}"
                                    {{ request('status_pengambilan') == $status ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status_pengembalian" class="form-label">Status Pengembalian:</label>
                        <select name="status_pengembalian" id="status_pengembalian" class="form-select">
                            <option value="">Semua</option>
                            @foreach ($statusPengembalian as $status)
                                <option value="{{ $status }}"
                                    {{ request('status_pengembalian') == $status ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="terlambat" class="form-label">Peminjaman Terlambat:</label>
                        <select name="terlambat" id="terlambat" class="form-select">
                            <option value="">Semua</option>
                            <option value="1" {{ request('terlambat') == '1' ? 'selected' : '' }}>Ya</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="tanggal_mulai" class="form-label">Tanggal Pengajuan (Dari):</label>
                        <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="form-control"
                            value="{{ request('tanggal_mulai') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="tanggal_akhir" class="form-label">Tanggal Pengajuan (Sampai):</label>
                        <input type="date" name="tanggal_akhir" id="tanggal_akhir" class="form-control"
                            value="{{ request('tanggal_akhir') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="tanggal_pinjam_mulai" class="form-label">Tanggal Pinjam (Dari):</label>
                        <input type="date" name="tanggal_pinjam_mulai" id="tanggal_pinjam_mulai" class="form-control"
                            value="{{ request('tanggal_pinjam_mulai') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="tanggal_pinjam_akhir" class="form-label">Tanggal Pinjam (Sampai):</label>
                        <input type="date" name="tanggal_pinjam_akhir" id="tanggal_pinjam_akhir" class="form-control"
                            value="{{ request('tanggal_pinjam_akhir') }}">
                    </div>

                    <div class="col-md-6">
                        <label for="sort_by" class="form-label">Urutkan Berdasarkan:</label>
                        <div class="input-group">
                            <select name="sort_by" id="sort_by" class="form-select">
                                <option value="tanggal_pengajuan"
                                    {{ request('sort_by', 'tanggal_pengajuan') == 'tanggal_pengajuan' ? 'selected' : '' }}>
                                    Tanggal Pengajuan</option>
                                <option value="status_persetujuan"
                                    {{ request('sort_by') == 'status_persetujuan' ? 'selected' : '' }}>Status Persetujuan
                                </option>
                                <option value="status_pengambilan"
                                    {{ request('sort_by') == 'status_pengambilan' ? 'selected' : '' }}>Status Pengambilan
                                </option>
                                <option value="status_pengembalian"
                                    {{ request('sort_by') == 'status_pengembalian' ? 'selected' : '' }}>Status Pengembalian
                                </option>
                                <option value="tanggal_disetujui"
                                    {{ request('sort_by') == 'tanggal_disetujui' ? 'selected' : '' }}>Tanggal Disetujui
                                </option>
                                <option value="tanggal_semua_diambil"
                                    {{ request('sort_by') == 'tanggal_semua_diambil' ? 'selected' : '' }}>Tanggal Diambil
                                </option>
                                <option value="tanggal_selesai"
                                    {{ request('sort_by') == 'tanggal_selesai' ? 'selected' : '' }}>Tanggal Selesai
                                </option>
                            </select>
                            <select name="sort_direction" id="sort_direction" class="form-select">
                                <option value="asc" {{ request('sort_direction') == 'asc' ? 'selected' : '' }}>Menaik
                                </option>
                                <option value="desc"
                                    {{ request('sort_direction', 'desc') == 'desc' ? 'selected' : '' }}>Menurun</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="per_page" class="form-label">Tampilkan:</label>
                        <select name="per_page" id="per_page" class="form-select">
                            <option value="10" {{ request('per_page', '10') == '10' ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="d-block" style="visibility: hidden;">Tombol Aksi</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-filter-outline me-1"></i> Filter
                            </button>
                            <a href="{{ route('admin.peminjaman.index') }}" class="btn btn-secondary">
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
                                <th>ID</th>
                                <th>Tgl Pengajuan</th>
                                <th>Peminjam</th>
                                <th>Status Persetujuan</th>
                                <th>Status Pengambilan</th>
                                <th>Status Pengembalian</th>
                                <th>Jumlah Barang</th>
                                <th>Tgl Pinjam</th>
                                <th>Tgl Kembali</th>
                                <th class="text-center" width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($peminjaman as $p)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration + $peminjaman->firstItem() - 1 }}</td>
                                    <td>{{ $p->id }}</td>
                                    <td>{{ \Carbon\Carbon::parse($p->tanggal_pengajuan)->format('d/m/Y H:i') }}</td>
                                    <td>{{ $p->peminjam->name }}</td>
                                    <td>
                                        @if ($p->status_persetujuan === 'menunggu_verifikasi')
                                            <span class="badge bg-warning text-dark">Menunggu Verifikasi</span>
                                        @elseif ($p->status_persetujuan === 'diproses')
                                            <span class="badge bg-info">Diproses</span>
                                        @elseif ($p->status_persetujuan === 'disetujui')
                                            <span class="badge bg-success">Disetujui</span>
                                        @elseif ($p->status_persetujuan === 'ditolak')
                                            <span class="badge bg-danger">Ditolak</span>
                                        @elseif ($p->status_persetujuan === 'sebagian_disetujui')
                                            <span class="badge bg-primary">Sebagian Disetujui</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($p->status_pengambilan === 'belum_diambil')
                                            <span class="badge bg-secondary">Belum Diambil</span>
                                        @elseif ($p->status_pengambilan === 'sebagian_diambil')
                                            <span class="badge bg-info">Sebagian Diambil</span>
                                        @elseif ($p->status_pengambilan === 'sudah_diambil')
                                            <span class="badge bg-success">Sudah Diambil</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($p->status_pengembalian === 'belum_dikembalikan')
                                            <span class="badge bg-secondary">Belum Dikembalikan</span>
                                        @elseif ($p->status_pengembalian === 'sebagian_dikembalikan')
                                            <span class="badge bg-info">Sebagian Dikembalikan</span>
                                        @elseif ($p->status_pengembalian === 'sudah_dikembalikan')
                                            <span class="badge bg-success">Sudah Dikembalikan</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $p->detailPeminjaman->count() }}</td>
                                    <td>
                                        @if ($p->detailPeminjaman->isNotEmpty())
                                            {{ \Carbon\Carbon::parse($p->detailPeminjaman->min('tanggal_pinjam'))->format('d/m/Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($p->detailPeminjaman->isNotEmpty())
                                            {{ \Carbon\Carbon::parse($p->detailPeminjaman->max('tanggal_kembali'))->format('d/m/Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.peminjaman.show', $p->id) }}"
                                            class="btn btn-sm btn-info">
                                            <i class="mdi mdi-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center">Tidak ada data peminjaman yang ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    {{ $peminjaman->links() }}
                </div>
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

            // Date range picker initialization if needed
            if (typeof flatpickr !== 'undefined') {
                flatpickr('#tanggal_mulai', {
                    dateFormat: 'Y-m-d',
                });
                flatpickr('#tanggal_akhir', {
                    dateFormat: 'Y-m-d',
                });
                flatpickr('#tanggal_pinjam_mulai', {
                    dateFormat: 'Y-m-d',
                });
                flatpickr('#tanggal_pinjam_akhir', {
                    dateFormat: 'Y-m-d',
                });
            }
        });
    </script>
@endpush
