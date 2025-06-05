{{-- resources/views/admin/barang/show.blade.php --}}
{{--
    Halaman ini menampilkan detail untuk satu Jenis Barang (Induk/Master).
    Juga menampilkan daftar semua unit fisik (Barang QR Code) yang terkait dengan jenis barang ini.

    Variabel yang diharapkan dari Controller (BarangController@show):
    - $barang: Model \App\Models\Barang (dengan relasi 'kategori' dan 'active_qr_codes_count' sudah di-load).
    - $qrCodes: Paginator of \App\Models\BarangQrCode (unit-unit terkait, dengan relasi 'ruangan', 'pemegangPersonal', 'arsip' sudah di-load).
    - $kategoriList: Collection of \App\Models\KategoriBarang (untuk dropdown di modal edit jenis barang).
--}}

@extends('layouts.app')

@section('title', 'Detail Jenis Barang - ' . $barang->nama_barang)

@section('content')
    <div class="container-fluid">
        {{-- Judul Halaman dan Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Detail Jenis Barang</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('barang.index') }}">Daftar Jenis Barang</a></li>
                            <li class="breadcrumb-item active">Detail: {{ $barang->nama_barang }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Informasi Jenis Barang (Induk) --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="card-title mb-0">Jenis Barang: {{ $barang->nama_barang }}
                    ({{ $barang->kode_barang ?? 'Belum Ada Kode' }})</h5>
                <div class="d-flex gap-2 mt-2 mt-sm-0">
                    @can('update', $barang)
                        <button type="button" class="btn btn-warning btn-sm btn-edit-jenis-barang" data-bs-toggle="modal"
                            data-bs-target="#modalEditJenisBarang" data-barang='@json($barang)'
                            data-url="{{ route('barang.update', $barang->id) }}">
                            <i class="fas fa-edit me-1"></i> Edit Info Jenis
                        </button>
                    @endcan
                    @can('delete', $barang)
                        <button type="button" class="btn btn-danger btn-sm btn-hapus-jenis-barang" data-bs-toggle="modal"
                            data-bs-target="#modalHapusJenisBarang" data-url="{{ route('barang.destroy', $barang->id) }}"
                            data-nama="{{ $barang->nama_barang }}" data-jumlah-unit="{{ $barang->active_qr_codes_count }}">
                            <i class="fas fa-trash-alt me-1"></i> Hapus Jenis & Semua Unit
                        </button>
                    @endcan
                    <a href="{{ route('barang.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nama Barang:</strong> {{ $barang->nama_barang }}</p>
                        <p><strong>Kode Barang:</strong> {{ $barang->kode_barang ?? '-' }}</p>
                        <p><strong>Kategori:</strong> {{ $barang->kategori->nama_kategori ?? '-' }}</p>
                        <p><strong>Merk / Model:</strong> {{ $barang->merk_model ?? '-' }}</p>
                        <p><strong>Ukuran:</strong> {{ $barang->ukuran ?? '-' }}</p>
                        <p><strong>Bahan:</strong> {{ $barang->bahan ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Tahun Pembuatan:</strong> {{ $barang->tahun_pembuatan ?? '-' }}</p>
                        <p><strong>Harga Perolehan Induk:</strong> Rp
                            {{ number_format($barang->harga_perolehan_induk ?? 0, 0, ',', '.') }}</p>
                        <p><strong>Sumber Perolehan Induk:</strong> {{ $barang->sumber_perolehan_induk ?? '-' }}</p>
                        <p><strong>Menggunakan Nomor Seri:</strong>
                            <span
                                @class([
                                    'badge',
                                    'bg-success' => $barang->menggunakan_nomor_seri,
                                    'bg-info' => !$barang->menggunakan_nomor_seri,
                                ])>{{ $barang->menggunakan_nomor_seri ? 'Ya' : 'Tidak' }}</span>
                        </p>
                        <p><strong>Jumlah Unit Aktif Saat Ini:</strong> <span
                                class="badge bg-primary fs-6">{{ $barang->active_qr_codes_count }}</span> unit</p>
                        <p><small class="text-muted">* Jumlah unit aktif adalah unit yang belum di-soft-delete/diarsipkan
                                permanen.</small></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Daftar Unit Fisik (Barang QR Code) --}}
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Unit Fisik Barang</h5>
                @if ($barang->menggunakan_nomor_seri)
                    @can('create', [App\Models\BarangQrCode::class, $barang])
                        <div class="d-flex align-items-center gap-2">
                            <form action="{{ route('barang-qr-code.create') }}" method="GET"
                                class="d-inline-flex align-items-center">
                                <input type="hidden" name="barang_id" value="{{ $barang->id }}">
                                <label for="jumlah_unit_to_add" class="form-label me-2 mb-0">Tambah:</label>
                                <input type="number" name="jumlah_unit" id="jumlah_unit_to_add" value="1" min="1"
                                    max="50" class="form-control form-control-sm" style="width: 70px;">
                                <button type="submit" class="btn btn-primary btn-sm ms-2">
                                    <i class="fas fa-plus me-1"></i> Unit Baru
                                </button>
                            </form>
                        </div>
                    @endcan
                @else
                    <span class="badge bg-info">Jenis barang ini tidak dikelola per unit individual.</span>
                @endif
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="unitTable" class="table table-bordered table-hover dt-responsive nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Kode Inventaris</th>
                                <th>No. Seri Pabrik</th>
                                <th>Lokasi/Pemegang</th>
                                <th class="text-end">Harga Perolehan</th>
                                <th>Tgl. Perolehan</th>
                                <th>Sumber</th>
                                <th>Kondisi</th>
                                <th>Status</th>
                                <th class="text-center">QR</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($qrCodes as $unit)
                                <tr>
                                    <td>{{ $loop->iteration + $qrCodes->firstItem() - 1 }}</td>
                                    <td><a
                                            href="{{ route('barang-qr-code.show', $unit->id) }}">{{ $unit->kode_inventaris_sekolah }}</a>
                                    </td>
                                    <td>{{ $unit->no_seri_pabrik ?? '-' }}</td>
                                    <td>
                                        @if ($unit->id_pemegang_personal && $unit->pemegangPersonal)
                                            <i class="fas fa-user text-primary me-1"
                                                title="Pemegang Personal"></i>{{ $unit->pemegangPersonal->username }}
                                        @elseif ($unit->id_ruangan && $unit->ruangan)
                                            <i class="fas fa-map-marker-alt text-info me-1"
                                                title="Lokasi Ruangan"></i>{{ $unit->ruangan->nama_ruangan }}
                                        @else
                                            <span class="text-muted">Belum Ditempatkan</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ $unit->harga_perolehan_unit ? number_format($unit->harga_perolehan_unit, 0, ',', '.') : '-' }}
                                    </td>
                                    <td>{{ $unit->tanggal_perolehan_unit ? \Carbon\Carbon::parse($unit->tanggal_perolehan_unit)->isoFormat('DD MMM YY') : '-' }}
                                    </td>
                                    <td>{{ $unit->sumber_dana_unit ?? '-' }}</td>
                                    <td>
                                        <span @class([
                                            'badge',
                                            'bg-success' => $unit->kondisi == \App\Models\BarangQrCode::KONDISI_BAIK,
                                            'bg-warning text-dark' =>
                                                $unit->kondisi == \App\Models\BarangQrCode::KONDISI_KURANG_BAIK,
                                            'bg-danger' =>
                                                $unit->kondisi == \App\Models\BarangQrCode::KONDISI_RUSAK_BERAT,
                                            'bg-dark' => $unit->kondisi == \App\Models\BarangQrCode::KONDISI_HILANG,
                                            'bg-secondary' => !in_array(
                                                $unit->kondisi,
                                                \App\Models\BarangQrCode::getValidKondisi()),
                                        ])>{{ $unit->kondisi }}</span>
                                    </td>
                                    <td>
                                        <span @class([
                                            'badge',
                                            'bg-success' => $unit->status == \App\Models\BarangQrCode::STATUS_TERSEDIA,
                                            'bg-info text-dark' =>
                                                $unit->status == \App\Models\BarangQrCode::STATUS_DIPINJAM,
                                            'bg-warning text-dark' =>
                                                $unit->status == \App\Models\BarangQrCode::STATUS_DALAM_PEMELIHARAAN,
                                            'bg-secondary' => !in_array(
                                                $unit->status,
                                                \App\Models\BarangQrCode::getValidStatus()),
                                        ])>{{ $unit->status }}</span>
                                        @if (
                                            $unit->arsip &&
                                                in_array($unit->arsip->status_arsip, [
                                                    \App\Models\ArsipBarang::STATUS_ARSIP_DIAJUKAN,
                                                    \App\Models\ArsipBarang::STATUS_ARSIP_DISETUJUI,
                                                ]))
                                            <small class="d-block text-danger fst-italic">(Proses Arsip)</small>
                                        @elseif($unit->arsip && $unit->arsip->status_arsip == \App\Models\ArsipBarang::STATUS_ARSIP_DISETUJUI_PERMANEN)
                                            <small class="d-block text-dark fst-italic">(Diarsipkan)</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($unit->qr_path && Storage::disk('public')->exists($unit->qr_path))
                                            <a href="{{ route('barang-qr-code.download', $unit->id) }}"
                                                title="Download QR Code {{ $unit->kode_inventaris_sekolah }}">
                                                <img src="{{ asset('storage/' . $unit->qr_path) }}"
                                                    alt="QR Code {{ $unit->kode_inventaris_sekolah }}"
                                                    style="width: 40px; height: 40px; cursor:pointer;">
                                            </a>
                                        @else
                                            <a href="{{ route('barang-qr-code.download', $unit->id) }}"
                                                class="btn btn-sm btn-outline-secondary"
                                                title="Generate & Download QR {{ $unit->kode_inventaris_sekolah }}"><i
                                                    class="fas fa-qrcode"></i></a>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            @can('view', $unit)
                                                <a href="{{ route('barang-qr-code.show', $unit->id) }}"
                                                    class="btn btn-info btn-sm" title="Lihat Detail Unit"><i
                                                        class="fas fa-eye"></i></a>
                                            @endcan
                                            {{-- Tombol aksi transisi unit DIHILANGKAN dari daftar ini --}}
                                            {{-- Tombol arsip juga DIHILANGKAN dari daftar ini --}}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center">Belum ada unit fisik yang terdaftar untuk jenis
                                        barang ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($qrCodes instanceof \Illuminate\Pagination\AbstractPaginator && $qrCodes->hasPages())
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $qrCodes->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Include Modal-modal --}}
    @if (isset($kategoriList))
        @include('admin.barang.partials.modal_edit_jenis', [
            'kategoriList' => $kategoriList,
            'barang' => $barang,
        ])
    @endif
    @include('admin.barang.partials.modal_hapus_jenis')
    {{-- Modal arsip unit tetap di-include jika ada tombol lain yang mungkin memicunya, atau jika JS-nya digunakan bersama --}}
    @include('admin.barang_qr_code.partials.modal_arsip_unit')

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const unitTableEl = document.getElementById('unitTable');
            if (unitTableEl && typeof DataTable !== 'undefined' && unitTableEl.tBodies[0] && unitTableEl.tBodies[0]
                .rows.length > 0 && unitTableEl.tBodies[0].rows[0].cells.length > 1) {
                new DataTable(unitTableEl, {
                    responsive: true,
                    paging: false,
                    info: false,
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
                    order: [
                        [0, 'asc']
                    ],
                    columnDefs: [{
                        orderable: false,
                        targets: [3, 9, 10]
                    }]
                });
            }

            document.addEventListener('click', function(e) {
                const targetElement = e.target.closest('button') || e.target.closest('a');
                if (!targetElement) return;

                if (targetElement.matches('.btn-edit-jenis-barang')) {
                    e.preventDefault();
                    const modalEl = document.getElementById('modalEditJenisBarang');
                    if (!modalEl) return;
                    const form = modalEl.querySelector('form');
                    const data = JSON.parse(targetElement.dataset.barang);
                    form.action = targetElement.dataset.url;
                    if (form.querySelector('#editNamaBarang')) form.querySelector('#editNamaBarang').value =
                        data.nama_barang ?? '';
                    if (form.querySelector('#editIdKategori')) form.querySelector('#editIdKategori').value =
                        data.id_kategori ?? '';
                    if (form.querySelector('#editMerkModel')) form.querySelector('#editMerkModel').value =
                        data.merk_model ?? '';
                    if (form.querySelector('#editUkuran')) form.querySelector('#editUkuran').value = data
                        .ukuran ?? '';
                    if (form.querySelector('#editBahan')) form.querySelector('#editBahan').value = data
                        .bahan ?? '';
                    if (form.querySelector('#editTahunPembuatan')) form.querySelector('#editTahunPembuatan')
                        .value = data.tahun_pembuatan ?? '';
                    if (form.querySelector('#editHargaPerolehanInduk')) form.querySelector(
                        '#editHargaPerolehanInduk').value = data.harga_perolehan_induk ?? '';
                    if (form.querySelector('#editSumberPerolehanInduk')) form.querySelector(
                        '#editSumberPerolehanInduk').value = data.sumber_perolehan_induk ?? '';
                    const inputKodeBarang = form.querySelector('#editKodeBarang');
                    const infoKodeBarang = form.querySelector('#infoKodeBarangEdit');
                    const radioYa = form.querySelector('#editMenggunakanNomorSeriYa');
                    const radioTidak = form.querySelector('#editMenggunakanNomorSeriTidak');
                    const infoNomorSeri = form.querySelector('#infoNomorSeriEdit');
                    // Menggunakan active_qr_codes_count yang di-load di controller
                    const hasUnits = (typeof data.active_qr_codes_count !== 'undefined' && parseInt(data
                        .active_qr_codes_count) > 0);

                    if (inputKodeBarang) {
                        inputKodeBarang.value = data.kode_barang ?? '';
                        inputKodeBarang.disabled = hasUnits;
                        if (infoKodeBarang) {
                            infoKodeBarang.textContent = hasUnits ?
                                'Kode Barang tidak dapat diubah jika sudah ada unit.' :
                                'Biarkan kosong jika ingin digenerate otomatis.';
                            infoKodeBarang.className = hasUnits ? 'form-text text-danger' :
                                'form-text text-muted';
                        }
                    }
                    if (radioYa && radioTidak && infoNomorSeri) {
                        radioYa.checked = data.menggunakan_nomor_seri == 1 || data
                            .menggunakan_nomor_seri === true;
                        radioTidak.checked = !(data.menggunakan_nomor_seri == 1 || data
                            .menggunakan_nomor_seri === true);
                        radioYa.disabled = hasUnits;
                        radioTidak.disabled = hasUnits;
                        infoNomorSeri.textContent = hasUnits ?
                            'Opsi ini tidak dapat diubah karena sudah ada unit.' : '';
                        infoNomorSeri.className = hasUnits ? 'form-text text-danger' :
                            'form-text text-muted';
                    }
                }

                if (targetElement.matches('.btn-hapus-jenis-barang')) {
                    e.preventDefault();
                    const formHapus = document.getElementById('formDeleteJenisBarang');
                    if (!formHapus) return;
                    formHapus.action = targetElement.dataset.url;
                    const namaBarang = targetElement.dataset.nama;
                    const jumlahUnit = targetElement.dataset
                        .jumlahUnit; // Ini akan menggunakan active_qr_codes_count
                    Swal.fire({
                        title: `<span class="text-danger fw-bold">PERHATIAN!</span>`,
                        html: `Anda akan menghapus jenis barang <strong>"${namaBarang}"</strong> beserta perkiraan <strong>${jumlahUnit} unit fisiknya</strong>. <br><strong class='text-danger fs-6'>Semua unit aktif akan DIARSIPKAN (jika ada), dan jenis barang ini akan di-soft-delete. Aksi ini berdampak besar.</strong><br><br>Apakah Anda yakin ingin melanjutkan?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Ya, Hapus Jenis Barang Ini!',
                        cancelButtonText: 'Batal',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            formHapus.submit();
                        }
                    });
                }

                // Event listener untuk tombol arsip unit (jika masih ada di tempat lain atau untuk modal yang di-include)
                if (targetElement.matches('.btn-arsip-unit')) {
                    const modalEl = document.getElementById('modalArsipUnit');
                    if (!modalEl) return;
                    const form = modalEl.querySelector('form');
                    form.action = targetElement.dataset.url;
                    modalEl.querySelector('#arsipUnitKodeDisplay').textContent = targetElement.dataset.kode;
                    const konfirmasiLabel = modalEl.querySelector('label[for="inputKonfirmasiArsipUnit"]');
                    if (konfirmasiLabel) {
                        konfirmasiLabel.innerHTML =
                            `Ketik "<strong class="text-danger">${targetElement.dataset.kode}</strong>" untuk konfirmasi:`;
                    }
                    form.reset();
                }
            });
        });
    </script>
@endpush
