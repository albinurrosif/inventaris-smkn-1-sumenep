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
                            <button id="btnTambahBarang" class="btn btn-primary btn-md me-2" data-bs-toggle="tooltip"
                                title="Tambah barang baru">
                                <i class="mdi mdi-plus me-1"></i> Tambah Barang
                            </button>

                            <a href="{{ route('barang.export') }}" class="btn btn-outline-success btn-md"
                                data-bs-toggle="tooltip" title="Export ke Excel">
                                <i class="mdi mdi-file-excel me-2"></i> Export
                            </a>

                            <form id="importForm" action="{{ route('barang.import') }}" method="POST"
                                enctype="multipart/form-data" class="d-none">
                                @csrf
                                <input type="file" name="file" id="fileInput" accept=".csv,.xlsx"
                                    onchange="document.getElementById('importForm').submit()" hidden>
                            </form>

                            <button type="button" class="btn btn-outline-info btn-md"
                                onclick="document.getElementById('fileInput').click();" data-bs-toggle="tooltip"
                                title="Import dari file Excel">
                                <i class="mdi mdi-upload me-2"></i> Import
                            </button>
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
                                    @foreach ($ruanganList as $ruanganItem)
                                        <option value="{{ $ruanganItem->id }}"
                                            {{ $ruanganId == $ruanganItem->id ? 'selected' : '' }}>
                                            {{ $ruanganItem->nama_ruangan }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </form>

                        {{-- Tabel --}}
                        <div class="table-responsive">
                            <table id="barangTable" class="table table-bordered dt-responsive nowrap w-100">
                                <thead class="table-light">
                                    @include('barang.partials.thead')
                                </thead>
                                <tfoot>
                                    @include('barang.partials.tfoot')
                                </tfoot>
                                <tbody>
                                    @include('barang.partials.tbody', ['barang' => $barang])
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- end card body -->
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Tambah Barang --}}
    @include('barang.partials.modal-create', ['ruanganList' => $ruanganList])
    {{-- Modal Edit Barang --}}
    @include('barang.partials.modal-edit', ['ruanganList' => $ruanganList])

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

        // Event listener untuk tombol edit
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-edit-barang')) {
                const button = e.target.closest('.btn-edit-barang');
                console.log('===> Tombol edit diklik');

                const barang = JSON.parse(button.getAttribute('data-barang'));
                console.log('Barang yang diklik:', barang);

                const modalEditBarang = new bootstrap.Modal(document.getElementById('modalEditBarang'));
                modalEditBarang.show();

                // Isi data form
                document.getElementById('formEditBarang').action = `/barang/${barang.id}`;
                document.getElementById('editNamaBarang').value = barang.nama_barang ?? '';
                document.getElementById('editKodeBarang').value = barang.kode_barang ?? '';
                document.getElementById('editMerkModel').value = barang.merk_model ?? '';
                document.getElementById('editNoSeriPabrik').value = barang.no_seri_pabrik ?? '';
                document.getElementById('editUkuran').value = barang.ukuran ?? '';
                document.getElementById('editBahan').value = barang.bahan ?? '';
                document.getElementById('editTahunPembuatanPembelian').value = barang.tahun_pembuatan_pembelian ??
                    '';
                document.getElementById('editJumlahBarang').value = barang.jumlah_barang ?? 1;
                document.getElementById('editHargaBeli').value = barang.harga_beli ?? '';
                document.getElementById('editSumber').value = barang.sumber ?? '';
                document.getElementById('editKeadaanBarang').value = barang.keadaan_barang ?? '';
                document.getElementById('editKeteranganMutasi').value = barang.keterangan_mutasi ?? '';
                document.getElementById('editIdRuangan').value = barang.id_ruangan ?? '';
            }
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
    </script>
@endpush
