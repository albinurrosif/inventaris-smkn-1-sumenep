@extends('layouts.app')

@section('title', 'Daftar Barang')

@section('content')
    {{-- Notifikasi Floating (atas layar) --}}
    @if ($errors->has('import') || session('success'))
        <div id="floating-alerts-wrapper"
            style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; width: auto; max-width: 100%;">
            @if ($errors->has('import'))
                <div class="alert alert-danger alert-dismissible fade show shadow" role="alert">
                    <strong>{{ $errors->first('import') }}</strong>
                    @if (session('failures'))
                        <ul class="mb-0 mt-2">
                            @foreach (session('failures') as $failure)
                                <li>
                                    Baris {{ $failure->row() }}:
                                    Kolom <strong>{{ implode(', ', $failure->attribute()) }}</strong> –
                                    {{ implode(', ', $failure->errors()) }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow" role="alert">
                    {{ session('success') }}
                </div>
            @endif
        </div>
    @endif

    <div class="container-fluid">
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Daftar Barang</li>

                        </ol>

                    </div>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h4 class="card-title mb-0">Data Barang</h4>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <a href="{{ route('barang.create') }}" class="btn btn-primary btn-sm">
                                <i class="mdi mdi-plus me-1"></i> Tambah Barang
                            </a>
                            <a href="{{ route('barang.export') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="mdi mdi-file-excel"></i> Export Resmi
                            </a>
                            <form action="{{ route('barang.import') }}" method="POST" enctype="multipart/form-data"
                                class="d-flex align-items-center gap-2">
                                @csrf
                                <input type="file" name="file" class="form-control form-control-sm"
                                    style="max-width: 180px;" required>
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="mdi mdi-upload"></i> Import
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card-body">

                        {{-- Filter ruangan --}}
                        <form method="GET" action="{{ route('barang.index') }}" class="row g-3 align-items-center mb-4">
                            <div class="col-auto">
                                <label for="ruangan_id" class="col-form-label">Filter Ruangan:</label>
                            </div>
                            <div class="col-auto">
                                <select name="ruangan_id" id="ruangan_id" class="form-select form-select-sm"
                                    onchange="this.form.submit()">
                                    <option value="">-- Semua Ruangan --</option>
                                    @foreach ($ruanganList as $ruangan)
                                        <option value="{{ $ruangan->id }}"
                                            {{ $ruanganId == $ruangan->id ? 'selected' : '' }}>
                                            {{ $ruangan->nama_ruangan }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </form>

                        {{-- Tabel --}}
                        <div class="table-responsive">
                            <table id="barangTable" class="table table-bordered dt-responsive nowrap w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Kode</th>
                                        <th>Nama</th>
                                        <th>Merk / Model</th>
                                        <th>No Seri Pabrik</th>
                                        <th>Ukuran</th>
                                        <th>Bahan</th>
                                        <th>Tahun</th>
                                        <th>Jumlah</th>
                                        <th>Harga Beli</th>
                                        <th>Sumber</th>
                                        <th>Ruangan</th>
                                        <th>Keadaan</th>
                                        <th>Mutasi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th>No</th>
                                        <th>Kode</th>
                                        <th>Nama</th>
                                        <th>Merk / Model</th>
                                        <th>No Seri Pabrik</th>
                                        <th>Ukuran</th>
                                        <th>Bahan</th>
                                        <th>Tahun</th>
                                        <th>Jumlah</th>
                                        <th>Harga Beli</th>
                                        <th>Sumber</th>
                                        <th>Ruangan</th>
                                        <th>Keadaan</th>
                                        <th>Mutasi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </tfoot>
                                <tbody>
                                    @forelse($barang as $key => $item)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $item->kode_barang }}</td>
                                            <td>{{ $item->nama_barang }}</td>
                                            <td>{{ $item->merk_model ?? '-' }}</td>
                                            <td>{{ $item->no_seri_pabrik ?? '-' }}</td>
                                            <td>{{ $item->ukuran ?? '-' }}</td>
                                            <td>{{ $item->bahan ?? '-' }}</td>
                                            <td>{{ $item->tahun_pembuatan_pembelian ?? '-' }}</td>
                                            <td>{{ $item->jumlah_barang }}</td>
                                            <td>
                                                {{ $item->harga_beli ? 'Rp' . number_format($item->harga_beli, 0, ',', '.') : '-' }}
                                            </td>
                                            <td>{{ $item->sumber ?? '-' }}</td>
                                            <td>{{ $item->ruangan->nama_ruangan ?? '-' }}</td>
                                            <td>
                                                @if ($item->keadaan_barang == 'Baik')
                                                    <span class="badge bg-success">Baik</span>
                                                @elseif ($item->keadaan_barang == 'Kurang Baik')
                                                    <span class="badge bg-warning">Kurang Baik</span>
                                                @else
                                                    <span class="badge bg-danger">Rusak Berat</span>
                                                @endif
                                            </td>
                                            <td>{{ $item->keterangan_mutasi ?? '-' }}</td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a href="{{ route('barang.show', $item->id) }}"
                                                        class="btn btn-info btn-sm">
                                                        <i class="mdi mdi-eye"></i>
                                                    </a>
                                                    <a href="{{ route('barang.edit', $item->id) }}"
                                                        class="btn btn-warning btn-sm">
                                                        <i class="mdi mdi-pencil"></i>
                                                    </a>
                                                    <form action="{{ route('barang.destroy', $item->id) }}" method="POST"
                                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus barang ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="mdi mdi-trash-can"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="15" class="text-center">Tidak ada data barang</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- end card body -->
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let table = $('#barangTable').DataTable({
                destroy: true,
                responsive: true,
                dom: 'Bfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                },
                initComplete: function() {
                    this.api().columns().every(function() {
                        let column = this;
                        if (column.index() === 14) return; // kolom Aksi tidak perlu filter
                        $('<input type="text" placeholder="Filter..." class="form-control form-control-sm" />')
                            .appendTo($(column.footer()).empty())
                            .on('keyup change clear', function() {
                                if (column.search() !== this.value) {
                                    column.search(this.value).draw();
                                }
                            });
                    });
                }
            });
        });
    </script>
@endpush

@push('scripts')
    <script>
        setTimeout(() => {
            const wrapper = document.getElementById('floating-alerts-wrapper');
            if (wrapper) {
                wrapper.classList.add('fade');
                setTimeout(() => wrapper.remove(), 500); // remove from DOM after fade
            }
        }, 2000); // 2 detik
    </script>
@endpush
