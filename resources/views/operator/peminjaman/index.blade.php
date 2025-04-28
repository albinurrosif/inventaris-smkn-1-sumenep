@extends('layouts.app')

@section('title', 'Verifikasi Peminjaman')

@section('content')
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

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Daftar Pengajuan Peminjaman</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Peminjam</th>
                                        <th>Ruangan Tujuan</th>
                                        <th>Tanggal Pinjam</th>
                                        <th>Status</th>
                                        <th>Jumlah Barang</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($peminjaman as $p)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $p->peminjam->name ?? '-' }}</td>
                                            <td>{{ $p->ruangan->nama_ruangan ?? '-' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($p->tanggal_pinjam)->translatedFormat('d M Y H:i') }}
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-{{ $p->status === 'menunggu' ? 'warning' : ($p->status === 'dipinjam' ? 'info' : 'success') }}">
                                                    {{ ucfirst($p->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $p->detailPeminjaman->count() }}</td>
                                            <td>
                                                @if ($p->status === 'menunggu')
                                                    <form action="{{ route('operator.peminjaman.verifikasi', $p->id) }}"
                                                        method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm">
                                                            <i class="mdi mdi-check"></i> Verifikasi
                                                        </button>
                                                    </form>
                                                @else
                                                    <button class="btn btn-secondary btn-sm" disabled>
                                                        <i class="mdi mdi-check-all"></i> Sudah Diverifikasi
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">Belum ada pengajuan peminjaman.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
