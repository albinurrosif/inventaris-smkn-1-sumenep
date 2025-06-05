@extends('layouts.app')

@section('title', 'Detail Jenis Barang - ' . $barang->nama_barang)

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Detail Jenis Barang (Ruangan Anda)</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('operator.dashboard') }}">Dashboard</a></li>
                            {{-- Jika Operator menggunakan route barang.index yang sama dengan Admin --}}
                            <li class="breadcrumb-item"><a href="{{ route('barang.index') }}">Daftar Jenis Barang</a></li>
                            {{-- Atau jika Operator punya route index sendiri: --}}
                            {{-- <li class="breadcrumb-item"><a href="{{ route('operator.barang.index') }}">Daftar Jenis Barang</a></li> --}}
                            <li class="breadcrumb-item active">Detail: {{ $barang->nama_barang }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Jenis Barang: {{ $barang->nama_barang }} ({{ $barang->kode_barang }})</h5>
                <div class="d-flex gap-2">
                    {{-- Operator mungkin tidak bisa mengedit info jenis barang induk --}}
                    {{-- Jika boleh, pastikan BarangPolicy@update mengizinkannya --}}
                    @can('update', $barang)
                        <button type="button" class="btn btn-warning btn-sm btn-edit-barang-trigger" data-bs-toggle="modal"
                            data-bs-target="#modalEditBarang" data-barang='@json($barang->loadCount('qrCodes'))'>
                            <i class="fas fa-edit me-1"></i> Edit Info Jenis Barang
                        </button>
                    @endcan

                    {{-- Operator TIDAK BOLEH menghapus jenis barang --}}
                    {{-- @can('delete', $barang) ... @endcan --}}

                    <a href="{{ route('barang.index') }}" class="btn btn-secondary btn-sm">
                        {{-- Jika Operator menggunakan route barang.index yang sama: <a href="{{ route('barang.index') }}" ...> --}}
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
            <div class="card-body">
                {{-- Detail Informasi Jenis Barang --}}
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Nama Barang:</strong> {{ $barang->nama_barang }}</p>
                        <p><strong>Kode Barang:</strong> {{ $barang->kode_barang }}</p>
                        <p><strong>Kategori:</strong> {{ $barang->kategori->nama_kategori ?? '-' }}</p>
                        <p><strong>Merk / Model:</strong> {{ $barang->merk_model ?? '-' }}</p>
                        <p><strong>Ukuran:</strong> {{ $barang->ukuran ?? '-' }}</p>
                        <p><strong>Bahan:</strong> {{ $barang->bahan ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Tahun Pembuatan:</strong> {{ $barang->tahun_pembuatan ?? '-' }}</p>
                        <p><strong>Harga Perolehan Induk (Ref):</strong> Rp
                            {{ $barang->harga_perolehan_induk ? number_format($barang->harga_perolehan_induk, 0, ',', '.') : '0' }}
                        </p>
                        <p><strong>Sumber Perolehan Induk (Ref):</strong> {{ $barang->sumber_perolehan_induk ?? '-' }}</p>
                        <p><strong>Menggunakan Nomor Seri:</strong> <span
                                class="badge bg-{{ $barang->menggunakan_nomor_seri ? 'success' : 'info' }}">{{ $barang->menggunakan_nomor_seri ? 'Ya' : 'Tidak' }}</span>
                        </p>
                        <p><strong>Jumlah Unit Aktif (di Ruangan Anda):</strong> <span
                                class="badge bg-primary fs-6">{{ $qrCodes->count() }}</span> unit</p>
                        <p>
                            <small class="text-muted">* Jumlah unit yang ditampilkan adalah unit aktif di ruangan yang Anda
                                kelola.</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Unit Fisik Barang (di Ruangan Anda)</h5>
                {{-- Operator mungkin tidak boleh menambah unit baru dari halaman ini, --}}
                {{-- penambahan unit mungkin melalui wizard pembuatan jenis barang baru --}}
                {{-- atau dari halaman khusus penambahan unit ke jenis barang yang sudah ada. --}}
                {{-- Jika boleh: --}}
                {{-- @can('createUnitFor', $barang) --}} {{-- Ability kustom jika perlu --}}
                {{-- <a href="{{ route('NAMAROUTE_TAMBAH_UNIT', ['id_barang' => $barang->id]) }}" class="btn btn-primary btn-sm"> --}}
                {{-- <i class="mdi mdi-plus"></i> Tambah Unit ke Jenis Ini --}}
                {{-- </a> --}}
                {{-- @endcan --}}
            </div>
            <div class="card-body">
                {{-- Variabel $qrCodes sudah difilter di controller untuk Operator --}}
                @if (isset($qrCodes) && $qrCodes->count() > 0)
                    <div class="table-responsive">
                        <table id="unitTableOperator" class="table table-bordered table-hover dt-responsive nowrap w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Kode Inventaris Sekolah</th>
                                    <th>No. Seri Pabrik</th>
                                    <th>Ruangan</th> {{-- Ruangan akan selalu sama dengan yang dikelola operator --}}
                                    <th>Kondisi</th>
                                    <th>Status Ketersediaan</th>
                                    <th>Harga Unit (Rp)</th>
                                    <th>Tgl. Perolehan</th>
                                    <th>Pemegang</th>
                                    <th>QR</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($qrCodes as $index => $unit)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            {{-- Operator seharusnya selalu bisa melihat detail unit di ruangannya --}}
                                            @can('view', $unit)
                                                <a
                                                    href="{{ route('barang-qr-code.show', $unit->id) }}">{{ $unit->kode_inventaris_sekolah }}</a>
                                            @else
                                                {{ $unit->kode_inventaris_sekolah }}
                                            @endcan
                                        </td>
                                        <td>{{ $unit->no_seri_pabrik ?? '-' }}</td>
                                        <td>{{ $unit->ruangan->nama_ruangan ?? ($unit->id_pemegang_personal ? 'Dipegang Personal' : 'Belum Ditempatkan') }}
                                        </td>
                                        <td>
                                            <span
                                                class="badge @if ($unit->kondisi == 'Baik') bg-success @elseif($unit->kondisi == 'Kurang Baik') bg-warning @elseif($unit->kondisi == 'Rusak Berat') bg-danger @else bg-secondary @endif">
                                                {{ $unit->kondisi }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge @if ($unit->status == 'Tersedia') bg-success @elseif($unit->status == 'Dipinjam') bg-info @elseif($unit->status == 'Dalam Pemeliharaan') bg-warning @elseif($unit->status == 'Diarsipkan/Dihapus') bg-danger @else bg-secondary @endif">
                                                {{ $unit->status }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            {{ $unit->harga_perolehan_unit ? number_format($unit->harga_perolehan_unit, 0, ',', '.') : '-' }}
                                        </td>
                                        <td>{{ $unit->tanggal_perolehan_unit ? \Carbon\Carbon::parse($unit->tanggal_perolehan_unit)->isoFormat('DD MMM YY') : '-' }}
                                        </td>
                                        <td>{{ $unit->pemegangPersonal->username ?? '-' }}</td>
                                        <td>
                                            @can('downloadQr', $unit)
                                                @if ($unit->qr_path && Storage::disk('public')->exists($unit->qr_path))
                                                    <a href="{{ route('barang-qr-code.download', $unit->id) }}"
                                                        title="Download QR Code">
                                                        <img src="{{ asset('storage/' . $unit->qr_path) }}"
                                                            alt="QR Code {{ $unit->kode_inventaris_sekolah }}"
                                                            style="width: 40px; height: 40px; cursor:pointer;">
                                                    </a>
                                                @else
                                                    <a href="{{ route('barang-qr-code.download', $unit->id) }}"
                                                        class="btn btn-sm btn-outline-secondary" title="Generate & Download QR">
                                                        <i class="fas fa-qrcode"></i>
                                                    </a>
                                                @endif
                                            @else
                                                <i class="fas fa-qrcode text-muted" title="QR Code"></i>
                                            @endcan
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                @can('view', $unit)
                                                    <a href="{{ route('barang-qr-code.show', $unit->id) }}"
                                                        class="btn btn-info btn-sm" title="Lihat Detail Unit">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endcan
                                                @can('update', $unit)
                                                    <a href="{{ route('barang-qr-code.edit', $unit->id) }}"
                                                        class="btn btn-warning btn-sm" title="Edit Unit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                                @can('delete', $unit)
                                                    <button type="button" class="btn btn-danger btn-sm btn-arsip-unit"
                                                        data-unit-id="{{ $unit->id }}"
                                                        data-unit-kode="{{ $unit->kode_inventaris_sekolah }}"
                                                        title="Arsipkan/Hapus Unit">
                                                        <i class="fas fa-archive"></i>
                                                    </button>
                                                @endcan
                                                @can('mutasi', $unit)
                                                    {{-- Tombol Mutasi Unit --}}
                                                    <button type="button" class="btn btn-primary btn-sm btn-mutasi-unit"
                                                        data-unit-id="{{ $unit->id }}"
                                                        data-unit-kode="{{ $unit->kode_inventaris_sekolah }}"
                                                        data-ruangan-asal-id="{{ $unit->id_ruangan }}"
                                                        data-ruangan-asal-nama="{{ $unit->ruangan->nama_ruangan ?? '' }}"
                                                        title="Mutasi/Pindahkan Unit">
                                                        <i class="fas fa-truck"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info text-center">
                        Tidak ada unit fisik barang dari jenis ini yang berada di ruangan yang Anda kelola.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Edit Jenis Barang (jika Operator diizinkan dan tombolnya diaktifkan) --}}
    @can('update', $barang)
        @include('admin.barang.partials.modal_edit', ['kategoriList' => $kategoriList])
    @endcan

    {{-- Modal untuk Arsip/Hapus Unit Individual --}}
    @if (isset($qrCodes) && $qrCodes->contains(fn($unit) => Gate::allows('delete', $unit)))
        @include('admin.barang_qr_code.partials.modal_arsip_unit')
    @endif

    {{-- Modal untuk Mutasi Unit Individual (Anda perlu membuat partial ini) --}}
    @if (isset($qrCodes) && $qrCodes->contains(fn($unit) => Gate::allows('mutasi', $unit)))
        @include('admin.barang_qr_code.partials.modal_mutasi_unit', [
            'ruanganListAll' => $ruanganListAll ?? App\Models\Ruangan::orderBy('nama_ruangan')->get(),
        ])
    @endif

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Inisialisasi DataTable untuk tabel unit jika ada data
            if ($('#unitTableOperator tbody tr').length > 0 && !$('#unitTableOperator tbody tr td').hasClass(
                    'text-center')) {
                $('#unitTableOperator').DataTable({
                    responsive: true,
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                    },
                    order: [
                        [0, 'asc']
                    ]
                });
            }
        });

        // JavaScript untuk tombol Edit Info Jenis Barang (jika diaktifkan untuk Operator)
        @can('update', $barang)
            document.addEventListener('click', function(e) {
                const editBtn = e.target.closest('.btn-edit-barang-trigger');
                if (editBtn) {
                    e.preventDefault();
                    const dataString = editBtn.getAttribute('data-barang');
                    if (!dataString) {
                        console.error('Data barang tidak ditemukan');
                        return;
                    }
                    let data;
                    try {
                        data = JSON.parse(dataString);
                    } catch (error) {
                        console.error('Gagal parse JSON:', error);
                        return;
                    }

                    const modalElement = document.getElementById('modalEditBarang');
                    if (!modalElement) {
                        console.error('Modal #modalEditBarang tidak ditemukan');
                        return;
                    }
                    const modal = new bootstrap.Modal(modalElement);
                    const form = document.getElementById('formEditBarangAction');
                    if (!form) {
                        console.error('Form #formEditBarangAction tidak ditemukan');
                        return;
                    }

                    form.action =
                        `{{ url('admin/barang') }}/${data.id}`; // Sesuaikan dengan route update barang Anda

                    modalElement.querySelector('#editNamaBarang').value = data.nama_barang ?? '';
                    modalElement.querySelector('#editIdKategori').value = data.id_kategori ?? '';
                    modalElement.querySelector('#editMerkModel').value = data.merk_model ?? '';
                    modalElement.querySelector('#editUkuran').value = data.ukuran ?? '';
                    modalElement.querySelector('#editBahan').value = data.bahan ?? '';
                    modalElement.querySelector('#editTahunPembuatan').value = data.tahun_pembuatan ?? '';
                    modalElement.querySelector('#editHargaPerolehanInduk').value = data.harga_perolehan_induk ?? '';
                    modalElement.querySelector('#editSumberPerolehanInduk').value = data.sumber_perolehan_induk ??
                        '';

                    const radioYa = modalElement.querySelector('#editMenggunakanNomorSeriYa');
                    const radioTidak = modalElement.querySelector('#editMenggunakanNomorSeriTidak');
                    const infoNomorSeri = modalElement.querySelector('#infoNomorSeriEdit');

                    const hasUnits = (typeof data.qr_codes_count !== 'undefined' && data.qr_codes_count > 0);

                    if (data.menggunakan_nomor_seri == 1 || data.menggunakan_nomor_seri === true) {
                        radioYa.checked = true;
                    } else {
                        radioTidak.checked = true;
                    }

                    // Untuk Operator, field ini selalu disabled saat edit karena sudah ada unit (sesuai pendekatan terintegrasi)
                    radioYa.disabled = true;
                    radioTidak.disabled = true;
                    infoNomorSeri.textContent = 'Properti "Menggunakan Nomor Seri" tidak dapat diubah.';
                    infoNomorSeri.className = 'form-text text-muted';

                    modal.show();
                }
            });
        @endcan

        // JavaScript untuk tombol Arsip/Hapus Unit Individual
        document.addEventListener('click', function(e) {
            const arsipBtn = e.target.closest('.btn-arsip-unit');
            if (arsipBtn) {
                e.preventDefault();
                const unitId = arsipBtn.getAttribute('data-unit-id');
                const unitKode = arsipBtn.getAttribute('data-unit-kode');

                const modalElement = document.getElementById('modalArsipUnit');
                if (!modalElement) {
                    console.error('Modal #modalArsipUnit tidak ditemukan');
                    return;
                }
                const modalArsip = new bootstrap.Modal(modalElement);
                const form = document.getElementById('formArsipUnitAction');
                if (!form) {
                    console.error('Form #formArsipUnitAction tidak ditemukan');
                    return;
                }

                modalElement.querySelector('#arsipUnitId').value = unitId;
                modalElement.querySelector('#arsipUnitKodeDisplay').textContent = unitKode;
                modalElement.querySelector('#konfirmasiKodeUnit').textContent = unitKode;

                form.action = `{{ url('admin/barang-qr-code') }}/${unitId}`; // Sesuaikan route
                form.reset();
                modalArsip.show();
            }
        });

        // JavaScript untuk tombol Mutasi Unit
        document.addEventListener('click', function(e) {
            const mutasiBtn = e.target.closest('.btn-mutasi-unit');
            if (mutasiBtn) {
                e.preventDefault();
                const unitId = mutasiBtn.getAttribute('data-unit-id');
                const unitKode = mutasiBtn.getAttribute('data-unit-kode');
                const ruanganAsalId = mutasiBtn.getAttribute('data-ruangan-asal-id');
                const ruanganAsalNama = mutasiBtn.getAttribute('data-ruangan-asal-nama');

                const modalElement = document.getElementById('modalMutasiUnit'); // Pastikan modal ini ada
                if (!modalElement) {
                    console.error('Modal #modalMutasiUnit tidak ditemukan');
                    return;
                }
                const modalMutasi = new bootstrap.Modal(modalElement);
                const form = document.getElementById(
                    'formMutasiUnitAction'); // Pastikan form ini ada di modal mutasi
                if (!form) {
                    console.error('Form #formMutasiUnitAction tidak ditemukan');
                    return;
                }

                modalElement.querySelector('#mutasiUnitId').value = unitId; // Input hidden di modal mutasi
                modalElement.querySelector('#mutasiUnitKodeDisplay').textContent = unitKode;
                modalElement.querySelector('#mutasiRuanganAsalDisplay').textContent = ruanganAsalNama ||
                    'Tidak di ruangan';
                modalElement.querySelector('#mutasiIdRuanganAsalHidden').value = ruanganAsalId ||
                    ''; // Input hidden

                form.action = `{{ url('admin/barang-qr-code/${unitId}/mutasi') }}`; // Sesuaikan route
                form.reset();

                // Hapus opsi ruangan asal dari dropdown ruangan tujuan
                const ruanganTujuanSelect = modalElement.querySelector('#mutasiIdRuanganTujuan');
                if (ruanganTujuanSelect && ruanganAsalId) {
                    for (let i = 0; i < ruanganTujuanSelect.options.length; i++) {
                        if (ruanganTujuanSelect.options[i].value == ruanganAsalId) {
                            ruanganTujuanSelect.options[i].style.display = 'none';
                        } else {
                            ruanganTujuanSelect.options[i].style.display = '';
                        }
                    }
                }
                modalMutasi.show();
            }
        });
    </script>
@endpush
