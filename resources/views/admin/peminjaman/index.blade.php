@extends('layouts.app')

@section('title', 'Pantau Peminjaman')

@section('content')
    <div class="container-fluid">
        <h4 class="mb-3">Riwayat Semua Peminjaman</h4>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Peminjam</th>
                            <th>Ruangan</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Kembali</th>
                            <th>Status</th>
                            <th>Diproses Oleh</th>
                            <th>Jumlah Barang</th>
                            <th>Keterlambatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($peminjaman as $p)
                            <tr class="{{ $p->terlambat ? 'table-danger' : '' }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $p->peminjam->name }}</td>
                                <td>{{ $p->ruangan->nama_ruangan }}</td>
                                <td>{{ \Carbon\Carbon::parse($p->tanggal_pinjam)->translatedFormat('d M Y H:i') }}</td>
                                <td>{{ \Carbon\Carbon::parse($p->tanggal_kembali)->translatedFormat('d M Y H:i') }}</td>
                                <td>
                                    <span
                                        class="badge bg-{{ $p->status === 'menunggu'
                                            ? 'warning'
                                            : ($p->status === 'dipinjam'
                                                ? 'info'
                                                : ($p->status === 'menunggu_verifikasi_pengembalian'
                                                    ? 'primary'
                                                    : 'success')) }}">
                                        {{ ucfirst(str_replace('_', ' ', $p->status)) }}
                                    </span>
                                </td>
                                <td>{{ $p->diprosesOleh->name ?? '-' }}</td>
                                <td>{{ $p->detailPeminjaman->count() }}</td>
                                <td>
                                    @if ($p->terlambat)
                                        <span class="badge bg-danger">Terlambat</span>
                                    @else
                                        <span class="badge bg-success">Tepat Waktu</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Belum ada data peminjaman.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
