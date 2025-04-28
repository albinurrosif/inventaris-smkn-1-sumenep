@extends('layouts.app')

@section('title', 'Riwayat Peminjaman')

@section('content') <div class="container-fluid">
        <h4 class="mb-3">Riwayat Peminjaman Saya</h4>

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
                            <th>Ruangan</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Kembali</th>
                            <th>Status</th>
                            <th>Jumlah Barang</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($peminjaman as $p)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
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
                                        {{ ucwords(str_replace('_', ' ', $p->status)) }}
                                    </span>
                                </td>
                                <td>{{ $p->detailPeminjaman->count() }}</td>
                                <td>
                                    {{-- Tombol Detail --}}
                                    <a href="#" class="btn btn-info btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#detailPeminjaman{{ $p->id }}">
                                        <i class="mdi mdi-eye"></i> Detail
                                    </a>

                                    {{-- Tombol Ajukan Pengembalian --}}
                                    @if ($p->status === 'dipinjam')
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#kembalikanPeminjaman{{ $p->id }}">
                                            <i class="mdi mdi-backup-restore"></i> Kembalikan
                                        </button>
                                    @endif

                                    @if ($p->status === 'dipinjam' && $p->dapat_diperpanjang && !$p->detailPeminjaman->first()?->diperpanjang)
                                        <form action="{{ route('peminjaman.perpanjang', $p->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-warning btn-sm">
                                                <i class="mdi mdi-clock-plus-outline"></i> Perpanjang
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>

                            {{-- Modal Detail --}}
                            <div class="modal fade" id="detailPeminjaman{{ $p->id }}" tabindex="-1"
                                aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Detail Peminjaman</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <ul>
                                                @foreach ($p->detailPeminjaman as $d)
                                                    <li>
                                                        <strong>{{ $d->barang->nama_barang }}</strong>
                                                        - {{ $d->jumlah_dipinjam }} pcs
                                                        - Kondisi: {{ $d->kondisi_sebelum }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <p><strong>Keterangan:</strong> {{ $p->keterangan ?? '-' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Modal Ajukan Pengembalian --}}
                            @if ($p->status === 'dipinjam')
                                <div class="modal fade" id="kembalikanPeminjaman{{ $p->id }}" tabindex="-1"
                                    aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <form action="{{ route('peminjaman.kembalikan', $p->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Ajukan Pengembalian</h5>
                                                    <button type="button" class="btn-close"
                                                        data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Silakan konfirmasi bahwa Anda akan mengembalikan barang berikut:</p>
                                                    <ul>
                                                        @foreach ($p->detailPeminjaman as $d)
                                                            <li>{{ $d->barang->nama_barang }} - {{ $d->jumlah_dipinjam }}
                                                                pcs</li>
                                                        @endforeach
                                                    </ul>
                                                    <p>Kondisi barang akan diverifikasi oleh operator.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="mdi mdi-check-circle-outline"></i> Ajukan Pengembalian
                                                    </button>
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Batal</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif

                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Belum ada data peminjaman.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
