@extends('layouts.app')

@section('title', 'Daftar Barang')

@section('content')
    @if (session('failures'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let failureMessages = '';
                @foreach (session('failures') as $failure)
                    failureMessages += `• Baris {{ $failure->row() }}: {{ implode(', ', $failure->errors()) }}<br>`;
                @endforeach

                Swal.fire({
                    icon: 'error',
                    title: 'Import Gagal',
                    html: failureMessages,
                    showConfirmButton: false,
                    timer: 5000, // Lebih lama karena pesan mungkin panjang
                    position: 'top',
                    toast: true
                });
            });
        </script>
    @endif

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Daftar Barang</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Data Barang (Agregat)</h4>
                        <div class="d-flex align-items-center gap-2">
                            <form method="GET" action="#" class="d-flex align-items-center gap-2">
                                <input type="hidden" name="id_ruangan" value="{{ request('id_ruangan') }}">
                                <input type="hidden" name="id_kategori" value="{{ request('id_kategori') }}">
                                <input type="hidden" name="status" value="{{ request('status') }}">
                                <input type="hidden" name="keadaan_barang" value="{{ request('keadaan_barang') }}">
                                <input type="hidden" name="tahun" value="{{ request('tahun') }}">

                                <a href="{{ route('barang-qr-code.export.excel', request()->query()) }}"
                                    class="btn btn-success">
                                    <i class="mdi mdi-file-excel"></i> Export Excel
                                </a>

                                <a href="{{ route('barang-qr-code.export.pdf', array_merge(request()->query(), ['pisah_per_ruangan' => false])) }}"
                                    class="btn btn-danger">
                                    <i class="mdi mdi-file-pdf-box"></i> Export PDF
                                </a>

                                {{-- Tambahan: pisah per ruangan --}}
                                <a href="{{ route('barang-qr-code.export.pdf', array_merge(request()->query(), ['pisah_per_ruangan' => true])) }}"
                                    class="btn btn-outline-danger">
                                    <i class="mdi mdi-file-pdf-box"></i> PDF (Per Ruangan)
                                </a>

                                <button type="button" class="btn btn-info"
                                    onclick="document.getElementById('fileInput').click();">
                                    <i class="mdi mdi-upload"></i> Import
                                </button>

                                <a href="{{ route('barang.create') }}" class="btn btn-primary">
                                    <i class="mdi mdi-plus"></i> Tambah Barang
                                </a>

                            </form>
                            <form id="importForm" action="{{ route('barang.import') }}" method="POST"
                                enctype="multipart/form-data" class="d-none">
                                @csrf
                                <input type="file" name="file" id="fileInput" accept=".csv,.xlsx"
                                    onchange="document.getElementById('importForm').submit()" hidden>
                            </form>
                            {{-- <button type="button" class="btn btn-info"
                                onclick="document.getElementById('fileInput').click();"><i class="mdi mdi-upload"></i>
                                Import</button>
                            <button id="btnTambahBarang" class="btn btn-primary"><i class="mdi mdi-plus"></i> Tambah
                                Barang</button> --}}
                        </div>
                    </div>

                    <div class="card-body">
                        <form method="GET" action="{{ route('barang.index') }}" class="row g-3 align-items-center mb-3">
                            <div class="col-auto">
                                <label for="id_ruangan" class="col-form-label">Filter Ruangan:</label>
                            </div>
                            <div class="col-auto">
                                <select name="id_ruangan" id="id_ruangan" class="form-select form-select-sm"
                                    onchange="this.form.submit()">
                                    <option value="">-- Semua Ruangan --</option>
                                    @foreach ($ruanganList as $ruanganItem)
                                        <option value="{{ $ruanganItem->id }}"
                                            {{ $ruanganId == $ruanganItem->id ? 'selected' : '' }}>
                                            {{ $ruanganItem->nama_ruangan }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto ms-auto">
                                <label for="globalSearch" class="visually-hidden">Cari</label>
                                <input type="text" id="globalSearch" class="form-control form-control-sm"
                                    placeholder="Cari di semua kolom...">
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table id="barangTable" class="table table-bordered dt-responsive nowrap w-100 table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Nama</th>
                                        <th>Kode</th>
                                        <th>Merk/Model</th>
                                        <th>Ukuran</th>
                                        <th>Bahan</th>
                                        <th>Tahun</th>
                                        <th>Jumlah</th>
                                        <th>Harga</th>
                                        <th>Sumber</th>
                                        <th>Keadaan</th>
                                        <th>Ruangan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th><input type="text" placeholder="Filter No"
                                                class="form-control form-control-sm"></th>
                                        <th><input type="text" placeholder="Filter Nama"
                                                class="form-control form-control-sm"></th>
                                        <th><input type="text" placeholder="Filter Kode"
                                                class="form-control form-control-sm"></th>
                                        <th><input type="text" placeholder="Filter Merk / Model"
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
                                        <th><input type="text" placeholder="Filter Keadaan"
                                                class="form-control form-control-sm"></th>
                                        <th><input type="text" placeholder="Filter Ruangan"
                                                class="form-control form-control-sm"></th>
                                        <th>Aksi</th>
                                    </tr>
                                </tfoot>
                                <tbody>
                                    @foreach ($barang as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            {{-- <td>{{ $item->nama_barang }}</td> --}}
                                            <td>
                                                <a
                                                    href="{{ route('barang.show', $item->id) }}">{{ $item->nama_barang }}</a>
                                            </td>
                                            <td>{{ $item->kode_barang }}</td>
                                            <td>{{ $item->merk_model ?? '-' }}</td>
                                            <td>{{ $item->ukuran ?? '-' }}</td>
                                            <td>{{ $item->bahan ?? '-' }}</td>
                                            <td>{{ $item->tahun_pembuatan_pembelian ?? '-' }}</td>
                                            <td>{{ $item->jumlah_barang }}</td>
                                            <td>{{ $item->harga_beli ? 'Rp' . number_format($item->harga_beli, 0, ',', '.') : '-' }}
                                            </td>
                                            <td>{{ $item->sumber ?? '-' }}</td>
                                            <td><span
                                                    class="badge bg-{{ $item->keadaan_barang == 'Baik' ? 'success' : ($item->keadaan_barang == 'Kurang Baik' ? 'warning' : 'danger') }}">{{ $item->keadaan_barang }}</span>
                                            </td>
                                            <td>
                                                @if ($item->qrCodes->isNotEmpty())
                                                    {{ $item->qrCodes->first()->ruangan->nama_ruangan ?? '-' }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('barang.show', $item->id) }}"
                                                    class="btn btn-info btn-sm" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-warning btn-sm btn-edit-barang"
                                                    data-barang='@json($item)'>
                                                    <i class="fas fa-edit"></i>
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
        </div>
    </div>

    @include('admin.barang.partials.modal_edit')

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
                //buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
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

        // Event listener untuk tombol tambah barang
        document.addEventListener('DOMContentLoaded', function() {
            const btnTambahBarang = document.getElementById('btnTambahBarang');
            const modalElement = document.getElementById('modalTambahBarang');

            if (btnTambahBarang && modalElement) {
                const modalTambahBarang = new bootstrap.Modal(modalElement);

                btnTambahBarang.addEventListener('click', function() {
                    modalTambahBarang.show();
                });
            }

            // Inisialisasi Bootstrap tooltip
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });


        // Event listener untuk tombol hapus
        document.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.btn-delete-barang');
            if (deleteBtn) {
                const barangId = deleteBtn.getAttribute('data-id');
                const barangNama = deleteBtn.getAttribute('data-nama');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: `Barang "${barangNama}" akan dihapus secara permanen.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('formDeleteBarang');
                        form.action = `/barang/${barangId}`;
                        form.submit();
                    }
                });
            }
        });

        document.getElementById('btnAutoGenerate').addEventListener('click', function() {
            const inputs = document.querySelectorAll('input[name^="kode_unit"]');
            inputs.forEach((input, index) => {
                input.value = `UNIT-${String(index + 1).padStart(3, '0')}`;
            });
        });
    </script>
    @if (session('showInputSeriModal'))
        @php $barang = \App\Models\Barang::find(session('barang_id')); @endphp
        @include('barang.partials.modal_input_seri', ['barang' => $barang])
        <script>
            var myModal = new bootstrap.Modal(document.getElementById('modalInputSeri'));
            myModal.show();
        </script>
    @endif

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
