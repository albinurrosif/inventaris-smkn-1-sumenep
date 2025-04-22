@extends('layouts.app')

@section('title', 'Data Ruangan')

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

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Ruangan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="card-title mb-0">Data Ruangan</h4>
                <button id="btnTambahRuangan" class="btn btn-primary btn-md" data-bs-toggle="tooltip"
                    title="Tambah ruangan">
                    <i class="mdi mdi-plus me-1"></i> Tambah Ruangan
                </button>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabelRuangan" class="table table-bordered dt-responsive nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama Ruangan</th>
                                <th>Operator</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ruangan as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->nama_ruangan }}</td>
                                    <td>{{ $item->operator->name ?? '-' }}</td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-warning btn-sm btn-edit-ruangan"
                                                data-ruangan='@json($item)'>
                                                <i class="mdi mdi-pencil"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm btn-delete-ruangan"
                                                data-id="{{ $item->id }}" data-nama="{{ $item->nama_ruangan }}">
                                                <i class="mdi mdi-trash-can"></i>
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

    {{-- Modal --}}
    @include('ruangan.partials.modal-create', ['operators' => $operators])
    @include('ruangan.partials.modal-edit', ['operators' => $operators])

    <form id="formDeleteRuangan" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show modal tambah
            const modalTambah = new bootstrap.Modal(document.getElementById('modalTambahRuangan'));
            document.getElementById('btnTambahRuangan').addEventListener('click', () => modalTambah.show());

            // Show modal edit
            const modalEdit = new bootstrap.Modal(document.getElementById('modalEditRuangan'));
            document.querySelectorAll('.btn-edit-ruangan').forEach(button => {
                button.addEventListener('click', () => {
                    const data = JSON.parse(button.dataset.ruangan);
                    document.getElementById('edit_nama_ruangan').value = data.nama_ruangan;
                    document.getElementById('edit_id_operator').value = data.id_operator ?? '';
                    document.getElementById('formEditRuangan').action = `/ruangan/${data.id}`;
                    modalEdit.show();
                });
            });

            // Tooltip
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(el => new bootstrap.Tooltip(el));
        });


        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btn-delete-ruangan').forEach(button => {
                button.addEventListener('click', function() {
                    const ruanganId = this.getAttribute('data-id');
                    const ruanganNama = this.getAttribute('data-nama');

                    Swal.fire({
                        title: 'Hapus Ruangan?',
                        text: `Ruangan "${ruanganNama}" akan dihapus secara permanen.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.getElementById('formDeleteRuangan');
                            form.action = `/ruangan/${ruanganId}`;
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
@endpush
