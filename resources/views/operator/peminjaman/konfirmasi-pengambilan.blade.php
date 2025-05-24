@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Konfirmasi Pengambilan Barang</h5>
                            <a href="{{ route('operator.peminjaman.index') }}" class="btn btn-sm btn-secondary">Kembali</a>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Konfirmasi pengambilan barang untuk memperbarui status
                            peminjaman menjadi 'Dipinjam'.
                        </div>

                        <div class="mb-4">
                            <h6>Informasi Peminjaman:</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th width="200">Peminjam</th>
                                    <td>{{ $peminjaman->peminjam->name }}</td>
                                </tr>
                                <tr>
                                    <th>Tanggal Pengajuan</th>
                                    <td>{{ $peminjaman->tanggal_pengajuan->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Tanggal Disetujui</th>
                                    <td>{{ $peminjaman->tanggal_disetujui ? $peminjaman->tanggal_disetujui->format('d/m/Y H:i') : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        @if ($peminjaman->status_pengajuan == 'disetujui')
                                            <span class="badge bg-success">Disetujui</span>
                                        @else
                                            <span
                                                class="badge bg-secondary">{{ ucfirst($peminjaman->status_pengajuan) }}</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Keterangan</th>
                                    <td>{{ $peminjaman->keterangan ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>

                        <h6>Daftar Barang yang Akan Diambil:</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Barang</th>
                                        <th>Ruangan Asal</th>
                                        <th>Jumlah</th>
                                        <th>Tanggal Kembali</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($peminjaman->detailPeminjaman as $index => $detail)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $detail->barang->nama_barang }}</td>
                                            <td>{{ $detail->ruanganAsal->nama_ruangan }}</td>
                                            <td>{{ $detail->jumlah_dipinjam }}</td>
                                            <td>{{ $detail->tanggal_kembali ? $detail->tanggal_kembali->format('d/m/Y') : '-' }}
                                            </td>
                                            <td>
                                                @if ($detail->status_pengembalian == 'disetujui')
                                                    <span class="badge bg-success">Disetujui</span>
                                                @else
                                                    <span
                                                        class="badge bg-secondary">{{ ucfirst($detail->status_pengembalian) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <form action="{{ route('operator.peminjaman.konfirmasi-pengambilan', $peminjaman->id) }}"
                            method="POST" class="mt-4">
                            @csrf
                            <div class="mb-3">
                                <label for="catatan" class="form-label">Catatan (opsional)</label>
                                <textarea class="form-control" id="catatan" name="catatan" rows="3"></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="konfirmasi" name="konfirmasi" required>
                                <label class="form-check-label" for="konfirmasi">
                                    Saya konfirmasi bahwa barang-barang di atas telah diambil oleh peminjam
                                </label>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Konfirmasi Pengambilan Barang</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
