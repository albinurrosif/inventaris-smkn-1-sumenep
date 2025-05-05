@extends('layouts.app')

@section('title', 'Daftar Peminjaman')

@section('content')
    <div class="container-fluid">
        <h4 class="mb-3">Daftar Peminjaman</h4>

        <div class="card">
            <div class="card-body table-responsive">

                {{--  Filter dan Pengurutan (Contoh) --}}
                <div class="mb-3">
                    <form action="{{ route('admin.peminjaman.index') }}" method="GET" class="row g-3">
                        <div class="col-auto">
                            <label for="filter-status" class="form-label">Filter Status:</label>
                            <select name="status" id="filter-status" class="form-select">
                                <option value="">Semua</option>
                                <option value="menunggu" {{ request('status') == 'menunggu' ? 'selected' : '' }}>Menunggu
                                </option>
                                <option value="diajukan" {{ request('status') == 'diajukan' ? 'selected' : '' }}>Diajukan
                                </option>
                                <option value="disetujui" {{ request('status') == 'disetujui' ? 'selected' : '' }}>Disetujui
                                </option>
                                <option value="ditolak" {{ request('status') == 'ditolak' ? 'selected' : '' }}>Ditolak
                                </option>
                                <option value="dipinjam" {{ request('status') == 'dipinjam' ? 'selected' : '' }}>Dipinjam
                                </option>
                                <option value="menunggu_verifikasi"
                                    {{ request('status') == 'menunggu_verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi
                                </option>
                                <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai
                                </option>
                                <option value="dibatalkan" {{ request('status') == 'dibatalkan' ? 'selected' : '' }}>
                                    Dibatalkan
                                </option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <label for="sort-by" class="form-label">Urutkan Berdasarkan:</label>
                            <select name="sort" id="sort-by" class="form-select">
                                <option value="tanggal_pengajuan"
                                    {{ request('sort') == 'tanggal_pengajuan' ? 'selected' : '' }}>Tanggal Pengajuan
                                </option>
                                <option value="status" {{ request('sort') == 'status' ? 'selected' : '' }}>Status
                                </option>
                                {{--  Tambahkan opsi pengurutan lain jika perlu --}}
                            </select>
                        </div>
                        <div class="col-auto">
                            <label for="sort-direction" class="form-label">Arah Urutan:</label>
                            <select name="direction" id="sort-direction" class="form-select">
                                <option value="asc" {{ request('direction') == 'asc' ? 'selected' : '' }}>Menaik
                                </option>
                                <option value="desc" {{ request('direction') == 'desc' ? 'selected' : '' }}>Menurun
                                </option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary mt-4">
                                <i class="mdi mdi-filter-outline"></i> Filter & Urutkan
                            </button>
                        </div>
                    </form>
                </div>

                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tgl Pengajuan</th>
                            <th>Peminjam</th>
                            <th>Status</th>
                            <th>Jumlah Item</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Kembali</th>
                            <th>Diproses Oleh</th>
                            <th>Tgl Selesai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($peminjaman as $p)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ \Carbon\Carbon::parse($p->tanggal_pengajuan)->translatedFormat('d M Y H:i') }}</td>
                                <td>{{ $p->peminjam->name }}</td>
                                <td>
                                    @if ($p->status === 'menunggu')
                                        <span class="badge bg-warning text-dark">Menunggu</span>
                                    @elseif ($p->status === 'diajukan')
                                        <span class="badge bg-warning text-dark">Diajukan</span>
                                    @elseif ($p->status === 'disetujui')
                                        <span class="badge bg-success">Disetujui</span>
                                    @elseif ($p->status === 'ditolak')
                                        <span class="badge bg-danger">Ditolak</span>
                                    @elseif ($p->status === 'dipinjam')
                                        <span class="badge bg-info">Dipinjam</span>
                                    @elseif ($p->status === 'menunggu_verifikasi')
                                        <span class="badge bg-secondary">Menunggu Verifikasi</span>
                                    @elseif ($p->status === 'selesai')
                                        <span class="badge bg-success">Selesai</span>
                                    @elseif ($p->status === 'dibatalkan')
                                        <span class="badge bg-secondary">Dibatalkan</span>
                                    @endif
                                </td>
                                <td>{{ $p->detailPeminjaman()->count() }}</td>
                                <td>
                                    @if ($p->detailPeminjaman->isNotEmpty())
                                        {{ \Carbon\Carbon::parse($p->detailPeminjaman->min('tanggal_pinjam'))->translatedFormat('d M Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($p->detailPeminjaman->isNotEmpty())
                                        {{ \Carbon\Carbon::parse($p->detailPeminjaman->min('tanggal_kembali'))->translatedFormat('d M Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($p->diproses_oleh)
                                        {{ $p->diprosesOleh->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $p->tanggal_selesai ? \Carbon\Carbon::parse($p->tanggal_selesai)->translatedFormat('d M Y H:i') : '-' }}
                                </td>
                                <td>
                                    <a href="{{ route('admin.peminjaman.show', $p->id) }}" class="btn btn-info btn-sm">
                                        <i class="mdi mdi-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center">Tidak ada data peminjaman.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">
                    {{ $peminjaman->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
