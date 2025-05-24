@extends('layouts.app')

@section('title', 'Detail Barang - ' . $barang->nama_barang)

@section('content')
    <div class="container-fluid">
        <!-- Page Title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">

                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('barang.index') }}">Daftar Barang</a></li>
                            <li class="breadcrumb-item active">Detail Unit</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Detail -->
        <div class="card">
            <div class="card-header justify-between">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Barang: {{ $barang->nama_barang }} ({{ $barang->kode_barang }})</h5>
                    <a href="{{ route('barang.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Barang
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4 p-3 border rounded position-relative">
                    <!-- Tambah border dan rounded untuk card-like appearance -->
                    <button class="btn btn-warning btn-sm btn-edit-barang rounded-circle position-absolute"
                        style="width: 32px; height: 32px; top: -10px; right: -10px"
                        data-barang='@json($barang)'>
                        <i class="fas fa-edit"></i>
                    </button>

                    <div class="col-md-6">
                        <p><strong>Merk / Model:</strong> {{ $barang->merk_model ?? '-' }}</p>
                        <p><strong>Ukuran:</strong> {{ $barang->ukuran ?? '-' }}</p>
                        <p><strong>Bahan:</strong> {{ $barang->bahan ?? '-' }}</p>
                        <p><strong>Kategori:</strong> {{ $barang->kategori->nama_kategori ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Ruangan:</strong>
                            @if ($barang->qrCodes->isNotEmpty())
                                {{ $barang->qrCodes->first()->ruangan->nama_ruangan }}
                            @else
                                -
                            @endif
                            @php
                                $uniqueRuangan = $barang->qrCodes->pluck('id_ruangan')->unique()->count();
                            @endphp

                            @if ($uniqueRuangan > 1)
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Peringatan: Terdapat unit dengan ruangan berbeda dalam agregat ini.
                                </div>
                            @endif
                        </p>
                        <p><strong>Tahun:</strong> {{ $barang->tahun_pembuatan_pembelian ?? '-' }}</p>
                        <p><strong>Jumlah Unit:</strong> {{ $barang->jumlah_barang }}</p>
                        <p><strong>Keadaan Umum:</strong> {{ $barang->keadaan_barang }}</p>
                    </div>
                </div>

                <!-- Table Unit / QR Codes -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nomor Seri Pabrik</th>
                                <th>Status</th>
                                <th>Keadaan Barang</th>
                                <th>Keterangan</th>
                                <th>QR Code</th>
                                <th>aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($barang->qrCodes as $index => $qrCode)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $qrCode->no_seri_pabrik }}</td>
                                    <td><span class="badge bg-secondary">{{ $qrCode->status }}</span></td>
                                    <td><span class="badge bg-info">{{ $qrCode->keadaan_barang }}</span></td>
                                    <td>{{ $qrCode->keterangan ?? '-' }}</td>
                                    <td>
                                        @if ($qrCode->qr_path && Storage::disk('public')->exists($qrCode->qr_path))
                                            <img src="{{ asset('storage/' . $qrCode->qr_path) }}" alt="QR Code"
                                                width="60">
                                        @else
                                            <span class="text-muted">QR tidak tersedia</span>
                                        @endif
                                    </td>
                                    <td>
                                        <!-- Tombol buka modal -->
                                        {{-- <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                            data-bs-target="#hapusUnitModal{{ $qrCode->id }}">
                                            <i class="fas fa-trash"></i>
                                        </button> --}}
                                        <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                                            data-bs-target="#modalHapusBarang">
                                            <i class="fas fa-trash"></i> Hapus Barang
                                        </button>
                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

    @include('admin.barang.partials.modal_edit')
    @include('admin.barang.partials.modal_delete')

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Inisialisasi DataTable
            $('#tabelBarang').DataTable();

        });
    </script>

    <script>
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-edit-barang')) {
                const data = JSON.parse(e.target.closest('.btn-edit-barang').dataset.barang);

                document.getElementById('editNamaBarang').value = data.nama_barang ?? '';
                document.getElementById('editMerkModel').value = data.merk_model ?? '';
                document.getElementById('editUkuran').value = data.ukuran ?? '';
                document.getElementById('editBahan').value = data.bahan ?? '';

                document.getElementById('formEditBarang').action = `/barang/${data.id}`;
                const modal = new bootstrap.Modal(document.getElementById('modalEditBarang'));
                modal.show();
            }
        });
    </script>
@endpush
