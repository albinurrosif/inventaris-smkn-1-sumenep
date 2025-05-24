@extends('layouts.app')

@section('title', 'Daftar Barang')

@section('content')

    {{-- SweetAlert Notifications --}}

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

    @if ($errors->has('file'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                Swal.fire({

                    icon: 'error',

                    title: 'Gagal',

                    text: '{{ $errors->first('file') }}',

                    timer: 3000,

                    showConfirmButton: false

                });

            });
        </script>
    @endif

    @if (session('failures'))

        <div class="alert alert-danger">

            <strong>Import gagal!</strong>

            <ul>

                @foreach (session('failures') as $failure)
                    <li>Baris {{ $failure->row() }}: {{ implode(', ', $failure->errors()) }}</li>
                @endforeach

            </ul>

        </div>

    @endif

    <div class="container-fluid">

        <div class="row">

            <div class="col-12">

                <div class="page-title-box d-sm-flex align-items-center justify-content-between">

                    <div class="page-title-right">

                        <ol class="breadcrumb m-0">

                            <li class="breadcrumb-item"><a href="{{ route('operator.dashboard') }}">Dashboard</a></li>

                            <li class="breadcrumb-item active">Daftar Barang</li>

                        </ol>

                    </div>

                </div>

            </div>

        </div>

        <div class="row">

            <div class="col-12">

                <div class="card">

                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">

                        <h4 class="card-title mb-0">Data Barang</h4>

                        <div class="d-flex flex-wrap gap-2 align-items-center">

                            <a href="{{ route('operator.barang.export') }}" class="btn btn-outline-success btn-md"
                                data-bs-toggle="tooltip" title="Export ke Excel">

                                <i class="mdi mdi-file-excel me-2"></i> Export

                            </a>

                        </div>

                    </div>

                    <div class="card-body">

                        {{-- Filter ruangan --}}

                        @if ($tidak_ada_ruangan)

                            <div class="alert alert-info">

                                Operator ini tidak mengelola ruangan mana pun.

                            </div>
                        @else
                            <form method="GET" action="{{ route('operator.barang.index') }}"
                                class="row g-3 align-items-center mb-4">

                                <div class="col-auto">

                                    <label for="ruangan_id" class="col-form-label">Filter Ruangan:</label>

                                </div>

                                <div class="col-auto">

                                    <select name="ruangan_id" id="ruangan_id" class="form-select form-select-sm"
                                        onchange="this.form.submit()">

                                        <option value="">-- Semua Ruangan --</option>

                                        @foreach ($ruanganList as $ruanganItem)
                                            <option value="{{ $ruanganItem->id }}"
                                                {{ $ruanganId == $ruanganItem->id ? 'selected' : '' }}>

                                                {{ $ruanganItem->nama_ruangan }}

                                            </option>
                                        @endforeach

                                    </select>

                                </div>

                                <div class="col-auto ms-auto">

                                    <label for="globalSearch" class="visually-hidden">Cari</label>

                                    <input type="text" id="globalSearch" class="form-control form-control-sm"
                                        placeholder="Cari di semua kolom...">

                                </div>

                            </form>

                            {{-- Tabel --}}

                            <div class="table-responsive">

                                <table id="barangTable" class="table table-bordered dt-responsive nowrap w-100">

                                    <thead class="table-light">

                                        <tr>

                                            <th>No. Urut</th>

                                            <th>Kode Barang</th>

                                            <th>Nama Barang</th>

                                            <th>Merk / <br>Model</th>

                                            <th>No. Seri <br>Pabrik</th>

                                            <th>Ukuran</th>

                                            <th>Bahan</th>

                                            <th>Tahun Pembuatan / <br>Pembelian</th>

                                            <th>Jumlah <br>Barang/Register</th>

                                            <th>Harga Beli / <br>Perolehan</th>

                                            <th>Sumber</th>

                                            <th>Ruangan</th>

                                            <th>Keadaan <br>Barang</th>

                                            <th>Keterangan <br>Mutasi Dll.</th>

                                        </tr>

                                    </thead>

                                    <tfoot>

                                        <tr>

                                            <th><input type="text" placeholder="Filter No"
                                                    class="form-control form-control-sm"></th>

                                            <th><input type="text" placeholder="Filter Kode"
                                                    class="form-control form-control-sm"></th>

                                            <th><input type="text" placeholder="Filter Nama"
                                                    class="form-control form-control-sm"></th>

                                            <th><input type="text" placeholder="Filter Merk / Model"
                                                    class="form-control form-control-sm"></th>

                                            <th><input type="text" placeholder="Filter No Seri"
                                                    class="form-control form-control-sm"></th>

                                            <th><input type="text" placeholder="Filter Ukuran"
                                                    class="form-control form-control-sm"></th>

                                            <th><input type="text" placeholder="Filter Bahan"
                                                    class="form-control form-control-sm"></th>

                                            <th><input type="text" placeholder="Filter Tahun"
                                                    class="form-control form-control-sm"></th>

                                            <th><input type="text" placeholder="Filter Jumlah"
                                                    class="form-control form-control-sm"></th>

                                            <th><input type="text" placeholder="Filter Harga"
                                                    class="form-control form-control-sm"></th>

                                            <th><input type="text" placeholder="Filter Sumber"
                                                    class="form-control form-control-sm"></th>

                                            <th>

                                                <input type="text" placeholder="Filter Ruangan"
                                                    class="form-control form-control-sm">

                                            </th>

                                            <th><input type="text" placeholder="Filter Keadaan"
                                                    class="form-control form-control-sm"></th>

                                            <th><input type="text" placeholder="Filter Mutasi"
                                                    class="form-control form-control-sm"></th>

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

                                            </tr>

                                        @empty

                                            {{-- <tr>

                                                <td colspan="14" class="text-center">Tidak ada data barang</td>

                                            </tr> --}}
                                        @endforelse

                                    </tbody>

                                </table>

                            </div>

                        @endif

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            // Hancurkan instance DataTables jika sudah ada 

            if ($.fn.DataTable.isDataTable('#barangTable')) {

                $('#barangTable').DataTable().destroy();

            }



            // Inisialisasi ulang DataTables 

            let table = $('#barangTable').DataTable({

                responsive: true,

                dom: 'lrtip',

                language: {

                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'

                },

                order: [

                    [0, 'asc']

                ],

                initComplete: function() {

                    this.api().columns().every(function() {

                        let column = this;

                        $('input', column.footer()).on('keyup change clear', function() {

                            if (column.search() !== this.value) {

                                column.search(this.value).draw();

                            }

                        });

                    });

                }

            });

            // Hubungkan input global ke pencarian DataTables 

            $('#globalSearch').on('keyup', function() {

                table.search(this.value).draw();

            });

        });
    </script>
@endpush
