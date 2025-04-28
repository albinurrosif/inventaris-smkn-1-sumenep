@extends('layouts.app')

@section('title', 'Ajukan Peminjaman')

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
        <div class="row justify-content-center">
            <div class="col-lg-10">

                {{-- Filter Form --}}
                <form method="GET" action="{{ route('peminjaman.create') }}" class="row mb-4">
                    <div class="col-md-4">
                        <label for="filter_ruangan" class="form-label">Filter Ruangan Asal Barang</label>
                        <select name="filter_ruangan" id="filter_ruangan" class="form-select">
                            <option value="">-- Semua Ruangan --</option>
                            @foreach ($ruangan as $r)
                                <option value="{{ $r->id }}"
                                    {{ request('filter_ruangan') == $r->id ? 'selected' : '' }}>
                                    {{ $r->nama_ruangan }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="keyword" class="form-label">Cari Barang</label>
                        <input type="text" name="keyword" id="keyword" class="form-control"
                            placeholder="Nama atau Kode Barang" value="{{ request('keyword') }}">
                    </div>
                    <div class="col-md-4 align-self-end">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="mdi mdi-magnify"></i> Cari Barang
                        </button>
                    </div>
                </form>

                {{-- Form Utama --}}
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Form Pengajuan Peminjaman</h4>
                        <a href="{{ route('peminjaman.index') }}" class="btn btn-secondary btn-sm">
                            <i class="mdi mdi-arrow-left"></i> Kembali
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('peminjaman.store') }}" method="POST">
                            @csrf

                            <div class="mb-3">
                                <label for="id_ruangan" class="form-label">Pilih Ruangan Tujuan</label>
                                <select name="id_ruangan" id="id_ruangan" class="form-select" required>
                                    <option value="">-- Pilih Ruangan --</option>
                                    @foreach ($ruangan as $r)
                                        <option value="{{ $r->id }}"
                                            {{ old('id_ruangan') == $r->id ? 'selected' : '' }}>
                                            {{ $r->nama_ruangan }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="tanggal_pinjam" class="form-label">Tanggal Pinjam</label>
                                <input type="datetime-local" name="tanggal_pinjam" id="tanggal_pinjam"
                                    value="{{ old('tanggal_pinjam', now()->format('Y-m-d\TH:i')) }}" class="form-control"
                                    required>
                            </div>

                            <div class="mb-3">
                                <label for="durasi_pinjam" class="form-label">Durasi Pinjam (hari)</label>
                                <input type="number" name="durasi_pinjam" id="durasi_pinjam" class="form-control"
                                    min="1" value="{{ old('durasi_pinjam', 1) }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="keterangan" class="form-label">Keterangan</label>
                                <textarea name="keterangan" id="keterangan" rows="3" class="form-control">{{ old('keterangan') }}</textarea>
                            </div>

                            {{-- Tabel Barang --}}
                            <div class="mb-3">
                                <label class="form-label">Pilih Barang</label>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Pilih</th>
                                                <th>Kode</th>
                                                <th>Nama Barang</th>
                                                <th>Stok</th>
                                                <th>Jumlah</th>
                                                <th>Detail</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($barang as $b)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" name="barang_id[]"
                                                            value="{{ $b->id }}"
                                                            class="form-check-input checkbox-barang">
                                                    </td>
                                                    <td>{{ $b->kode_barang }}</td>
                                                    <td>{{ $b->nama_barang }}</td>
                                                    <td>{{ $b->jumlah_barang }}</td>
                                                    <td>
                                                        <input type="number" name="jumlah[{{ $b->id }}]"
                                                            class="form-control form-control-sm jumlah-barang"
                                                            min="1" max="{{ $b->jumlah_barang }}" disabled>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-info btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#detailBarang{{ $b->id }}">
                                                            <i class="mdi mdi-eye"></i>
                                                        </button>
                                                    </td>
                                                </tr>

                                                {{-- Modal --}}
                                                <div class="modal fade" id="detailBarang{{ $b->id }}" tabindex="-1"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Detail Barang</h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p><strong>Kode:</strong> {{ $b->kode_barang }}</p>
                                                                <p><strong>Nama:</strong> {{ $b->nama_barang }}</p>
                                                                <p><strong>Merk/Model:</strong> {{ $b->merk_model ?? '-' }}
                                                                </p>
                                                                <p><strong>Ukuran:</strong> {{ $b->ukuran ?? '-' }}</p>
                                                                <p><strong>Stok:</strong> {{ $b->jumlah_barang }}</p>
                                                                <p><strong>Kondisi:</strong> {{ $b->keadaan_barang }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center">Tidak ada barang yang tersedia.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-check-circle-outline"></i> Ajukan Peminjaman
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.checkbox-barang').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const jumlahInput = this.closest('tr').querySelector('.jumlah-barang');
                    jumlahInput.disabled = !this.checked;
                    if (!this.checked) jumlahInput.value = '';
                });
            });
        });
    </script>
@endpush

@push('scripts')
    <script>
        document.querySelectorAll('.jumlah-barang').forEach(input => {
            input.addEventListener('input', function() {
                const max = parseInt(this.getAttribute('max'));
                if (parseInt(this.value) > max) {
                    this.value = max;
                    Swal.fire({
                        icon: 'warning',
                        title: 'Melebihi Stok!',
                        text: 'Jumlah melebihi stok barang yang tersedia.',
                        timer: 2500,
                        showConfirmButton: false,
                    });
                }
            });
        });
    </script>
@endpush
