@extends('layouts.app')

@section('title', 'Verifikasi Pengembalian Barang')

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
        <h4 class="mb-3">Verifikasi Pengembalian</h4>

        <div class="card">
            <div class="card-body">
                @if ($peminjaman && $peminjaman->detailPeminjaman->isNotEmpty())
                    <form action="{{ route('operator.peminjaman.proses-verifikasi-pengembalian', $peminjaman->id) }}"
                        method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Nama Peminjam</label>
                            <input type="text" class="form-control" value="{{ $peminjaman->peminjam->name }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal Pengajuan</label>
                            <input type="text" class="form-control"
                                value="{{ \Carbon\Carbon::parse($peminjaman->tanggal_pengajuan)->translatedFormat('d M Y H:i') }}"
                                readonly>
                        </div>

                        <h5 class="card-title">Detail Barang yang Dikembalikan</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Barang</th>
                                        <th>Jumlah Dipinjam</th>
                                        <th>Tanggal Kembali</th>
                                        <th>Status Pengembalian</th>
                                        <th>Keterangan Kondisi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($peminjaman->detailPeminjaman as $detail)
                                        @if ($detail->status == 'menunggu_verifikasi')
                                            <tr>
                                                <td>{{ $detail->barang->nama_barang }}</td>
                                                <td>{{ $detail->jumlah_dipinjam }}</td>
                                                <td>{{ \Carbon\Carbon::parse($detail->tanggal_kembali)->translatedFormat('d M Y') }}
                                                </td>
                                                <td>
                                                    <select name="status_pengembalian[{{ $detail->id }}]"
                                                        class="form-select">
                                                        <option value="Dikembalikan"
                                                            {{ old('status_pengembalian.' . $detail->id) == 'Dikembalikan' ? 'selected' : '' }}>
                                                            Dikembalikan</option>
                                                        <option value="Rusak"
                                                            {{ old('status_pengembalian.' . $detail->id) == 'Rusak' ? 'selected' : '' }}>
                                                            Rusak</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" name="kondisi_setelah[{{ $detail->id }}]"
                                                        class="form-control" placeholder="Keterangan kondisi setelah...">
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" class="btn btn-success">
                            <i class="mdi mdi-check-circle"></i> Simpan Verifikasi
                        </button>

                        <a href="{{ route('operator.peminjaman.index') }}" class="btn btn-secondary">
                            <i class="mdi mdi-arrow-left"></i> Kembali
                        </a>
                    </form>
                @else
                    <div class="alert alert-warning">Tidak ada data pengembalian yang menunggu verifikasi.</div>
                @endif
            </div>
        </div>
    </div>
@endsection
