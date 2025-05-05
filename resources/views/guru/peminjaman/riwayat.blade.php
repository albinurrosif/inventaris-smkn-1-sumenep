@extends('layouts.app')

@section('title', 'Riwayat Pengajuan Peminjaman')

@section('content')
    <div class="container-fluid">
        <h4 class="mb-3">Riwayat Pengajuan Peminjaman</h4>

        @if (session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: '{{ session('success') }}',
                        timer: 3000,
                        showConfirmButton: false
                    });
                });
            </script>
        @endif

        @if (session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: '{{ session('error') }}',
                        timer: 3000,
                        showConfirmButton: false
                    });
                });
            </script>
        @endif

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tgl Pengajuan</th>
                            <th>Status</th>
                            <th>Jumlah Item</th>
                            <th>Keterangan</th>
                            <th>Tgl Selesai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayatPeminjaman as $p)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ \Carbon\Carbon::parse($p->tanggal_pengajuan)->translatedFormat('d M Y H:i') }}</td>
                                <td>
                                    @if ($p->status === 'menunggu')
                                        <span class="badge bg-warning text-dark">Menunggu</span>
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
                                <td>{{ $p->keterangan ?? '-' }}</td>
                                <td>{{ $p->tanggal_selesai ? \Carbon\Carbon::parse($p->tanggal_selesai)->translatedFormat('d M Y H:i') : '-' }}
                                </td>
                                <td>
                                    <a href="{{ route('guru.peminjaman.show', $p->id) }}" class="btn btn-info btn-sm">
                                        <i class="mdi mdi-eye"></i> Detail
                                    </a>
                                    {{-- Anda bisa menambahkan tombol lain jika diperlukan, misalnya untuk melihat detail pengembalian --}}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada riwayat peminjaman.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">
                    {{ $riwayatPeminjaman->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
