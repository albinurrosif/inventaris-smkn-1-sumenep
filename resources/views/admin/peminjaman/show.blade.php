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
                        <p class="card-text"><strong>Status Persetujuan:</strong>
                            @if ($peminjaman->status_persetujuan === 'menunggu_verifikasi')
                                <span class="badge bg-warning text-dark">Menunggu Verifikasi</span>
                            @elseif ($peminjaman->status_persetujuan === 'diproses')
                                <span class="badge bg-info">Diproses</span>
                            @elseif ($peminjaman->status_persetujuan === 'disetujui')
                                <span class="badge bg-success">Disetujui</span>
                            @elseif ($peminjaman->status_persetujuan === 'ditolak')
                                <span class="badge bg-danger">Ditolak</span>
                            @elseif ($peminjaman->status_persetujuan === 'sebagian_disetujui')
                                <span class="badge bg-primary">Sebagian Disetujui</span>
                            @endif
                        </p>
                        <p class="card-text"><strong>Status Pengambilan:</strong>
                            @if ($peminjaman->status_pengambilan === 'belum_diambil')
                                <span class="badge bg-secondary">Belum Diambil</span>
                            @elseif ($peminjaman->status_pengambilan === 'sebagian_diambil')
                                <span class="badge bg-info">Sebagian Diambil</span>
                            @elseif ($peminjaman->status_pengambilan === 'sudah_diambil')
                                <span class="badge bg-success">Sudah Diambil</span>
                            @endif
                        </p>
                        <p class="card-text"><strong>Status Pengembalian:</strong>
                            @if ($peminjaman->status_pengembalian === 'belum_dikembalikan')
                                <span class="badge bg-secondary">Belum Dikembalikan</span>
                            @elseif ($peminjaman->status_pengembalian === 'sebagian_dikembalikan')
                                <span class="badge bg-info">Sebagian Dikembalikan</span>
                            @elseif ($peminjaman->status_pengembalian === 'sudah_dikembalikan')
                                <span class="badge bg-success">Sudah Dikembalikan</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        @if ($peminjaman->pengajuanDisetujuiOleh)
                            <p class="card-text"><strong>Disetujui Oleh:</strong>
                                {{ $peminjaman->pengajuanDisetujuiOleh->name }}</p>
                            <p class="card-text"><strong>Tanggal Disetujui:</strong>
                                {{ $peminjaman->tanggal_disetujui ? \Carbon\Carbon::parse($peminjaman->tanggal_disetujui)->translatedFormat('d M Y H:i') : '-' }}
                            </p>
                        @endif

                        @if ($peminjaman->pengajuanDitolakOleh)
                            <p class="card-text"><strong>Ditolak Oleh:</strong>
                                {{ $peminjaman->pengajuanDitolakOleh->name }}</p>
                            <p class="card-text"><strong>Tanggal Ditolak:</strong>
                                {{ $peminjaman->tanggal_ditolak ? \Carbon\Carbon::parse($peminjaman->tanggal_ditolak)->translatedFormat('d M Y H:i') : '-' }}
                            </p>
                        @endif

                        @if ($peminjaman->tanggal_semua_diambil)
                            <p class="card-text"><strong>Tanggal Pengambilan Lengkap:</strong>
                                {{ \Carbon\Carbon::parse($peminjaman->tanggal_semua_diambil)->translatedFormat('d M Y H:i') }}
                            </p>
                        @endif

                        @if ($peminjaman->tanggal_selesai)
                            <p class="card-text"><strong>Tanggal Selesai:</strong>
                                {{ \Carbon\Carbon::parse($peminjaman->tanggal_selesai)->translatedFormat('d M Y H:i') }}
                            </p>
                        @endif

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
                                    <th>Diproses Oleh</th>
                                    <th>Keterlambatan</th>
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
                                            @elseif ($detail->status_pengembalian === 'rusak')
                                                <span class="badge bg-danger">Rusak</span>
                                            @elseif ($detail->status_pengembalian === 'hilang')
                                                <span class="badge bg-danger">Hilang</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($detail->disetujuiOleh)
                                                <p class="mb-0">Disetujui: {{ $detail->disetujuiOleh->name }}</p>
                                            @endif
                                            @if ($detail->ditolakOleh)
                                                <p class="mb-0">Ditolak: {{ $detail->ditolakOleh->name }}</p>
                                            @endif
                                            @if ($detail->pengambilanDikonfirmasiOleh)
                                                <p class="mb-0">Pengambilan:
                                                    {{ $detail->pengambilanDikonfirmasiOleh->name }}</p>
                                            @endif
                                            @if ($detail->disetujuiOlehPengembalian)
                                                <p class="mb-0">Kembali: {{ $detail->disetujuiOlehPengembalian->name }}
                                                </p>
                                            @endif
                                            @if ($detail->diverifikasiOlehPengembalian)
                                                <p class="mb-0">Verifikasi:
                                                    {{ $detail->diverifikasiOlehPengembalian->name }}</p>
                                            @endif
                                            @if (
                                                !$detail->disetujuiOleh &&
                                                    !$detail->ditolakOleh &&
                                                    !$detail->pengambilanDikonfirmasiOleh &&
                                                    !$detail->disetujuiOlehPengembalian &&
                                                    !$detail->diverifikasiOlehPengembalian)
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if ($detail->is_terlambat)
                                                <span class="badge bg-danger">Terlambat
                                                    {{ $detail->jumlah_hari_terlambat }} hari</span>
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
                                <td>Pengajuan</td>
                                <td>{{ \Carbon\Carbon::parse($peminjaman->tanggal_pengajuan)->translatedFormat('d M Y H:i') }}
                                </td>
                                <td>{{ $peminjaman->peminjam->name }}</td>
                                <td>{{ $peminjaman->keterangan ?? '-' }}</td>
                            </tr>
                            @if ($peminjaman->pengajuanDisetujuiOleh)
                                <tr>
                                    <td>Disetujui</td>
                                    <td>{{ $peminjaman->tanggal_disetujui ? \Carbon\Carbon::parse($peminjaman->tanggal_disetujui)->translatedFormat('d M Y H:i') : '-' }}
                                    </td>
                                    <td>{{ $peminjaman->pengajuanDisetujuiOleh->name }}</td>
                                    <td>-</td>
                                </tr>
                            @endif
                            @if ($peminjaman->pengajuanDitolakOleh)
                                <tr>
                                    <td>Ditolak</td>
                                    <td>{{ $peminjaman->tanggal_ditolak ? \Carbon\Carbon::parse($peminjaman->tanggal_ditolak)->translatedFormat('d M Y H:i') : '-' }}
                                    </td>
                                    <td>{{ $peminjaman->pengajuanDitolakOleh->name }}</td>
                                    <td>-</td>
                                </tr>
                            @endif
                            @if ($peminjaman->tanggal_semua_diambil)
                                <tr>
                                    <td>Sudah Diambil</td>
                                    <td>{{ \Carbon\Carbon::parse($peminjaman->tanggal_semua_diambil)->translatedFormat('d M Y H:i') }}
                                    </td>
                                    <td>-</td>
                                    <td>-</td>
                                </tr>
                            @endif
                            @if ($peminjaman->tanggal_selesai)
                                <tr>
                                    <td>Selesai</td>
                                    <td>{{ \Carbon\Carbon::parse($peminjaman->tanggal_selesai)->translatedFormat('d M Y H:i') }}
                                    </td>
                                    <td>-</td>
                                    <td>-</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Buttons for Actions -->
        <div class="mb-4">
            <div class="d-flex gap-2">
                <a href="{{ route('admin.peminjaman.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>

                @if ($peminjaman->status_persetujuan === 'menunggu_verifikasi')
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                        <i class="bi bi-check-circle"></i> Setujui
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bi bi-x-circle"></i> Tolak
                    </button>
                @endif

                <!-- Add more action buttons as needed based on status -->
            </div>
        </div>
    </div>

    <!-- Modal templates as needed based on actions -->
@endsection
