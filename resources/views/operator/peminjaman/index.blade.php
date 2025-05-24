@extends('layouts.app')

@section('title', 'Daftar Peminjaman')

@section('content')
    <div class="container-fluid">
        <h4 class="mb-3">Daftar Pengajuan Peminjaman</h4>

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
                <table id="peminjamanTable" class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Peminjam</th>
                            <th>Tgl Pengajuan</th>
                            <th>Status Persetujuan</th>
                            <th>Status Pengambilan</th>
                            <th>Jumlah Item</th>
                            <th>Tgl Selesai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($peminjaman as $p)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $p->peminjam->name }}</td>
                                <td>{{ \Carbon\Carbon::parse($p->tanggal_pengajuan)->translatedFormat('d M Y H:i') }}</td>
                                <td>
                                    @if ($p->status_persetujuan === 'menunggu_verifikasi')
                                        <span class="badge bg-warning text-dark">Menunggu Verifikasi</span>
                                    @elseif ($p->status_persetujuan === 'diproses')
                                        <span class="badge bg-info">Diproses</span>
                                    @elseif ($p->status_persetujuan === 'disetujui')
                                        <span class="badge bg-success">Disetujui</span>
                                    @elseif ($p->status_persetujuan === 'ditolak')
                                        <span class="badge bg-danger">Ditolak</span>
                                    @elseif ($p->status_persetujuan === 'sebagian_disetujui')
                                        <span class="badge bg-primary">Sebagian Disetujui</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($p->status_pengambilan === 'belum_diambil')
                                        <span class="badge bg-secondary">Belum Diambil</span>
                                    @elseif ($p->status_pengambilan === 'sebagian_diambil')
                                        <span class="badge bg-info">Sebagian Diambil</span>
                                    @elseif ($p->status_pengambilan === 'sudah_diambil')
                                        <span class="badge bg-success">Sudah Diambil</span>
                                    @endif
                                </td>
                                <td>{{ $p->detailPeminjaman()->count() }}</td>
                                <td>{{ $p->tanggal_selesai ? \Carbon\Carbon::parse($p->tanggal_selesai)->translatedFormat('d M Y H:i') : '-' }}
                                </td>
                                <td>
                                    <a href="{{ route('operator.peminjaman.show', $p->id) }}" class="btn btn-info btn-sm">
                                        <i class="mdi mdi-eye"></i> Detail
                                    </a>
                                    {{-- Tambahkan tombol aksi lain yang relevan untuk operator di sini --}}
                                </td>
                            </tr>
                        @empty
                            {{-- <tr>
                                <td colspan="7" class="text-center">Belum ada pengajuan peminjaman.</td>
                            </tr> --}}
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

@push('scripts')
    <script>
        $(document).ready(function() {
            // Hanya inisialisasi DataTables jika ada data
            if ($('#peminjamanTable tbody tr').length > 1) {
                $('#peminjamanTable').DataTable({
                    responsive: true,
                    // Menonaktifkan fitur bawaan DataTables yang mungkin berkonflik dengan paginasi Laravel
                    paging: true,
                    ordering: true,
                    info: true,
                    searching: true
                });
            }
        });
    </script>
@endpush
