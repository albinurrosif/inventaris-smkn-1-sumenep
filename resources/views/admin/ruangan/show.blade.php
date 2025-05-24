@extends('layouts.app')

@section('title', 'Detail Ruangan - ' . $ruangan->nama_ruangan)

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('ruangan.index') }}">Ruangan</a></li>
                            <li class="breadcrumb-item active">Detail Ruangan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col">
                <h1 class="h3">Detail Ruangan: {{ $ruangan->nama_ruangan }}</h1>
            </div>
            <div class="col-auto">
                <a href="{{ route('ruangan.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Informasi Umum -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Informasi Ruangan</h5>
                        <button type="button" class="btn btn-sm btn-warning btn-edit-ruangan"
                            data-ruangan='@json($ruangan)'>
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Nama Ruangan</dt>
                            <dd class="col-sm-7">{{ $ruangan->nama_ruangan }}</dd>

                            <dt class="col-sm-5">Operator</dt>
                            <dd class="col-sm-7">{{ $ruangan->operator->name ?? '-' }}</dd>

                            <dt class="col-sm-5">Jumlah Barang</dt>
                            <dd class="col-sm-7">{{ $itemCount }}</dd>

                            <dt class="col-sm-5">Total Unit</dt>
                            <dd class="col-sm-7">{{ $totalUnit }}</dd>

                            <dt class="col-sm-5">Nilai Total</dt>
                            <dd class="col-sm-7">Rp {{ number_format($totalValue, 0, ',', '.') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Daftar Barang -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Barang dalam Ruangan</h5>
                    </div>
                    <div class="card-body">
                        @if ($barangList->count())
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Nama Barang</th>
                                            <th>Jumlah</th>
                                            <th>Unit</th>
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
                                                <td>{{ $barang->total_unit }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info mb-0">
                                Tidak ada barang dalam ruangan ini.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.ruangan.partials.modal-edit', ['ruangan' => $ruangan])

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle modal edit
            const modalEdit = new bootstrap.Modal(document.getElementById('modalEditRuangan'));
            document.querySelectorAll('.btn-edit-ruangan').forEach(button => {
                button.addEventListener('click', () => {
                    const data = JSON.parse(button.dataset.ruangan);
                    document.getElementById('edit_nama_ruangan').value = data.nama_ruangan;
                    document.querySelector('#modalEditRuangan form').action =
                        `/ruangan/${data.id}`;
                    modalEdit.show();
                });
            });
        });
    </script>
@endpush
