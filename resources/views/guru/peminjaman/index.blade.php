@extends('layouts.app')

@section('title', 'Daftar Pengajuan Peminjaman')

@section('content')
    <div class="container-fluid">
        <h4 class="mb-3">Daftar Pengajuan Peminjaman</h4>

        <div class="card">
            <div class="card-header">
                <a href="{{ route('guru.peminjaman.create') }}" class="btn btn-primary">
                    <i class="mdi mdi-plus-circle-outline"></i> Ajukan Peminjaman Baru
                </a>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tgl Pengajuan</th>
                            <th>Status</th>
                            <th>Jumlah Item</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($peminjaman as $p)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ \Carbon\Carbon::parse($p->tanggal_pengajuan)->translatedFormat('d M Y H:i') }}</td>
                                <td>
                                    @if ($p->status_pengajuan === 'menunggu')
                                        <span class="badge bg-warning text-dark">Menunggu</span>
                                    @elseif ($p->status_pengajuan === 'diajukan')
                                        <span class="badge bg-warning text-dark">Diajukan</span>
                                    @elseif ($p->status_pengajuan === 'disetujui')
                                        <span class="badge bg-success">Disetujui</span>
                                    @elseif ($p->status_pengajuan === 'ditolak')
                                        <span class="badge bg-danger">Ditolak</span>
                                    @elseif ($p->status_pengajuan === 'dipinjam')
                                        <span class="badge bg-info">Dipinjam</span>
                                    @elseif ($p->status_pengajuan === 'menunggu_verifikasi')
                                        <span class="badge bg-secondary">Menunggu Verifikasi</span>
                                    @elseif ($p->status_pengajuan === 'selesai')
                                        <span class="badge bg-success">Selesai</span>
                                    @endif
                                </td>
                                <td>{{ $p->detailPeminjaman()->count() }}</td>
                                <td>{{ $p->keterangan ?? '-' }}</td>
                                <td>
                                    {{-- Tombol Detail --}}
                                    <a href="{{ route('guru.peminjaman.show', $p->id) }}" class="btn btn-info btn-sm">
                                        <i class="mdi mdi-eye"></i> Detail
                                    </a>
                                    {{-- Tombol Batal --}}
                                    @if ($p->status === 'menunggu')
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#batalkanPeminjaman{{ $p->id }}">
                                            <i class="mdi mdi-cancel"></i> Batal
                                        </button>
                                        {{-- Modal Pembatalan --}}
                                        <div class="modal fade" id="batalkanPeminjaman{{ $p->id }}" tabindex="-1"
                                            aria-hidden="true">
                                            <div class="modal-dialog">
                                                <form action="{{ route('guru.peminjaman.batal', $p->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Konfirmasi Pembatalan</h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Anda yakin ingin membatalkan pengajuan peminjaman ini?</p>
                                                            <ul>
                                                                @foreach ($p->detailPeminjaman as $item)
                                                                    <li>{{ $item->barang->nama_barang }}
                                                                        ({{ $item->jumlah_dipinjam }}
                                                                        pcs)
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-danger">
                                                                <i class="mdi mdi-trash-can-outline"></i> Batalkan
                                                            </button>
                                                            <button type="button" class="btn btn-secondary"
                                                                data-bs-dismiss="modal">Tutup</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Belum ada pengajuan peminjaman.</td>
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
