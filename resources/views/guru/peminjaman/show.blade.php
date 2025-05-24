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
                    @if ($peminjaman->status_persetujuan === 'menunggu_verifikasi')
                        <span class="badge bg-warning text-dark">Menunggu Verifikasi</span>
                    @elseif ($peminjaman->status_persetujuan === 'diproses')
                        <span class="badge bg-info">Diproses</span>
                    @elseif ($peminjaman->status_persetujuan === 'disetujui')
                        <span class="badge bg-success">Disetujui</span>
                    @elseif ($peminjaman->status_persetujuan === 'ditolak')
                        <span class="badge bg-danger">Ditolak</span>
                    @elseif ($peminjaman->status_persetujuan === 'sebagian_disetujui')
                        <span class="badge bg-info">Sebagian Disetujui</span>
                    @endif
                </p>

                @if ($peminjaman->status_pengambilan !== 'belum_diambil')
                    <p class="card-text"><strong>Status Pengambilan:</strong>
                        @if ($peminjaman->status_pengambilan === 'sebagian_diambil')
                            <span class="badge bg-info">Sebagian Diambil</span>
                        @elseif ($peminjaman->status_pengambilan === 'sudah_diambil')
                            <span class="badge bg-success">Sudah Diambil</span>
                            @if ($peminjaman->tanggal_semua_diambil)
                                ({{ \Carbon\Carbon::parse($peminjaman->tanggal_semua_diambil)->translatedFormat('d M Y') }})
                            @endif
                        @endif
                    </p>
                @endif

                @if ($peminjaman->status_pengembalian !== 'belum_dikembalikan')
                    <p class="card-text"><strong>Status Pengembalian:</strong>
                        @if ($peminjaman->status_pengembalian === 'sebagian_dikembalikan')
                            <span class="badge bg-info">Sebagian Dikembalikan</span>
                        @elseif ($peminjaman->status_pengembalian === 'sudah_dikembalikan')
                            <span class="badge bg-success">Sudah Dikembalikan</span>
                            @if ($peminjaman->tanggal_selesai)
                                ({{ \Carbon\Carbon::parse($peminjaman->tanggal_selesai)->translatedFormat('d M Y') }})
                            @endif
                        @endif
                    </p>
                @endif

                @if ($peminjaman->pengajuanDisetujuiOleh)
                    <p class="card-text"><strong>Diproses Oleh:</strong> {{ $peminjaman->pengajuanDisetujuiOleh->name }}</p>
                @endif

                @if ($peminjaman->keterangan)
                    <p class="card-text"><strong>Keterangan Pengajuan:</strong> {{ $peminjaman->keterangan }}</p>
                @endif

                {{-- Tombol Batalkan Pengajuan hanya jika statusnya masih menunggu_verifikasi --}}
                @if ($peminjaman->status_persetujuan === 'menunggu_verifikasi' && Auth::id() == $peminjaman->id_peminjam)
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                        data-bs-target="#batalModal">Batalkan Pengajuan</button>
                @endif
            </div>
        </div>

        <h5 class="mt-4">Detail Barang yang Diajukan</h5>
        @if ($peminjaman->detailPeminjaman->isNotEmpty())
            <div class="table-responsive">
                <table id="detailPeminjamanTable" class="table table-bordered align-middle">
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
                            @if ($peminjaman->status_persetujuan !== 'ditolak')
                                <th>Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($peminjaman->detailPeminjaman as $key => $detail)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $detail->barang->nama_barang }}</td>
                                <td>
                                    {{ $detail->jumlah_dipinjam }}
                                    @if ($detail->jumlah_terverifikasi && $detail->jumlah_terverifikasi != $detail->jumlah_dipinjam)
                                        <br><small class="text-muted">(Terverifikasi:
                                            {{ $detail->jumlah_terverifikasi }})</small>
                                    @endif
                                </td>
                                <td>{{ $detail->ruanganAsal->nama_ruangan }}</td>
                                <td>{{ $detail->ruanganTujuan->nama_ruangan }}</td>
                                <td>{{ \Carbon\Carbon::parse($detail->tanggal_pinjam)->translatedFormat('d M Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($detail->tanggal_kembali)->translatedFormat('d M Y') }}</td>
                                <td>{{ $detail->durasi_pinjam }}</td>
                                <td>
                                    @if ($detail->status_persetujuan === 'menunggu_verifikasi')
                                        <span class="badge bg-warning text-dark">Menunggu Verifikasi</span>
                                    @elseif ($detail->status_persetujuan === 'disetujui')
                                        <span class="badge bg-success">Disetujui</span>
                                    @elseif ($detail->status_persetujuan === 'ditolak')
                                        <span class="badge bg-danger">Ditolak</span>
                                    @endif

                                    @if ($detail->status_pengambilan === 'sudah_diambil')
                                        <br><span class="badge bg-info">Sudah Diambil</span>
                                    @elseif ($detail->status_pengambilan === 'sebagian_diambil')
                                        <br><span class="badge bg-info">Sebagian Diambil</span>
                                    @endif

                                    @if ($detail->status_pengembalian === 'dipinjam')
                                        <br><span class="badge bg-primary">Dipinjam</span>
                                        @if ($detail->terlambat)
                                            <br><span class="badge bg-danger">Terlambat
                                                {{ $detail->jumlah_hari_terlambat }} hari</span>
                                        @endif
                                    @elseif ($detail->status_pengembalian === 'menunggu_verifikasi')
                                        <br><span class="badge bg-secondary">Menunggu Verifikasi</span>
                                    @elseif ($detail->status_pengembalian === 'dikembalikan')
                                        <br><span class="badge bg-success">Dikembalikan</span>
                                    @elseif ($detail->status_pengembalian === 'rusak')
                                        <br><span class="badge bg-danger">Rusak</span>
                                    @elseif ($detail->status_pengembalian === 'hilang')
                                        <br><span class="badge bg-danger">Hilang</span>
                                    @endif

                                    @if ($detail->disetujui_oleh)
                                        <br><small>Disetujui oleh: {{ $detail->disetujuiOleh->name ?? 'Operator' }}</small>
                                    @endif

                                    @if ($detail->ditolak_oleh)
                                        <br><small>Ditolak oleh: {{ $detail->ditolakOleh->name ?? 'Operator' }}</small>
                                    @endif

                                    @if ($detail->pengambilan_dikonfirmasi_oleh)
                                        <br><small>Pengambilan dikonfirmasi oleh:
                                            {{ $detail->pengambilanDikonfirmasiOleh->name ?? 'Operator' }}</small>
                                    @endif

                                    @if ($detail->diverifikasi_oleh_pengembalian)
                                        <br><small>Diverifikasi oleh:
                                            {{ $detail->diverifikasiOlehPengembalian->name ?? 'Operator' }}</small>
                                    @endif
                                </td>
                                @if ($peminjaman->status_persetujuan !== 'ditolak')
                                    <td>
                                        {{-- Tombol untuk ajukan pengembalian --}}
                                        @if (
                                            $detail->status_persetujuan === 'disetujui' &&
                                                in_array($detail->status_pengambilan, ['sudah_diambil', 'sebagian_diambil']) &&
                                                $detail->status_pengembalian === 'dipinjam')
                                            <form action="{{ route('guru.peminjaman.ajukanPengembalian', $detail->id) }}"
                                                method="POST" class="mb-2">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">Ajukan
                                                    Pengembalian</button>
                                            </form>
                                        @endif

                                        {{-- Tombol untuk ajukan perpanjangan --}}
                                        @if (
                                            $detail->status_persetujuan === 'disetujui' &&
                                                in_array($detail->status_pengambilan, ['sudah_diambil', 'sebagian_diambil']) &&
                                                $detail->status_pengembalian === 'dipinjam' &&
                                                $detail->dapat_diperpanjang &&
                                                !$detail->diperpanjang)
                                            <form action="{{ route('guru.peminjaman.ajukanPerpanjangan', $detail->id) }}"
                                                method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-info">Ajukan
                                                    Perpanjangan</button>
                                            </form>
                                        @endif

                                        {{-- Tombol untuk hapus item (hanya untuk status menunggu_verifikasi) --}}
                                        @if ($detail->status_persetujuan === 'menunggu_verifikasi')
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                                data-bs-target="#hapusItemModal{{ $detail->id }}">Hapus Item</button>

                                            {{-- Modal Hapus Item --}}
                                            <div class="modal fade" id="hapusItemModal{{ $detail->id }}" tabindex="-1"
                                                aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Hapus Item Peminjaman</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <form
                                                            action="{{ route('guru.peminjaman.destroy', $peminjaman->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="detail_id"
                                                                value="{{ $detail->id }}">
                                                            <div class="modal-body">
                                                                <p>Anda yakin ingin menghapus item
                                                                    {{ $detail->barang->nama_barang }} dari pengajuan
                                                                    peminjaman ini?</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit" class="btn btn-danger">Hapus</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p>Tidak ada detail barang yang diajukan.</p>
        @endif

        {{-- Modal Batalkan Peminjaman --}}
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
                            <p class="text-danger">Semua item yang diajukan akan dihapus.</p>
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

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#detailPeminjamanTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json'
                }
            });
        });
    </script>
@endpush
