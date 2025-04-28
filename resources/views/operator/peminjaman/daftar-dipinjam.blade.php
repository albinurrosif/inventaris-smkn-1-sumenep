@extends('layouts.app')

@section('title', 'Peminjaman yang Sedang Berlangsung')

@section('content')
<div class="container-fluid">
    <h4 class="mb-3">Daftar Barang Sedang Dipinjam</h4>

    @if (session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: '{{ session('
            success ') }}',
            timer: 3000,
            showConfirmButton: false
        });
    </script>
    @endif

    @if (session('error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: '{{ session('
            error ') }}',
            timer: 3000,
            showConfirmButton: false
        });
    </script>
    @endif

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nama Peminjam</th>
                        <th>Ruangan Tujuan</th>
                        <th>Tanggal Pinjam</th>
                        <th>Tanggal Kembali</th>
                        <th>Status</th>
                        <th>Keterlambatan</th>
                        <th>Jumlah Barang</th>
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
                            <span class="badge bg-info">Sedang Dipinjam</span>
                        </td>
                        <td>
                            @if ($p->terlambat)
                            <span class="badge bg-danger">Terlambat</span>
                            @else
                            <span class="badge bg-success">Tepat Waktu</span>
                            @endif
                        </td>
                        <td>{{ $p->detailPeminjaman->count() }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">Tidak ada peminjaman yang sedang berlangsung.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection