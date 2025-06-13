@extends('layouts.app')

@section('title', 'Daftar Jenis Barang')

@push('styles')
@endpush

@section('content')
    {{-- Notifikasi untuk kegagalan import --}}
    @if (session('failures'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let failureMessages = '<ul class="text-start ps-3">';
                @foreach (session('failures') as $failure)
                    failureMessages +=
                        `<li class="mb-1">Baris {{ $failure->row() }}: {{ implode(', ', $failure->errors()) }}</li>`;
                @endforeach
                failureMessages += '</ul>';

                Swal.fire({
                    icon: 'error',
                    title: 'Import Gagal',
                    html: failureMessages,
                    showConfirmButton: true,
                    position: 'center',
                    customClass: {
                        htmlContainer: 'text-start'
                    }
                });
            });
        </script>
    @endif

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Daftar Jenis Barang (Induk)</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Daftar Jenis Barang</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Filter --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter & Pencarian Jenis Barang</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route($rolePrefix . 'barang.index') }}" id="filterFormJenisBarang">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="id_kategori_filter" class="form-label mb-1">Kategori</label>
                            <select name="id_kategori" id="id_kategori_filter" class="form-control" {{-- Choices.js akan menggantikan ini --}}
                                data-choices-removeItemButton="true"
                                onchange="document.getElementById('filterFormJenisBarang').submit()">
                                <option value="">-- Semua Kategori --</option>
                                @foreach ($kategoriList as $kategori)
                                    <option value="{{ $kategori->id }}"
                                        {{ ($kategoriId ?? '') == $kategori->id ? 'selected' : '' }}>
                                        {{ $kategori->nama_kategori }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="id_ruangan_filter" class="form-label mb-1">Ruangan (Unit)</label>
                            <select name="id_ruangan" id="id_ruangan_filter" class="form-control" {{-- Choices.js akan menggantikan ini --}}
                                data-choices-removeItemButton="true"
                                onchange="document.getElementById('filterFormJenisBarang').submit()">
                                <option value="">-- Semua Ruangan --</option>
                                @foreach ($ruanganList as $ruanganItem)
                                    <option value="{{ $ruanganItem->id }}"
                                        {{ ($ruanganId ?? '') == $ruanganItem->id ? 'selected' : '' }}>
                                        {{ $ruanganItem->nama_ruangan }} ({{ $ruanganItem->kode_ruangan }})
                                    </option>
                                @endforeach
                                <option value="tanpa-ruangan"
                                    {{ ($ruanganId ?? '') == 'tanpa-ruangan' ? 'selected' : '' }}>
                                    Tanpa Ruangan (Dipegang Personal/Baru)
                                </option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="search_filter" class="form-label mb-1">Pencarian</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="search" id="search_filter" class="form-control"
                                    placeholder="Nama, Kode, Merk/Model..." value="{{ $searchTerm ?? '' }}">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-md-2 d-grid">
                            <label for="btn_reset_filter_barang" class="form-label mb-1">&nbsp;</label>
                            {{-- Label kosong untuk alignment --}}
                            <a href="{{ route($rolePrefix . 'barang.index') }}" id="btn_reset_filter_barang"
                                class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-sync-alt me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tombol Aksi Global & Tambah --}}
        <div class="mb-3 d-flex justify-content-end align-items-center">
            <div class="d-flex align-items-center gap-2">
                {{-- @can('export', App\Models\BarangQrCode::class)
                    <a href="{{ route($rolePrefix . 'barang-qr-code.export-excel', array_merge(request()->query(), ['search' => $searchTerm ?? null, 'id_ruangan' => $ruanganId ?? null, 'id_kategori' => $kategoriId ?? null])) }}"
                        class="btn btn-outline-success btn-sm">
                        <i class="mdi mdi-file-excel me-1"></i>Export Excel (Unit)
                    </a>
                    <a href="{{ route($rolePrefix . 'barang-qr-code.export-pdf', array_merge(request()->query(), ['search' => $searchTerm ?? null, 'id_ruangan' => $ruanganId ?? null, 'id_kategori' => $kategoriId ?? null, 'pisah_per_ruangan' => false])) }}"
                        class="btn btn-outline-danger btn-sm">
                        <i class="mdi mdi-file-pdf-box me-1"></i> Export PDF (Unit Semua)
                    </a>
                    <a href="{{ route($rolePrefix . 'barang-qr-code.export-pdf', array_merge(request()->query(), ['search' => $searchTerm ?? null, 'id_ruangan' => $ruanganId ?? null, 'id_kategori' => $kategoriId ?? null, 'pisah_per_ruangan' => true])) }}"
                        class="btn btn-danger btn-sm">
                        <i class="mdi mdi-file-pdf-box me-1"></i> Export PDF (Unit Per Ruangan)
                    </a>
                @endcan --}}
                @can('import', App\Models\Barang::class)
                    <button type="button" class="btn btn-outline-info btn-sm"
                        onclick="document.getElementById('fileInputImportBarang').click();">
                        <i class="mdi mdi-upload me-1"></i> Import Barang
                    </button>
                    <form id="importFormBarang" action="{{ route($rolePrefix . 'barang.import.all') }}" method="POST"
                        enctype="multipart/form-data" class="d-none">
                        @csrf
                        <input type="file" name="file" id="fileInputImportBarang" accept=".csv,.xlsx"
                            onchange="document.getElementById('importFormBarang').submit()">
                    </form>
                @endcan
                @can('create', App\Models\Barang::class)
                    <a href="{{ route($rolePrefix . 'barang.create') }}" class="btn btn-success btn-sm">
                        <i class="mdi mdi-plus me-1"></i> Tambah Jenis Barang
                    </a>
                @endcan
            </div>
        </div>

        {{-- Card Tabel Data --}}
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0"><i class="mdi mdi-format-list-bulleted me-2"></i>Data Jenis Barang</h4>
            </div>
            <div class="card-body">
                @if ($operatorTidakAdaRuangan ?? false)
                    <div class="alert alert-warning text-center" role="alert">
                        Anda adalah Operator dan saat ini tidak ditugaskan untuk mengelola ruangan manapun. <br> Tidak ada
                        jenis barang yang dapat ditampilkan sesuai lingkup Anda. Silakan hubungi Admin.
                    </div>
                @endif
                <div class="table-responsive">
                    <table id="barangTable" class="table table-hover table-striped dt-responsive align-middle nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nama Barang</th>
                                <th>Kode</th>
                                <th>Kategori</th>
                                <th>Merk/Model</th>
                                <th>Tahun</th>
                                <th class="text-center">Jml. Unit Aktif</th>
                                <th class="text-end">Harga Induk (Rp)</th>
                                <th>Sumber Induk</th>
                                <th style="width: 120px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($barangs as $index => $item)
                                <tr>
                                    <td>{{ $barangs->firstItem() + $index }}</td>
                                    <td>
                                        <a href="{{ route($rolePrefix . 'barang.show', $item->id) }}"
                                            class="fw-medium">{{ $item->nama_barang }}</a>
                                        <small class="d-block text-muted">
                                            {{ $item->menggunakan_nomor_seri ? 'Perlu No. Seri Unit' : 'Tidak Perlu No. Seri Unit' }}
                                        </small>
                                    </td>
                                    <td>{{ $item->kode_barang }}</td>
                                    <td>{{ $item->kategori->nama_kategori ?? '-' }}</td>
                                    <td>{{ $item->merk_model ?? '-' }}</td>
                                    <td>{{ $item->tahun_pembuatan ?? '-' }}</td>
                                    <td class="text-center">{{ $item->active_qr_codes_count }}</td>
                                    <td class="text-end">
                                        {{ $item->harga_perolehan_induk ? number_format($item->harga_perolehan_induk, 0, ',', '.') : '-' }}
                                    </td>
                                    <td>{{ $item->sumber_perolehan_induk ?? '-' }}</td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            @can('view', $item)
                                                <a href="{{ route($rolePrefix . 'barang.show', $item->id) }}"
                                                    class="btn btn-outline-info btn-sm" title="Lihat Detail & Unit">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            @endcan
                                            {{-- @can('update', $item)
                                                <a href="{{ route('barang.edit', $item->id) }}"
                                                    class="btn btn-outline-warning btn-sm" title="Edit Info Induk">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endcan
                                            @can('delete', $item)
                                                <button type="button" class="btn btn-outline-danger btn-sm btn-delete-barang"
                                                    data-id="{{ $item->id }}" data-nama="{{ $item->nama_barang }}"
                                                    data-route="{{ route('barang.destroy', $item->id) }}"
                                                    title="Hapus Jenis Barang & Arsipkan Unitnya">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endcan --}}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">
                                        @if ($operatorTidakAdaRuangan ?? false)
                                            <i class="fas fa-folder-open fs-3 text-muted mb-2"></i><br>
                                            Anda tidak memiliki akses ke jenis barang manapun karena tidak ada ruangan yang
                                            dikelola.
                                        @else
                                            <i class="fas fa-folder-open fs-3 text-muted mb-2"></i><br>
                                            Jenis barang tidak ditemukan. Silakan coba dengan filter lain atau tambahkan
                                            jenis barang baru.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- @if ($barangs->hasPages())
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $barangs->links() }}
                    </div>
                @endif --}}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const kategoriFilterEl = document.getElementById('id_kategori_filter');
            if (kategoriFilterEl) {
                new Choices(kategoriFilterEl, {
                    removeItemButton: true,
                    searchPlaceholderValue: "Cari kategori...",

                    allowHTML: true,
                    noResultsText: 'Tidak ada hasil ditemukan',
                    noChoicesText: 'Tidak ada pilihan untuk dipilih'
                });
            }

            const ruanganFilterEl = document.getElementById('id_ruangan_filter');
            if (ruanganFilterEl) {
                new Choices(ruanganFilterEl, {
                    removeItemButton: true,
                    searchPlaceholderValue: "Cari ruangan...",
                    allowHTML: true,
                    noResultsText: 'Tidak ada hasil ditemukan',
                    noChoicesText: 'Tidak ada pilihan untuk dipilih'
                });
            }

            // DataTable Initialization
            if ($.fn.DataTable.isDataTable('#barangTable')) {
                $('#barangTable').DataTable().destroy();
            }
            if ($('#barangTable tbody tr').length > 0 && !$('#barangTable tbody tr td[colspan="10"]').length) {
                $('#barangTable').DataTable({
                    responsive: true,
                    dom: 'lrtip', // Menyembunyikan length dan search global
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
                    order: [1, 'asc'], // Biarkan server-side ordering
                    //     paging: false, // Matikan Paging DataTables
                    //     info: false, // Matikan Info DataTables
                    //     searching: false // Matikan Search DataTables
                });
            }

            // Delete Button Logic
            document.addEventListener('click', function(e) {
                const deleteBtn = e.target.closest('.btn-delete-barang');
                if (deleteBtn) {
                    e.preventDefault();
                    const barangNama = deleteBtn.getAttribute('data-nama');
                    const actionUrl = deleteBtn.getAttribute('data-route');

                    Swal.fire({
                        title: 'Hapus Jenis Barang: ' + barangNama + '?',
                        html: `
                        <p class="text-danger mb-2 text-start">Perhatian: Menghapus jenis barang ini akan otomatis mengarsipkan semua unit fisik aktif yang terkait dengannya.</p>
                        <form id="swalFormDeleteBarang" class="text-start">
                           <input type="hidden" name="_token" value="{{ csrf_token() }}">
                           <input type="hidden" name="_method" value="DELETE">
                            <div class="mb-2">
                                <label for="swal_jenis_penghapusan" class="form-label">Jenis Penghapusan Unit<span class="text-danger">*</span>:</label>
                                <select name="jenis_penghapusan" id="swal_jenis_penghapusan" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Jenis --</option>
                                    @foreach (\App\Models\ArsipBarang::getValidJenisPenghapusan() as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label for="swal_alasan_penghapusan" class="form-label">Alasan Penghapusan Unit<span class="text-danger">*</span>:</label>
                                <textarea name="alasan_penghapusan" id="swal_alasan_penghapusan" class="form-control form-control-sm" rows="2" placeholder="Alasan mengapa semua unit dari jenis ini dihapus/diarsipkan" required></textarea>
                            </div>
                            <div class="mb-2">
                                <label for="swal_berita_acara_path" class="form-label">Berita Acara (Opsional):</label>
                                <input type="file" name="berita_acara_path" id="swal_berita_acara_path" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            </div>
                            <div class="mb-2">
                                <label for="swal_foto_bukti_path" class="form-label">Foto Bukti (Opsional):</label>
                                <input type="file" name="foto_bukti_path" id="swal_foto_bukti_path" class="form-control form-control-sm" accept=".jpg,.jpeg,.png">
                            </div>
                            <div class="mb-1">
                                <label for="swal_konfirmasi_hapus_semua" class="form-label">Ketik <strong class="text-danger">HAPUS SEMUA</strong> untuk konfirmasi<span class="text-danger">*</span>:</label>
                                <input type="text" name="konfirmasi_hapus_semua" id="swal_konfirmasi_hapus_semua" class="form-control form-control-sm" required autocomplete="off" placeholder="HAPUS SEMUA">
                            </div>
                        </form>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Hapus & Arsipkan Unit!',
                        cancelButtonText: 'Batal',
                        customClass: {
                            popup: 'swal2-sm',
                            htmlContainer: 'text-start',
                        },
                        focusConfirm: false,
                        preConfirm: () => {
                            const swalForm = Swal.getPopup().querySelector(
                                '#swalFormDeleteBarang');
                            const jenis = swalForm.querySelector(
                                    '#swal_jenis_penghapusan')
                                .value;
                            const alasan = swalForm.querySelector(
                                    '#swal_alasan_penghapusan')
                                .value;
                            const konfirmasi = swalForm.querySelector(
                                '#swal_konfirmasi_hapus_semua').value;
                            if (!jenis) {
                                Swal.showValidationMessage(
                                    `Jenis penghapusan wajib dipilih.`);
                                return false;
                            }
                            if (!alasan) {
                                Swal.showValidationMessage(
                                    `Alasan penghapusan wajib diisi.`);
                                return false;
                            }
                            if (konfirmasi.toUpperCase() !== 'HAPUS SEMUA') {
                                Swal.showValidationMessage(
                                    `Ketik "HAPUS SEMUA" dengan benar untuk konfirmasi.`
                                );
                                return false;
                            }
                            return new FormData(swalForm);
                        }
                    }).then((result) => {
                        if (result.isConfirmed && result.value instanceof FormData) {
                            const formData = result.value;
                            Swal.fire({
                                title: 'Memproses...',
                                text: 'Mohon tunggu sebentar.',
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            fetch(actionUrl, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]').getAttribute(
                                        'content')
                                }
                            }).then(response => {
                                if (response.headers.get("content-type") && response
                                    .headers
                                    .get("content-type").indexOf(
                                        "application/json") !== -1
                                ) {
                                    return response.json().then(data => ({
                                        status: response.status,
                                        ok: response.ok,
                                        body: data,
                                        redirected: response.redirected,
                                        url: response.url
                                    }));
                                } else {
                                    return {
                                        status: response.status,
                                        ok: response.ok,
                                        body: null,
                                        redirected: response.redirected,
                                        url: response.url
                                    };
                                }
                            }).then(res => {
                                if (res.ok) {
                                    if (res.redirected) {
                                        window.location.href = res.url;
                                    } else if (res.body && res.body.success) {
                                        Swal.fire('Berhasil!', res.body.message ||
                                            'Jenis barang dan unitnya telah diarsipkan.',
                                            'success').then(() => location
                                            .reload());
                                    } else if (res.body && !res.body.success) {
                                        Swal.fire('Gagal!', res.body.message ||
                                            'Terjadi kesalahan saat menghapus.',
                                            'error'
                                        );
                                    } else {
                                        Swal.fire('Berhasil!', 'Proses selesai.',
                                                'success')
                                            .then(() => location.reload());
                                    }
                                } else {
                                    const errorMessage = res.body && res.body
                                        .message ? res
                                        .body.message :
                                        `Gagal menghapus. Status: ${res.status}`;
                                    Swal.fire('Gagal!', errorMessage, 'error');
                                }
                            }).catch(error => {
                                Swal.fire('Error!',
                                    'Terjadi kesalahan jaringan atau sistem.',
                                    'error');
                                console.error('Fetch error:', error);
                            });
                        }
                    });
                }
            });
        });
    </script>
@endpush
