@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('kategori-barang.index') }}">Kategori</a></li>
                            <li class="breadcrumb-item active">Detail Kategori</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col">
                <h1 class="h3">Detail Kategori: {{ $kategoriBarang->nama_kategori }}</h1>
            </div>
            <div class="col-auto">
                <a href="{{ route('kategori-barang.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Informasi Kategori</h5>
                        <button type="button" class="btn btn-sm btn-warning btn-edit-kategori"
                            data-kategori='@json($kategoriBarang)'>
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Nama Kategori</dt>
                            <dd class="col-sm-7">{{ $kategoriBarang->nama_kategori }}</dd>

                            <dt class="col-sm-5">Deskripsi</dt>
                            <dd class="col-sm-7">{{ $kategoriBarang->deskripsi ?? '-' }}</dd>

                            <dt class="col-sm-5">Jumlah Item</dt>
                            <dd class="col-sm-7">{{ $itemCount }}</dd>

                            <dt class="col-sm-5">Total Unit</dt>
                            <dd class="col-sm-7">{{ $totalUnit }}</dd>

                            <dt class="col-sm-5">Unit Aktif</dt>
                            <dd class="col-sm-7">{{ $activeUnit }}</dd>

                            <dt class="col-sm-5">Nilai Total</dt>
                            <dd class="col-sm-7">Rp {{ number_format($totalValue, 0, ',', '.') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Barang dalam Kategori</h5>
                    </div>
                    <div class="card-body">
                        @if ($barangList->count())
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Nama Barang</th>
                                            <th>Jumlah</th>
                                            <th>Ruangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($barangList as $barang)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('barang.show', $barang->id) }}">
                                                        {{ $barang->nama_barang }}
                                                    </a>
                                                </td>
                                                <td>{{ $barang->jumlah_barang }}</td>
                                                <td>{{ $barang->ruangan->nama_ruangan ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info mb-0">
                                Tidak ada barang dalam kategori ini.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>

    @include('admin.kategori.partials.modal-edit', ['kategoriBarang' => $kategoriBarang])
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle modal edit
            const modalEdit = new bootstrap.Modal(document.getElementById('modalEditKategori'));
            document.querySelectorAll('.btn-edit-kategori').forEach(button => {
                button.addEventListener('click', () => {
                    const data = JSON.parse(button.dataset.kategori);
                    document.getElementById('edit_nama_kategori').value = data.nama_kategori;
                    document.getElementById('edit_deskripsi').value = data.deskripsi;
                    document.querySelector('#modalEditKategori form').action =
                        `/kategori-barang/${data.id}`;
                    modalEdit.show();
                });
            });
        });
    </script>
@endpush
