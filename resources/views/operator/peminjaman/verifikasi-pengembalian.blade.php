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

        @if ($peminjaman && $peminjaman->count())
            @foreach ($peminjaman as $item)
                {{-- isi looping --}}
            @endforeach
        @else
            <div class="alert alert-warning">Tidak ada data pengembalian yang menunggu verifikasi.</div>
        @endif
        @foreach ($peminjaman as $item)
            <div class="card mb-4">
                <div class="card-body">
                    <form action="{{ route('operator.peminjaman.verifikasi-pengembalian.store', $item->id) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Nama Peminjam</label>
                            <input type="text" class="form-control" value="{{ $item->peminjam->name }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal Peminjaman</label>
                            <input type="text" class="form-control"
                                value="{{ \Carbon\Carbon::parse($item->tanggal_pinjam)->translatedFormat('d M Y H:i') }}"
                                readonly>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Barang</th>
                                        <th>Jumlah Dipinjam</th>
                                        <th>Jumlah Kembali</th>
                                        <th>Status</th>
                                        <th>Kondisi Setelah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($item->detailPeminjaman as $detail)
                                        <tr>
                                            <td>{{ $detail->barang->nama_barang }}</td>
                                            <td>{{ $detail->jumlah_dipinjam }}</td>
                                            <td>
                                                <input type="number" name="jumlah_terverifikasi[{{ $detail->id }}]"
                                                    class="form-control" value="{{ $detail->jumlah_dipinjam }}"
                                                    min="0">
                                            </td>
                                            <td>
                                                <select name="status_pengembalian[{{ $detail->id }}]" class="form-select">
                                                    <option value="Dikembalikan"
                                                        {{ old('status_pengembalian.' . $detail->id) == 'Dikembalikan' ? 'selected' : '' }}>
                                                        Dikembalikan</option>
                                                    <option value="Hilang"
                                                        {{ old('status_pengembalian.' . $detail->id) == 'Hilang' ? 'selected' : '' }}>
                                                        Hilang</option>
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
                </div>
            </div>
        @endforeach
    </div>
@endsection
