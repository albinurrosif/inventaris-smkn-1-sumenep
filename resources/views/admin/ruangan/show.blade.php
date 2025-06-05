@php
    // Untuk menggunakan konstanta kondisi dari model BarangQrCode
    use App\Models\BarangQrCode;
@endphp

@extends('layouts.app')

@section('title', 'Detail Ruangan - ' . $ruangan->nama_ruangan)

@push('styles')
    {{-- Tambahkan style khusus jika ada, misalnya untuk DataTable --}}
    <style>
        /* Gaya dari canvas sebelumnya */
        .dl-horizontal dt {
            white-space: normal;
        }

        .table-sm th,
        .table-sm td {
            padding: 0.4rem;
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Detail Ruangan: {{ $ruangan->nama_ruangan }}</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.ruangan.index') }}">Ruangan</a></li>
                            <li class="breadcrumb-item active">Detail: {{ $ruangan->nama_ruangan }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col">
                {{-- Judul utama halaman sudah ada di page-title-box --}}
            </div>
            <div class="col-auto">
                <div class="d-flex gap-2">
                    @can('update', $ruangan)
                        @if (Auth::user()->hasRole(\App\Models\User::ROLE_ADMIN) ||
                                (Auth::user()->hasRole(\App\Models\User::ROLE_OPERATOR) && $ruangan->id_operator == Auth::id()))
                            {{-- Tombol Edit sekarang memicu modal --}}
                            <button type="button" class="btn btn-warning btn-sm btn-edit-ruangan-trigger"
                                data-ruangan='{!! json_encode($ruangan->only(['id', 'nama_ruangan', 'kode_ruangan', 'id_operator'])) !!}' data-bs-toggle="modal" data-bs-target="#modalEditRuangan"
                                title="Edit Ruangan {{ $ruangan->nama_ruangan }}">
                                <i class="fas fa-edit me-1"></i> Edit Ruangan
                            </button>
                        @endif
                    @endcan
                    <a href="{{ route('admin.ruangan.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar Ruangan
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 col-md-12 mb-3"> {{-- Diubah menjadi lg-6 untuk layout 2 kolom --}}
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Ruangan</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Kode Ruangan</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-dark">{{ $ruangan->kode_ruangan }}</span>
                            </dd>

                            <dt class="col-sm-5">Nama Ruangan</dt>
                            <dd class="col-sm-7">{{ $ruangan->nama_ruangan }}</dd>

                            <dt class="col-sm-5">Operator Penanggung Jawab</dt>
                            <dd class="col-sm-7">{{ $ruangan->operator->username ?? '-' }}</dd>

                            <dt class="col-sm-5">Total Unit Barang Aktif</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-info">{{ $totalUnit }} unit</span>
                            </dd>

                            <dt class="col-sm-5">Estimasi Nilai Total Aset</dt>
                            <dd class="col-sm-7">
                                <span class="text-success fw-bold">Rp {{ number_format($totalValue, 0, ',', '.') }}</span>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-12 mb-3"> {{-- Diubah menjadi lg-6 untuk layout 2 kolom --}}
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-boxes me-2"></i>Ringkasan Jenis Barang</h5>
                    </div>
                    <div class="card-body">
                        @if ($unitBarang->count() > 0)
                            @php
                                $barangGrouped = $unitBarang
                                    ->loadMissing('barang.kategori')
                                    ->groupBy('barang.nama_barang');
                            @endphp
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Jenis Barang</th>
                                            <th>Kategori</th>
                                            <th class="text-center">Jumlah Unit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($barangGrouped as $namaBarang => $items)
                                            @php $firstItem = $items->first(); @endphp
                                            <tr>
                                                <td>
                                                    <a href="{{ route('barang.show', $firstItem->barang->id) }}">
                                                        {{ $namaBarang }}
                                                    </a>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        {{ $firstItem->barang->kategori->nama_kategori ?? '-' }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-primary rounded-pill">{{ $items->count() }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info mb-0 text-center">
                                <i class="fas fa-info-circle me-2"></i>
                                Belum ada unit barang aktif yang tercatat di ruangan ini.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if ($unitBarang->count() > 0)
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-barcode me-2"></i>Daftar Unit Barang di Ruangan Ini
                            </h5>
                            <div class="text-muted">{{ $unitBarang->count() }} unit barang aktif</div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover dt-responsive nowrap align-middle w-100"
                                    id="tabelUnitBarangDiRuangan">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Kode Inventaris</th>
                                            <th>Nama Jenis Barang</th>
                                            <th>Kategori</th>
                                            <th>Kondisi</th>
                                            <th>Pemegang Personal</th>
                                            <th class="text-end">Harga Perolehan (Rp)</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($unitBarang as $index => $unit)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <a href="{{ route('barang-qr-code.show', $unit->id) }}">
                                                        <code>{{ $unit->kode_inventaris_sekolah }}</code>
                                                    </a>
                                                </td>
                                                <td>
                                                    <a href="{{ route('barang.show', $unit->barang->id) }}">
                                                        {{ $unit->barang->nama_barang }}
                                                    </a>
                                                    @if ($unit->barang->merk_model)
                                                        <small
                                                            class="d-block text-muted">{{ $unit->barang->merk_model }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">
                                                        {{ $unit->barang->kategori->nama_kategori ?? '-' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @php
                                                        $kondisiClass = match ($unit->kondisi) {
                                                            BarangQrCode::KONDISI_BAIK => 'bg-success',
                                                            BarangQrCode::KONDISI_KURANG_BAIK => 'bg-warning text-dark',
                                                            BarangQrCode::KONDISI_RUSAK_BERAT => 'bg-danger',
                                                            BarangQrCode::KONDISI_HILANG => 'bg-dark',
                                                            default => 'bg-secondary',
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $kondisiClass }}">
                                                        {{ $unit->kondisi }}
                                                    </span>
                                                </td>
                                                <td>{{ $unit->pemegangPersonal->username ?? '-' }}</td>
                                                <td class="text-end">
                                                    {{ number_format($unit->harga_perolehan_unit, 0, ',', '.') }}
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('barang-qr-code.show', $unit->id) }}"
                                                        class="btn btn-outline-primary btn-sm" title="Lihat Detail Unit">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="6" class="text-end fw-bold">Total Estimasi Nilai Aset di Ruangan
                                                Ini:</th>
                                            <th class="text-end fw-bold">
                                                Rp {{ number_format($totalValue, 0, ',', '.') }}
                                            </th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            @if ($unitBarang instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                <div class="mt-3 d-flex justify-content-center">
                                    {{ $unitBarang->appends(request()->query())->links() }} {{-- Menambahkan query string --}}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Include Modal Edit Ruangan --}}
    {{-- Pastikan $operators di-pass dari RuanganController@show --}}
    @can('update', $ruangan)
        @if (Auth::user()->hasRole(\App\Models\User::ROLE_ADMIN) ||
                (Auth::user()->hasRole(\App\Models\User::ROLE_OPERATOR) && $ruangan->id_operator == Auth::id()))
            @include('admin.ruangan.partials.modal-edit', ['operators' => $operators ?? collect()])
        @endif
    @endcan

@endsection

@push('scripts')
    {{-- <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script> --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> {{-- Jika belum ada di layout utama --}}

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DataTable Initialization untuk tabel unit barang
            if ($('#tabelUnitBarangDiRuangan').length && $('#tabelUnitBarangDiRuangan tbody tr').length > 0 && !$(
                    '#tabelUnitBarangDiRuangan tbody tr td[colspan]').length) {
                $('#tabelUnitBarangDiRuangan').DataTable({
                    responsive: true,
                    paging: {{ $unitBarang instanceof \Illuminate\Pagination\LengthAwarePaginator ? 'false' : 'true' }},
                    searching: true,
                    info: {{ $unitBarang instanceof \Illuminate\Pagination\LengthAwarePaginator ? 'false' : 'true' }},
                    ordering: true,
                    order: [
                        [1, 'asc']
                    ],
                    pageLength: 25,
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                    },
                    columnDefs: [{
                            targets: [0, 7],
                            orderable: false
                        },
                        {
                            targets: [6],
                            className: 'text-end'
                        }
                    ]
                });
            }

            // Logika untuk modal edit ruangan
            const modalEditRuanganElement = document.getElementById('modalEditRuangan');
            if (modalEditRuanganElement) {
                const modalEdit = new bootstrap.Modal(modalEditRuanganElement);
                document.querySelectorAll('.btn-edit-ruangan-trigger').forEach(button => {
                    button.addEventListener('click', () => {
                        const data = JSON.parse(button.dataset.ruangan);
                        const form = modalEditRuanganElement.querySelector(
                            '#formEditRuanganAction');
                        const titleSpan = modalEditRuanganElement.querySelector(
                            '#editNamaRuanganTitleModal');

                        if (form) {
                            form.action =
                                `{{ route('admin.ruangan.update', ['ruangan' => ':id']) }}`
                                .replace(':id', data.id);
                            form.querySelector('#edit_modal_nama_ruangan').value = data
                                .nama_ruangan || '';
                            form.querySelector('#edit_modal_kode_ruangan').value = data
                                .kode_ruangan || '';
                            form.querySelector('#edit_modal_id_operator').value = data
                                .id_operator || '';
                            // Reset validasi error sebelumnya
                            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove(
                                'is-invalid'));
                            form.querySelectorAll('.invalid-feedback').forEach(el => el
                                .textContent = '');
                        }
                        if (titleSpan) {
                            titleSpan.textContent = data.nama_ruangan || '';
                        } else {
                            modalEditRuanganElement.querySelector('.modal-title').textContent =
                                'Edit Ruangan: ' + (data.nama_ruangan || '');
                        }
                        modalEdit.show();
                    });
                });
            }

            // Tooltip (Bootstrap 5)
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endpush
