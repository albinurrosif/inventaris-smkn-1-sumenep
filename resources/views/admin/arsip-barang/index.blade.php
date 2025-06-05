@extends('layouts.app')

@section('title', 'Riwayat Arsip Barang')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        #arsipTable th,
        #arsipTable td {
            vertical-align: middle;
            font-size: 0.85rem;
            /* Ukuran font lebih kecil untuk tabel padat */
        }

        .btn-sm i {
            /* Ukuran ikon pada tombol kecil */
            font-size: 0.9rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Riwayat Arsip & Penghapusan Barang</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Arsip Barang</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-archive me-2"></i>Filter Riwayat Arsip</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.arsip-barang.index') }}">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label for="search_arsip" class="form-label">Pencarian</label>
                            <input type="text" name="search" id="search_arsip" class="form-control form-control-sm"
                                placeholder="Nama barang, kode inventaris, no seri..." value="{{ $searchTerm ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label for="jenis_penghapusan_filter" class="form-label">Jenis Penghapusan</label>
                            <select name="jenis_penghapusan" id="jenis_penghapusan_filter"
                                class="form-select form-select-sm">
                                <option value="">-- Semua Jenis --</option>
                                @foreach ($jenisPenghapusanList as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ ($jenisPenghapusanFilter ?? '') == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="tanggal_mulai_filter" class="form-label">Tgl. Hapus Mulai</label>
                            <input type="date" name="tanggal_mulai" id="tanggal_mulai_filter"
                                class="form-control form-control-sm" value="{{ $tanggalMulaiFilter ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label for="tanggal_selesai_filter" class="form-label">Tgl. Hapus Sampai</label>
                            <input type="date" name="tanggal_selesai" id="tanggal_selesai_filter"
                                class="form-control form-control-sm" value="{{ $tanggalSelesaiFilter ?? '' }}">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button class="btn btn-primary btn-sm w-100" type="submit"><i
                                    class="fas fa-filter"></i></button>
                        </div>
                        @if ($searchTerm || $jenisPenghapusanFilter || $tanggalMulaiFilter || $tanggalSelesaiFilter)
                            <div class="col-md-12 mt-2 text-end">
                                <a href="{{ route('admin.arsip-barang.index') }}" class="btn btn-outline-secondary btn-sm"
                                    title="Reset Filter">
                                    <i class="fas fa-times"></i> Reset Filter
                                </a>
                            </div>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Daftar Barang Diarsipkan</h5>
            </div>
            <div class="card-body">

                <div class="table-responsive">
                    <table id="arsipTable" class="table table-bordered table-hover dt-responsive nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">No</th>
                                <th>Kode Inventaris</th>
                                <th>No. Seri</th>
                                <th>Nama Barang</th>
                                <th>Kategori</th>
                                <th>Lokasi Terakhir</th>
                                <th>Jenis Hapus</th>
                                <th>Alasan</th>
                                <th>Tgl. Hapus</th>
                                <th>Oleh</th>
                                <th class="text-center">Status Unit</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($arsipList as $index => $arsip)
                                <tr>
                                    <td class="text-center">{{ $arsipList->firstItem() + $index }}</td>
                                    <td>
                                        @if ($arsip->barangQrCode)
                                            <a href="{{ route('barang-qr-code.show', $arsip->id_barang_qr_code) }}"
                                                target="_blank">
                                                <code>{{ $arsip->barangQrCode->kode_inventaris_sekolah ?? 'N/A' }}</code>
                                            </a>
                                        @else
                                            <span class="text-muted">N/A (Unit Mungkin Telah Dihapus Permanen)</span>
                                        @endif
                                    </td>
                                    <td>{{ $arsip->barangQrCode->no_seri_pabrik ?? ($arsip->barangQrCode->no_seri_internal ?? '-') }}
                                    </td>
                                    <td>{{ $arsip->barangQrCode->barang->nama_barang ?? 'N/A' }}</td>
                                    <td>
                                        <span
                                            class="badge bg-secondary">{{ $arsip->barangQrCode->barang->kategori->nama_kategori ?? '-' }}</span>
                                    </td>
                                    <td>
                                        {{ $arsip->ruanganSaatDiarsipkan->nama_ruangan ?? ($arsip->pemegangSaatDiarsipkan->username ? 'Pribadi: ' . $arsip->pemegangSaatDiarsipkan->username : 'Tidak Diketahui') }}
                                    </td>
                                    <td><span class="badge bg-warning text-dark">{{ $arsip->jenis_penghapusan }}</span>
                                    </td>
                                    <td>{{ Str::limit($arsip->alasan_penghapusan, 50) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($arsip->tanggal_penghapusan)->isoFormat('DD MMM YYYY') }}
                                    </td>
                                    <td>{{ $arsip->userPenyetuju->username ?? ($arsip->userPengaju->username ?? '-') }}
                                    </td>
                                    <td class="text-center">
                                        @if ($arsip->barangQrCode)
                                            @if ($arsip->barangQrCode->trashed())
                                                <span class="badge bg-danger">Diarsipkan</span>
                                            @else
                                                <span class="badge bg-success">Aktif (Dipulihkan)</span>
                                            @endif
                                        @else
                                            <span class="badge bg-dark">Dihapus Permanen</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($arsip->barangQrCode && $arsip->barangQrCode->trashed())
                                            @can('restore', $arsip->barangQrCode)
                                                {{-- Otorisasi pada BarangQrCode --}}
                                                <form action="{{ route('admin.arsip-barang.restore', $arsip->id) }}"
                                                    method="POST" class="d-inline form-restore-arsip">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm btn-restore"
                                                        data-bs-toggle="tooltip"
                                                        title="Pulihkan Unit Barang {{ $arsip->barangQrCode->kode_inventaris_sekolah }}">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        @endif
                                        {{-- Tombol lihat detail arsip --}}
                                        @can('view', $arsip)
                                            <a href="{{ route('admin.arsip-barang.show', $arsip->id) }}"
                                                class="btn btn-info btn-sm" data-bs-toggle="tooltip" title="Detail Arsip">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center">
                                        @if ($searchTerm || $jenisPenghapusanFilter || $tanggalMulaiFilter || $tanggalSelesaiFilter)
                                            Tidak ada data arsip yang cocok dengan kriteria filter Anda.
                                        @else
                                            Belum ada data barang yang diarsipkan.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($arsipList instanceof \Illuminate\Pagination\LengthAwarePaginator && $arsipList->hasPages())
                    <div class="mt-3 d-flex justify-content-end">
                        {{ $arsipList->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if ($.fn.DataTable.isDataTable('#arsipTable')) {
                $('#arsipTable').DataTable().destroy();
            }
            if ($('#arsipTable tbody tr').length > 0 && !$('#arsipTable tbody tr td[colspan="12"]').length) {
                $('#arsipTable').DataTable({
                    responsive: true,
                    paging: false, // Paginasi dikontrol oleh Laravel
                    searching: false, // Pencarian dikontrol oleh form di atas
                    info: false, // Info entri dikontrol oleh Laravel
                    ordering: true,
                    order: [
                        [8, 'desc']
                    ], // Default sort by Tanggal Hapus (index 8) descending
                    columnDefs: [{
                            targets: [0, 11],
                            orderable: false,
                            searchable: false
                        }, // No dan Aksi
                        {
                            targets: [10],
                            className: 'text-center'
                        } // Status Unit
                    ],
                    language: { // Opsi untuk melokalisasi DataTables ke Bahasa Indonesia
                        sEmptyTable: "Tidak ada data yang tersedia pada tabel ini",
                        sProcessing: "Sedang memproses...",
                        sLengthMenu: "Tampilkan _MENU_ entri",
                        sZeroRecords: "Tidak ditemukan data yang sesuai",
                        sInfo: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                        sInfoEmpty: "Menampilkan 0 sampai 0 dari 0 entri",
                        sInfoFiltered: "(disaring dari _MAX_ entri keseluruhan)",
                        sInfoPostFix: "",
                        sSearch: "Cari:",
                        sUrl: "",
                        oPaginate: {
                            sFirst: "Pertama",
                            sPrevious: "Sebelumnya",
                            sNext: "Selanjutnya",
                            sLast: "Terakhir"
                        }
                    },
                });
            }

            // Handle Restore Confirmation
            document.querySelectorAll('.form-restore-arsip').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const kodeInventaris = this.closest('tr').querySelector('td:nth-child(2) code')
                        ?.textContent.trim() || 'Unit Barang';
                    Swal.fire({
                        title: 'Konfirmasi Pulihkan Unit Barang',
                        html: `Anda yakin ingin memulihkan unit barang <strong>${kodeInventaris}</strong> dari arsip?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Pulihkan!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endpush
