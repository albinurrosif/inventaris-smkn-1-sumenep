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
                @if ($peminjaman->prosesor)
                    <p class="card-text"><strong>Diproses Oleh:</strong> {{ $peminjaman->prosesor->name }}</p>
                    <p class="card-text"><strong>Tanggal Diproses:</strong>
                        {{ $peminjaman->tanggal_proses ? \Carbon\Carbon::parse($peminjaman->tanggal_proses)->translatedFormat('d M Y H:i') : 'Belum Diproses' }}
                    </p>
                @endif
                @if ($peminjaman->keterangan)
                    <p class="card-text"><strong>Keterangan Pengajuan:</strong> {{ $peminjaman->keterangan }}</p>
                @endif

                {{-- Tombol Batalkan hanya untuk Guru dan jika statusnya masih 'menunggu' atau 'diajukan' --}}
                @if (in_array($peminjaman->status, ['menunggu', 'diajukan']) && Auth::id() == $peminjaman->id_peminjam)
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                        data-bs-target="#batalModal">Batalkan Pengajuan</button>
                @endif
            </div>
        </div>

        <h5 class="mt-4">Detail Barang yang Diajukan</h5>
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
                            <th>Durasi (Hari)</th>
                            <th>Status Item</th>
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
                                <td>{{ \Carbon\Carbon::parse($detail->tanggal_pinjam)->translatedFormat('d M Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($detail->tanggal_kembali)->translatedFormat('d M Y') }}</td>
                                <td>{{ $detail->durasi_pinjam }}</td>
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
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p>Tidak ada detail barang yang diajukan.</p>
        @endif

        <div class="modal fade" id="batalModal" tabindex="-1" aria-labelledby="batalModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="batalModalLabel">Batalkan Pengajuan Peminjaman</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('guru.peminjaman.destroy', $peminjaman->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="modal-body">
                            <p>Anda yakin ingin membatalkan pengajuan peminjaman ini?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger">Batalkan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
