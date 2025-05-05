@extends('layouts.app')

@section('title', 'Detail Peminjaman')

@section('content')
    <div class="container-fluid">
        <h4 class="mb-3">Detail Pengajuan Peminjaman</h4>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Informasi Pengajuan</h5>
                <p class="card-text"><strong>Peminjam:</strong> {{ $peminjaman->peminjam->name }}</p>
                <p class="card-text"><strong>Tanggal Pengajuan:</strong>
                    {{ \Carbon\Carbon::parse($peminjaman->tanggal_pengajuan)->translatedFormat('d M Y H:i') }}</p>
                <p class="card-text"><strong>Status Pengajuan:</strong>
                    @if ($peminjaman->status_pengajuan === 'menunggu')
                        <span class="badge bg-warning text-dark">Menunggu</span>
                    @elseif ($peminjaman->status_pengajuan === 'menunggu_verifikasi')
                        <span class="badge bg-secondary">Menunggu Verifikasi</span>
                    @elseif ($peminjaman->status_pengajuan === 'diajukan')
                        <span class="badge bg-warning text-dark">Diajukan</span>
                    @elseif ($peminjaman->status_pengajuan === 'disetujui')
                        <span class="badge bg-success">Disetujui</span>
                    @elseif ($peminjaman->status_pengajuan === 'ditolak')
                        <span class="badge bg-danger">Ditolak</span>
                    @elseif ($peminjaman->status_pengajuan === 'dipinjam')
                        <span class="badge bg-info">Dipinjam</span>
                    @elseif ($peminjaman->status_pengajuan === 'selesai')
                        <span class="badge bg-success">Selesai</span>
                    @elseif ($peminjaman->status_pengajuan === 'dibatalkan')
                        <span class="badge bg-secondary">Dibatalkan</span>
                    @endif
                </p>
                @if ($peminjaman->diprosesOleh)
                    <p class="card-text"><strong>Diproses Oleh:</strong> {{ $peminjaman->diprosesOleh->name }}</p>
                @endif
                @if ($peminjaman->keterangan)
                    <p class="card-text"><strong>Keterangan Pengajuan:</strong> {{ $peminjaman->keterangan }}</p>
                @endif
            </div>
        </div>

        <h2 class="mt-4">Detail Barang yang Diajukan</h2>
        @if ($peminjaman->detailPeminjaman->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th>Jumlah</th>
                            <th>Ruangan Asal</th>
                            <th>Ruangan Tujuan</th>
                            <th>Tanggal Pinjam</th>
                            <th>Tanggal Kembali</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($peminjaman->detailPeminjaman as $detail)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $detail->barang->nama_barang }}</td>
                                <td>{{ $detail->jumlah_dipinjam }}</td>
                                <td>{{ $detail->ruanganAsal->nama_ruangan }}</td>
                                <td>{{ $detail->ruanganTujuan->nama_ruangan }}</td>
                                <td>{{ \Carbon\Carbon::parse($detail->tanggal_pinjam)->translatedFormat('d M Y H:i') }}</td>
                                <td>{{ \Carbon\Carbon::parse($detail->tanggal_kembali)->translatedFormat('d M Y H:i') }}
                                </td>
                                <td>
                                    @if ($detail->status === 'menunggu')
                                        <span class="badge bg-warning text-dark">Menunggu</span>
                                    @elseif ($detail->status_pengembalian === 'diajukan')
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

                                    @if ($detail->disetujuiOleh)
                                        <br><small>Disetujui oleh: {{ $detail->disetujuiOleh->name }}</small>
                                    @elseif ($detail->diverifikasiOleh)
                                        <br><small>Diverifikasi oleh: {{ $detail->diverifikasiOleh->name }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if ($peminjaman->status_pengajuan === 'menunggu_verifikasi' || $detail->status_pengajuan === 'diajukan')
                                        <form action="{{ route('operator.peminjaman.setujui', $peminjaman->id) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="mdi mdi-check"></i> Setujui
                                            </button>
                                        </form>
                                        <form action="{{ route('operator.peminjaman.tolak', $peminjaman->id) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="mdi mdi-close"></i> Tolak
                                            </button>
                                        </form>
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

        {{-- Modal Verifikasi Pengembalian (Contoh) --}}
        <div class="modal fade" id="verifikasiModal" tabindex="-1" aria-labelledby="verifikasiModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="verifikasiModalLabel">Verifikasi Pengembalian Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Isi dengan formulir atau tampilan untuk verifikasi pengembalian --}}
                        <p>Formulir atau detail verifikasi pengembalian akan ditampilkan di sini.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary">Simpan Verifikasi</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
