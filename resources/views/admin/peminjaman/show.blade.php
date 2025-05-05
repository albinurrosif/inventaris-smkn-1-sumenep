@extends('layouts.app')

@section('title', 'Detail Peminjaman')

@section('content')
    <div class="container-fluid">
        <h4 class="mb-3">Detail Peminjaman</h4>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Informasi Peminjaman</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p class="card-text"><strong>Peminjam:</strong> {{ $peminjaman->peminjam->name }}</p>
                        <p class="card-text"><strong>Tanggal Pengajuan:</strong>
                            {{ \Carbon\Carbon::parse($peminjaman->tanggal_pengajuan)->translatedFormat('d M Y H:i') }}</p>
                        <p class="card-text"><strong>Status:</strong>
                            @if ($peminjaman->status_pengajuan === 'menunggu')
                                <span class="badge bg-warning text-dark">Menunggu</span>
                            @elseif ($peminjaman->status_pengajuan === 'diajukan')
                                <span class="badge bg-warning text-dark">Diajukan</span>
                            @elseif ($peminjaman->status_pengajuan === 'disetujui')
                                <span class="badge bg-success">Disetujui</span>
                            @elseif ($peminjaman->status_pengajuan === 'ditolak')
                                <span class="badge bg-danger">Ditolak</span>
                            @elseif ($peminjaman->status_pengajuan === 'dipinjam')
                                <span class="badge bg-info">Dipinjam</span>
                            @elseif ($peminjaman->status_pengajuan === 'menunggu_verifikasi')
                                <span class="badge bg-secondary">Menunggu Verifikasi</span>
                            @elseif ($peminjaman->status_pengajuan === 'selesai')
                                <span class="badge bg-success">Selesai</span>
                            @elseif ($peminjaman->status_pengajuan === 'dibatalkan')
                                <span class="badge bg-secondary">Dibatalkan</span>
                            @endif
                        </p>
                        @if ($peminjaman->diproses_oleh)
                            <p class="card-text"><strong>Diproses Oleh:</strong> {{ $peminjaman->diprosesOleh->name }}</p>
                            <p class="card-text"><strong>Tanggal Diproses:</strong>
                                {{ $peminjaman->tanggal_proses ? \Carbon\Carbon::parse($peminjaman->tanggal_proses)->translatedFormat('d M Y H:i') : '-' }}
                            </p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <p class="card-text"><strong>Keterangan:</strong> {{ $peminjaman->keterangan ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Detail Barang</h5>
                @if ($peminjaman->detailPeminjaman->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Nama Barang</th>
                                    <th>Jumlah</th>
                                    <th>Ruangan Asal</th>
                                    <th>Ruangan Tujuan</th>
                                    <th>Tanggal Pinjam</th>
                                    <th>Tanggal Kembali</th>
                                    <th>Status Item</th>
                                    <th>Disetujui/Diverifikasi Oleh</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($peminjaman->detailPeminjaman as $key => $detail)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $detail->barang->nama_barang }}</td>
                                        <td>{{ $detail->jumlah_dipinjam }}</td>
                                        <td>{{ $detail->ruanganAsal->nama_ruangan }}</td>
                                        <td>{{ $detail->ruanganTujuan->nama_ruangan }}</td>
                                        <td>{{ \Carbon\Carbon::parse($detail->tanggal_pinjam)->translatedFormat('d M Y') }}
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($detail->tanggal_kembali)->translatedFormat('d M Y') }}
                                        </td>
                                        <td>
                                            @if ($detail->status_pengembalian === 'diajukan')
                                                <span class="badge bg-warning text-dark">Diajukan</span>
                                            @elseif ($detail->status_pengembalian === 'disetujui')
                                                <span class="badge bg-success">Disetujui</span>
                                            @elseif ($detail->status_pengembalian === 'ditolak')
                                                <span class="badge bg-danger">Ditolak</span>
                                            @elseif ($detail->status_pengembalian === 'dipinjam')
                                                <span class="badge bg-info">Dipinjam</span>
                                            @elseif ($detail->status_pengembalian === 'menunggu_verifikasi')
                                                <span class="badge bg-secondary">Menunggu Verifikasi</span>
                                            @elseif ($detail->status_pengembalian === 'dikembalikan')
                                                <span class="badge bg-success">Dikembalikan</span>
                                            @endif

                                            @if ($detail->terlambat)
                                                <br>
                                                <span class="badge bg-danger">Terlambat</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($detail->disetujuiOleh)
                                                {{ $detail->disetujuiOleh->name }}
                                            @elseif ($detail->diverifikasiOleh)
                                                {{ $detail->diverifikasiOleh->name }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p>Tidak ada detail barang yang diajukan.</p>
                @endif
            </div>
        </div>

        {{--  Riwayat Status (Contoh - Jika Diperlukan) --}}
        {{--  
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Riwayat Status</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Oleh</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Diajukan</td>
                                <td>...</td>
                                <td>...</td>
                                <td>...</td>
                            </tr>
                            <tr>
                                <td>Disetujui</td>
                                <td>...</td>
                                <td>...</td>
                                <td>...</td>
                            </tr>
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
        --}}
    </div>
@endsection
