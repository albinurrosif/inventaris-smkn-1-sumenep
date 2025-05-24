@extends('layouts.app')

@section('title', 'Data Kategori Barang')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Kategori Barang</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="card-title mb-0">Data Kategori Barang</h4>
                <button id="btnTambahKategori" class="btn btn-primary btn-md" data-bs-toggle="tooltip"
                    title="Tambah kategori">
                    <i class="mdi mdi-plus me-1"></i> Tambah Kategori
                </button>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabelKategori" class="table table-bordered dt-responsive nowrap w-100 table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama Kategori</th>
                                <th>Jumlah Item</th>
                                <th>Total Unit</th>
                                <th>Unit Aktif</th>
                                <th>Nilai Total</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kategoriBarang as $index => $kategori)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <a
                                            href="{{ route('kategori-barang.show', $kategori->id) }}">{{ $kategori->nama_kategori }}</a>
                                    </td>
                                    <td>{{ $kategori->jumlah_item }}</td>
                                    <td>{{ $kategori->jumlah_unit }}</td>
                                    <td>{{ $kategori->unit_aktif }}</td>
                                    <td>Rp {{ number_format($kategori->nilai_total, 2) }}</td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-warning btn-sm btn-edit-kategori"
                                                data-kategori='@json($kategori)' data-bs-toggle="tooltip"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm btn-delete-kategori"
                                                data-id="{{ $kategori->id }}" data-nama="{{ $kategori->nama_kategori }}"
                                                data-bs-toggle="tooltip" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('admin.kategori.partials.modal-create', ['kategoriBarang' => $kategoriBarang])
    @include('admin.kategori.partials.modal-edit', ['kategoriBarang' => $kategoriBarang])

    <form id="formDeleteKategori" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show modal tambah
            const modalTambah = new bootstrap.Modal(document.getElementById('modalTambahKategori'));
            document.getElementById('btnTambahKategori').addEventListener('click', () => modalTambah.show());

            // Show modal edit
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

            // Tooltip
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(el => new bootstrap.Tooltip(el));

            // SweetAlert Delete Confirmation
            document.querySelectorAll('.btn-delete-kategori').forEach(button => {
                button.addEventListener('click', function() {
                    const kategoriId = this.getAttribute('data-id');
                    const kategoriNama = this.getAttribute('data-nama');

                    Swal.fire({
                        title: 'Hapus Kategori?',
                        text: `Kategori "${kategoriNama}" akan dihapus secara permanen.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.getElementById('formDeleteKategori');
                            form.action = `/kategori-barang/${kategoriId}`;
                            form.submit();
                        }
                    });
                });
            });

            // Initialize DataTable
            $('#tabelKategori').DataTable({
                responsive: true,
                paging: true,
                searching: true,
                info: true,
                ordering: true,
                columnDefs: [{
                    targets: [6],
                    orderable: false
                }]
            });
        });
    </script>
@endpush
