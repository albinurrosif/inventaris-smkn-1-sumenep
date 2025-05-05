@extends('layouts.app')

@section('title', 'Peminjaman Berlangsung')

@section('content')
    <div class="container-fluid">
        <h4 class="mb-3">Peminjaman Berlangsung</h4>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Peminjam</th>
                            <th>Tgl Pengajuan</th>
                            <th>Barang</th>
                            <th>Ruangan Tujuan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($peminjamanBerlangsung as $p)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $p->peminjam->name }}</td>
                                <td>{{ \Carbon\Carbon::parse($p->tanggal_pengajuan)->translatedFormat('d M Y H:i') }}</td>
                                <td>
                                    <ul>
                                        @foreach ($p->detailPeminjaman as $detail)
                                            <li>{{ $detail->barang->nama_barang }} ({{ $detail->jumlah_dipinjam }})</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td>
                                    @if ($p->detailPeminjaman->isNotEmpty())
                                        {{ $p->detailPeminjaman->first()->ruanganTujuan->nama_ruangan ?? '-' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('operator.peminjaman.show', $p->id) }}" class="btn btn-info btn-sm">
                                        <i class="mdi mdi-eye"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada peminjaman berlangsung.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">
                    {{ $peminjamanBerlangsung->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
