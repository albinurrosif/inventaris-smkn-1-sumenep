@extends('layouts.app')

@section('title', 'Detail Peminjaman')

@section('content')
    <div class="container-fluid">
        <h4 class="mb-3">Detail Pengajuan Peminjaman</h4>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Informasi Pengajuan</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p class="card-text"><strong>Peminjam:</strong> {{ $peminjaman->peminjam->name }}</p>
                        <p class="card-text"><strong>Tanggal Pengajuan:</strong>
                            {{ \Carbon\Carbon::parse($peminjaman->tanggal_pengajuan)->translatedFormat('d M Y H:i') }}</p>
                        <p class="card-text"><strong>Status Persetujuan:</strong>
                            @if ($peminjaman->status_persetujuan === 'menunggu_verifikasi')
                                <span class="badge bg-secondary">Menunggu Verifikasi</span>
                            @elseif ($peminjaman->status_persetujuan === 'disetujui')
                                <span class="badge bg-success">Disetujui</span>
                            @elseif ($peminjaman->status_persetujuan === 'ditolak')
                                <span class="badge bg-danger">Ditolak</span>
                            @elseif ($peminjaman->status_persetujuan === 'sebagian_disetujui')
                                <span class="badge bg-warning text-dark">Sebagian Disetujui</span>
                            @elseif ($peminjaman->status_persetujuan === 'diproses')
                                <span class="badge bg-info">Diproses</span>
                            @else
                                <span class="badge bg-secondary">{{ $peminjaman->status_persetujuan }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="card-text"><strong>Status Pengambilan:</strong>
                            @if ($peminjaman->status_pengambilan === 'belum_diambil')
                                <span class="badge bg-warning text-dark">Belum Diambil</span>
                            @elseif ($peminjaman->status_pengambilan === 'sebagian_diambil')
                                <span class="badge bg-info">Sebagian Diambil</span>
                            @elseif ($peminjaman->status_pengambilan === 'sudah_diambil')
                                <span class="badge bg-success">Sudah Diambil</span>
                            @else
                                <span class="badge bg-secondary">{{ $peminjaman->status_pengambilan }}</span>
                            @endif
                        </p>
                        <p class="card-text"><strong>Status Pengembalian:</strong>
                            @if ($peminjaman->status_pengembalian === 'belum_dikembalikan')
                                <span class="badge bg-warning text-dark">Belum Dikembalikan</span>
                            @elseif ($peminjaman->status_pengembalian === 'sebagian_dikembalikan')
                                <span class="badge bg-info">Sebagian Dikembalikan</span>
                            @elseif ($peminjaman->status_pengembalian === 'sudah_dikembalikan')
                                <span class="badge bg-success">Sudah Dikembalikan</span>
                            @else
                                <span
                                    class="badge bg-secondary">{{ $peminjaman->status_pengembalian ?? 'Belum Ada' }}</span>
                            @endif
                        </p>
                        @if ($peminjaman->pengajuanDisetujuiOleh)
                            <p class="card-text"><strong>Diproses Oleh:</strong>
                                {{ $peminjaman->pengajuanDisetujuiOleh->name }}</p>
                        @endif
                    </div>
                </div>
                @if ($peminjaman->keterangan)
                    <p class="card-text"><strong>Keterangan Pengajuan:</strong> {{ $peminjaman->keterangan }}</p>
                @endif

                @if ($peminjaman->status_persetujuan === 'menunggu_verifikasi')
                    <div class="mt-3">
                        <form action="{{ route('operator.peminjaman.setujui-semua', $peminjaman->id) }}" method="POST"
                            class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="mdi mdi-check-all"></i> Setujui Semua Item
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <h5 class="mt-4">Detail Barang yang Diajukan</h5>
        @if ($peminjaman->detailPeminjaman->isNotEmpty())
            <div class="table-responsive">
                <table id="detailPeminjamanTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th>Jumlah</th>
                            <th>Ruangan Asal</th>
                            <th>Ruangan Tujuan</th>
                            <th>Tanggal Pinjam</th>
                            <th>Tanggal Kembali</th>
                            <th>Status</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($peminjaman->detailPeminjaman as $detail)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $detail->barang->nama_barang }}</td>
                                <td>{{ $detail->jumlah_dipinjam }}</td>
                                <td>{{ $detail->ruanganAsal->nama_ruangan }}</td>
                                <td>{{ $detail->ruanganTujuan->nama_ruangan }}</td>
                                <td>{{ \Carbon\Carbon::parse($detail->tanggal_pinjam)->translatedFormat('d M Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($detail->tanggal_kembali)->translatedFormat('d M Y') }}</td>
                                <td>
                                    <div class="mb-1">
                                        <strong>Persetujuan:</strong>
                                        @if ($detail->status_persetujuan === 'menunggu_verifikasi')
                                            <span class="badge bg-secondary">Menunggu</span>
                                        @elseif ($detail->status_persetujuan === 'disetujui')
                                            <span class="badge bg-success">Disetujui</span>
                                            @if ($detail->disetujui_oleh)
                                                oleh {{ optional(App\Models\User::find($detail->disetujui_oleh))->name }}
                                            @endif
                                        @elseif ($detail->status_persetujuan === 'ditolak')
                                            <span class="badge bg-danger">Ditolak</span>
                                            @if ($detail->ditolak_oleh)
                                                oleh {{ optional(App\Models\User::find($detail->ditolak_oleh))->name }}
                                            @endif
                                        @endif
                                    </div>

                                    @if ($detail->status_persetujuan === 'disetujui')
                                        <div class="mb-1">
                                            <strong>Pengambilan:</strong>
                                            @if ($detail->status_pengambilan === 'belum_diambil')
                                                <span class="badge bg-warning text-dark">Belum Diambil</span>
                                            @elseif ($detail->status_pengambilan === 'sudah_diambil')
                                                <span class="badge bg-success">Sudah Diambil</span>
                                            @endif
                                        </div>
                                    @endif

                                    @if ($detail->status_pengambilan === 'sudah_diambil')
                                        <div>
                                            <strong>Pengembalian:</strong>
                                            @if ($detail->status_pengembalian === 'dipinjam')
                                                <span class="badge bg-info">Dipinjam</span>
                                            @elseif ($detail->status_pengembalian === 'dikembalikan')
                                                <span class="badge bg-success">Dikembalikan</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if ($detail->status_persetujuan === 'menunggu_verifikasi')
                                        <form action="{{ route('operator.peminjaman.setujui-item', $detail->id) }}"
                                            method="POST" class="d-inline mb-1">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="mdi mdi-check"></i> Setujui
                                            </button>
                                        </form>
                                        <form action="{{ route('operator.peminjaman.tolak-item', $detail->id) }}"
                                            method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="mdi mdi-close"></i> Tolak
                                            </button>
                                        </form>
                                    @elseif ($detail->status_persetujuan === 'disetujui' && $detail->status_pengambilan === 'belum_diambil')
                                        <button type="button" class="btn btn-sm btn-primary konfirmasi-pengambilan"
                                            data-bs-toggle="modal" data-bs-target="#konfirmasiPengambilanModal"
                                            data-detail-id="{{ $detail->id }}">
                                            <i class="mdi mdi-hand"></i> Konfirmasi Pengambilan
                                        </button>
                                    @elseif ($detail->status_pengambilan === 'sudah_diambil' && $detail->status_pengembalian === 'dipinjam')
                                        <button type="button" class="btn btn-sm btn-info konfirmasi-pengembalian"
                                            data-bs-toggle="modal" data-bs-target="#konfirmasiPengembalianModal"
                                            data-detail-id="{{ $detail->id }}">
                                            <i class="mdi mdi-keyboard-return"></i> Konfirmasi Pengembalian
                                        </button>
                                    @endif

                                    @if ($detail->catatan)
                                        <div class="mt-2">
                                            <small class="text-muted"><i class="mdi mdi-note-text"></i>
                                                {{ $detail->catatan }}</small>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info">
                Tidak ada detail barang yang diajukan.
            </div>
        @endif

        {{-- Modal Konfirmasi Pengambilan --}}
        <div class="modal fade" id="konfirmasiPengambilanModal" tabindex="-1"
            aria-labelledby="konfirmasiPengambilanModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="konfirmasiPengambilanModalLabel">Konfirmasi Pengambilan Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formKonfirmasiPengambilan"
                        action="{{ route('operator.peminjaman.konfirmasi-pengambilan-item') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <input type="hidden" name="detail_id" id="pengambilan_detail_id">
                            <p>Apakah Anda yakin ingin mengkonfirmasi pengambilan barang ini?</p>
                            <div class="mb-3">
                                <label for="catatan" class="form-label">Catatan (opsional)</label>
                                <textarea class="form-control" id="catatan_pengambilan" name="catatan" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Konfirmasi Pengambilan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal Konfirmasi Pengembalian --}}
        <div class="modal fade" id="konfirmasiPengembalianModal" tabindex="-1"
            aria-labelledby="konfirmasiPengembalianModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="konfirmasiPengembalianModalLabel">Konfirmasi Pengembalian Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="formKonfirmasiPengembalian"
                        action="{{ route('operator.peminjaman.verifikasi-pengembalian-item') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <input type="hidden" name="detail_id" id="pengembalian_detail_id">
                            <p>Apakah Anda yakin ingin mengkonfirmasi pengembalian barang ini?</p>
                            <div class="mb-3">
                                <label for="kondisi_setelah" class="form-label">Kondisi Barang</label>
                                <select class="form-select" id="kondisi_setelah" name="kondisi_setelah" required>
                                    <option value="baik">Baik</option>
                                    <option value="rusak ringan">Rusak Ringan</option>
                                    <option value="rusak berat">Rusak Berat</option>
                                    <option value="hilang">Hilang</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="catatan" class="form-label">Catatan (opsional)</label>
                                <textarea class="form-control" id="catatan_pengembalian" name="catatan" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Konfirmasi Pengembalian</button>
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
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json',
                }
            });

            // Set detail ID pada modal konfirmasi pengambilan
            $('.konfirmasi-pengambilan').on('click', function() {
                const detailId = $(this).data('detail-id');
                $('#pengambilan_detail_id').val(detailId);
            });

            // Set detail ID pada modal konfirmasi pengembalian
            $('.konfirmasi-pengembalian').on('click', function() {
                const detailId = $(this).data('detail-id');
                $('#pengembalian_detail_id').val(detailId);
            });
        });
    </script>
@endpush
