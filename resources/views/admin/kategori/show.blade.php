@php
    // use App\Models\Barang; // Tidak perlu jika hanya mengakses relasi
@endphp

@extends('layouts.app')

@section('title', 'Detail Kategori - ' . $kategoriBarang->nama_kategori)

@push('styles')
    {{-- Tambahkan CSS khusus jika diperlukan --}}
    <style>
        .dl-horizontal dt {
            white-space: normal;
            /* Memastikan teks panjang di <dt> tidak terpotong */
        }

        .table-sm th,
        .table-sm td {
            padding: 0.4rem;
            /* Padding lebih kecil untuk tabel ringkas */
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Detail Kategori: {{ $kategoriBarang->nama_kategori }}</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.kategori-barang.index') }}">Kategori
                                    Barang</a>
                            </li>
                            <li class="breadcrumb-item active">{{ $kategoriBarang->nama_kategori }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baris Judul Halaman dan Tombol Aksi Utama --}}
        <div class="row mb-3">
            <div class="col">
                {{-- Judul utama halaman sudah ada di page-title-box, bisa dikosongkan jika tidak perlu pengulangan --}}
                {{-- <h5 class="mb-0">Kategori: <span class="fw-semibold">{{ $kategoriBarang->nama_kategori }}</span></h5> --}}
            </div>
            <div class="col-auto">
                <div class="d-flex gap-2">
                    @can('update', $kategoriBarang)
                        <button type="button" class="btn btn-warning btn-sm btn-edit-kategori-trigger"
                            data-kategori='@json($kategoriBarang->only(['id', 'nama_kategori']))' data-bs-toggle="modal"
                            data-bs-target="#modalEditKategori" title="Edit Kategori {{ $kategoriBarang->nama_kategori }}">
                            <i class="fas fa-edit me-1"></i> Edit Kategori
                        </button>
                    @endcan
                    <a href="{{ route('admin.kategori-barang.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-5 mb-3 mb-lg-0">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Kategori</h6>
                        {{-- Tombol Edit dipindahkan ke atas --}}
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5 col-md-4">Nama Kategori</dt>
                            <dd class="col-sm-7 col-md-8"><span
                                    class="badge bg-dark">{{ $kategoriBarang->nama_kategori }}</span></dd>

                            <dt class="col-sm-5 col-md-4">Jumlah Jenis Barang (Induk)</dt>
                            <dd class="col-sm-7 col-md-8">{{ $kategoriBarang->jumlah_item_induk ?? 0 }} Jenis</dd>

                            <dt class="col-sm-5 col-md-4">Total Unit Fisik</dt>
                            <dd class="col-sm-7 col-md-8">{{ $kategoriBarang->jumlah_unit_total ?? 0 }} Unit</dd>

                            <dt class="col-sm-5 col-md-4">Total Unit Tersedia</dt>
                            <dd class="col-sm-7 col-md-8">{{ $kategoriBarang->jumlah_unit_tersedia ?? 0 }} Unit</dd>

                            <dt class="col-sm-5 col-md-4">Estimasi Nilai Aset</dt>
                            <dd class="col-sm-7 col-md-8"><span class="text-success fw-bold">Rp
                                    {{ number_format($kategoriBarang->nilai_total_estimasi ?? 0, 0, ',', '.') }}</span>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0"><i class="fas fa-boxes me-2"></i>Daftar Jenis Barang dalam Kategori
                            "{{ $kategoriBarang->nama_kategori }}"</h6>
                    </div>
                    <div class="card-body px-2 py-2">
                        @if ($kategoriBarang->barangs && $kategoriBarang->barangs->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0 align-middle"
                                    id="tabelJenisBarangDalamKategori">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%;" class="text-center">No</th>
                                            <th>Nama Jenis Barang</th>
                                            <th>Kode Barang</th>
                                            <th class="text-center">Unit Aktif</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($kategoriBarang->barangs as $index => $barang)
                                            <tr>
                                                <td class="text-center">{{ $loop->iteration }}</td>
                                                <td>
                                                    @can('view', $barang)
                                                        <a
                                                            href="{{ route('admin.barang.show', $barang->id) }}">{{ $barang->nama_barang }}</a>
                                                    @else
                                                        {{ $barang->nama_barang }}
                                                    @endcan
                                                </td>
                                                <td>{{ $barang->kode_barang }}</td>
                                                <td class="text-center">
                                                    <span class="badge bg-info">
                                                        {{ $barang->jumlah_unit_aktif_per_barang ?? 0 }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info mb-0 text-center mx-3">
                                Belum ada jenis barang (induk) dalam kategori ini.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Edit disertakan di sini karena tombol edit ada di halaman ini --}}
    @can('update', $kategoriBarang)
        @include('admin.kategori.partials.modal-edit') {{-- Pastikan path partial ini benar --}}
    @endcan
@endsection

@push('scripts')
    {{-- <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script> --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalEditKategoriElement = document.getElementById('modalEditKategori');
            if (modalEditKategoriElement) {
                const modalEdit = new bootstrap.Modal(modalEditKategoriElement);
                document.querySelectorAll('.btn-edit-kategori-trigger').forEach(button => {
                    button.addEventListener('click', () => {
                        const data = JSON.parse(button.dataset.kategori);
                        const form = modalEditKategoriElement.querySelector(
                            '#formEditKategoriAction');
                        const titleSpan = modalEditKategoriElement.querySelector(
                            '#editNamaKategoriTitleModal');

                        if (form) {
                            form.action =
                                `{{ route('admin.kategori-barang.update', ['kategori_barang' => ':id']) }}`
                                .replace(':id', data.id);
                            form.querySelector('#edit_modal_nama_kategori').value = data
                                .nama_kategori || '';

                            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove(
                                'is-invalid'));
                            form.querySelectorAll('.invalid-feedback').forEach(el => el
                                .textContent = '');
                        }
                        if (titleSpan) {
                            titleSpan.textContent = data.nama_kategori || '';
                        } else {
                            modalEditKategoriElement.querySelector('.modal-title').textContent =
                                'Edit Kategori: ' + (data.nama_kategori || '');
                        }
                        modalEdit.show();
                    });
                });
            }

            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>
@endpush
