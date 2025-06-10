@php
    use App\Models\StokOpname;
    use App\Models\DetailStokOpname;
    use App\Models\BarangQrCode;
@endphp

@extends('layouts.app')

@section('title', 'Proses Stok Opname - Ruangan ' . $stokOpname->ruangan->nama_ruangan)

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        .form-control-sm-custom {
            height: calc(1.5em + .5rem + 2px);
            padding: .25rem .5rem;
            font-size: .8rem;
        }

        .table-opname td,
        .table-opname th {
            vertical-align: middle;
            font-size: 0.8rem;
        }

        .sticky-header {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 10;
        }

        .status-badge {
            font-size: 0.9em;
        }

        .select2-container--bootstrap-5 .select2-selection--single {
            height: calc(1.5em + .5rem + 2px) !important;
            padding: .25rem .5rem !important;
            font-size: .8rem !important;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            line-height: 1.5 !important;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + .5rem) !important;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Proses Stok Opname</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route($rolePrefix . 'stok-opname.index') }}">Stok
                                    Opname</a></li>

                            <li class="breadcrumb-item active">Proses SO</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notifikasi akan ditangani oleh JavaScript SweetAlert dari AJAX --}}

        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        Sesi Stok Opname: Ruangan <strong>{{ $stokOpname->ruangan->nama_ruangan }}</strong>
                        (Tgl: {{ \Carbon\Carbon::parse($stokOpname->tanggal_opname)->isoFormat('DD MMM YYYY') }})
                    </h5>
                    <div>
                        @php
                            $statusClassShow = match (strtolower($stokOpname->status ?? '')) {
                                strtolower(StokOpname::STATUS_DRAFT) => 'secondary',
                                strtolower(StokOpname::STATUS_SELESAI) => 'success',
                                strtolower(StokOpname::STATUS_DIBATALKAN) => 'danger',
                                default => 'light text-dark',
                            };
                        @endphp
                        Status: <span class="badge bg-{{ $statusClassShow }} status-badge">{{ $stokOpname->status }}</span>
                        @if ($stokOpname->trashed())
                            <span class="badge bg-dark ms-1 status-badge">Diarsipkan</span>
                        @endif
                    </div>
                </div>
                <p class="text-muted mb-0">Operator: {{ $stokOpname->operator->username }}</p>
                @if ($stokOpname->catatan)
                    <p class="text-muted mb-0 mt-1">Catatan Sesi: {{ $stokOpname->catatan }}</p>
                @endif
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-1"></i>
                    Periksa setiap unit barang di bawah ini. Update <strong>Kondisi Fisik</strong> dan tambahkan
                    <strong>Catatan Fisik</strong> jika perlu.
                    Perubahan disimpan otomatis per baris jika menggunakan tombol <i class="fas fa-save"></i> Simpan.
                    Setelah semua item diperiksa, Admin dapat memfinalisasi sesi stok opname ini.
                </div>

                @if ($stokOpname->status === StokOpname::STATUS_DRAFT && !$stokOpname->trashed())
                    @can('processDetails', $stokOpname)
                        <div class="mb-3 text-end">
                            <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal"
                                data-bs-target="#modalTambahBarangTemuan">
                                <i class="fas fa-plus-circle me-1"></i> Tambah Barang Temuan
                            </button>
                        </div>
                    @endcan
                @endif

                <div class="table-responsive mt-3">
                    <table class="table table-sm table-bordered table-opname">
                        <thead class="sticky-header">
                            <tr>
                                <th class="text-center" style="width: 5%;">No</th>
                                <th>Kode Unit</th>
                                <th>Nama Barang</th>
                                <th>No. Seri</th>
                                <th class="text-center">Kondisi Tercatat</th>
                                <th style="width: 18%;">Kondisi Fisik <span class="text-danger">*</span></th>
                                <th style="width: 25%;">Catatan Fisik</th>
                                <th class="text-center" style="width: 8%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="detail-stok-opname-body">
                            @forelse ($stokOpname->detailStokOpname as $index => $detail)
                                <tr id="row-detail-{{ $detail->id }}" data-detail-id="{{ $detail->id }}">
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td>
                                        @if ($detail->barangQrCode)
                                            <a href="{{ route($rolePrefix . 'barang-qr-code.show', $detail->barangQrCode->id) }}"
                                                target="_blank" data-bs-toggle="tooltip" title="Lihat detail unit barang">
                                                <code>{{ $detail->barangQrCode->kode_inventaris_sekolah }}</code>
                                            </a>
                                        @else
                                            <span class="text-danger">Unit tidak ditemukan!</span>
                                        @endif
                                    </td>
                                    <td>{{ optional(optional($detail->barangQrCode)->barang)->nama_barang ?? 'N/A' }}</td>
                                    <td>{{ optional($detail->barangQrCode)->no_seri_pabrik ?: '-' }}
                                    </td>
                                    <td class="text-center">

                                        <span
                                            class="badge {{ \App\Models\BarangQrCode::getKondisiColor($detail->kondisi_tercatat) }}">{{ $detail->kondisi_tercatat }}</span>
                                    </td>
                                    <td>
                                        @if ($stokOpname->status === StokOpname::STATUS_DRAFT && !$stokOpname->trashed())
                                            <select name="kondisi_fisik"
                                                class="form-select form-control-sm-custom kondisi-fisik-input select2-kondisi">
                                                <option value="">-- Pilih --</option>
                                                @foreach ($kondisiFisikList as $key => $value)
                                                    <option value="{{ $key }}"
                                                        {{ $detail->kondisi_fisik == $key ? 'selected' : '' }}>
                                                        {{ $value }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            {{ $kondisiFisikList[$detail->kondisi_fisik] ?? ($detail->kondisi_fisik ?? '-') }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($stokOpname->status === StokOpname::STATUS_DRAFT && !$stokOpname->trashed())
                                            <textarea name="catatan_fisik" class="form-control form-control-sm-custom catatan-fisik-input" rows="1">{{ $detail->catatan_fisik }}</textarea>
                                        @else
                                            {{ $detail->catatan_fisik ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($stokOpname->status === StokOpname::STATUS_DRAFT && !$stokOpname->trashed())
                                            @can('processDetails', $stokOpname)
                                                <button type="button" class="btn btn-success btn-sm btn-save-detail"
                                                    data-detail-id="{{ $detail->id }}" title="Simpan Perubahan Baris Ini">
                                                    <i class="fas fa-save"></i>
                                                </button>
                                            @endcan
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">Belum ada unit barang dalam sesi stok opname ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <a href="{{ route($rolePrefix . 'stok-opname.index') }}" class="btn btn-outline-secondary">Kembali ke
                    Daftar SO</a>

                @if ($stokOpname->status === StokOpname::STATUS_DRAFT && !$stokOpname->trashed())
                    @can('cancel', $stokOpname)
                        <button type="button" class="btn btn-danger btn-cancel-so" data-id="{{ $stokOpname->id }}"
                            data-ruangan="{{ $stokOpname->ruangan->nama_ruangan }}"
                            data-tanggal="{{ \Carbon\Carbon::parse($stokOpname->tanggal_opname)->isoFormat('DD MMM YYYY') }}">
                            <i class="fas fa-times-circle me-1"></i> Batalkan Sesi SO
                        </button>
                    @endcan
                    @can('finalize', $stokOpname)
                        <button type="button" class="btn btn-primary btn-finalize-so" data-id="{{ $stokOpname->id }}"
                            data-ruangan="{{ $stokOpname->ruangan->nama_ruangan }}"
                            data-tanggal="{{ \Carbon\Carbon::parse($stokOpname->tanggal_opname)->isoFormat('DD MMM YYYY') }}">
                            <i class="fas fa-check-circle me-1"></i> Finalisasi & Proses Hasil SO
                        </button>
                    @endcan
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Tambah Barang Temuan --}}
    @if ($stokOpname->status === StokOpname::STATUS_DRAFT && !$stokOpname->trashed())
        @can('processDetails', $stokOpname)
            <div class="modal fade" id="modalTambahBarangTemuan" tabindex="-1" aria-labelledby="modalTambahBarangTemuanLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalTambahBarangTemuanLabel">Tambah Barang Temuan ke Sesi Opname</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="formTambahBarangTemuan">
                            @csrf
                            <input type="hidden" name="id_stok_opname" value="{{ $stokOpname->id }}">
                            <div class="modal-body">
                                <div class="alert alert-warning">Pilih salah satu metode identifikasi barang di bawah ini atau
                                    tambahkan sebagai unit baru.</div>

                                <div class="mb-3">
                                    <label for="search_kode_inventaris_temuan" class="form-label">Cari Berdasarkan Kode
                                        Inventaris / No. Seri</label>
                                    <select class="form-control select2-barang-qr-ajax" id="search_kode_inventaris_temuan"
                                        name="id_barang_qr_code_existing" style="width: 100%;">
                                        <option value="">Ketik untuk mencari...</option>
                                    </select>
                                    <small class="form-text text-muted">Jika barang sudah ada di sistem (mungkin terarsip atau
                                        di lokasi lain).</small>
                                </div>

                                <div class="text-center my-2"><strong>ATAU</strong></div>

                                <div class="mb-3">
                                    <label for="id_barang_induk_temuan" class="form-label">Tambah Unit Baru dari Jenis Barang
                                        Induk</label>
                                    <select class="form-control select2-basic" id="id_barang_induk_temuan"
                                        name="id_barang_induk_new" style="width: 100%;">
                                        <option value="">-- Pilih Jenis Barang Induk --</option>
                                        @foreach (\App\Models\Barang::whereNull('deleted_at')->orderBy('nama_barang')->get() as $barangInduk)
                                            <option value="{{ $barangInduk->id }}"
                                                data-uses-serial="{{ $barangInduk->menggunakan_nomor_seri ? 'true' : 'false' }}">
                                                {{ $barangInduk->nama_barang }} ({{ $barangInduk->kode_barang }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3" id="no_seri_pabrik_temuan_group" style="display: none;">
                                    <label for="no_seri_pabrik_temuan" class="form-label">No. Seri Pabrik (Jika Ada & Jenis
                                        Barang Menggunakan)</label>
                                    <input type="text" class="form-control" id="no_seri_pabrik_temuan"
                                        name="no_seri_pabrik_new">
                                </div>
                                <div class="mb-3">
                                    <label for="harga_perolehan_unit_temuan" class="form-label">Harga Perolehan Unit Temuan
                                        <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="harga_perolehan_unit_temuan"
                                        name="harga_perolehan_unit_new" step="0.01" min="0">
                                </div>
                                <div class="mb-3">
                                    <label for="tanggal_perolehan_unit_temuan" class="form-label">Tanggal Perolehan Unit
                                        Temuan <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tanggal_perolehan_unit_temuan"
                                        name="tanggal_perolehan_unit_new" value="{{ date('Y-m-d') }}">
                                </div>

                                <hr>
                                <h6 class="mt-3">Detail Pemeriksaan Fisik Barang Temuan:</h6>
                                <div class="mb-3">
                                    <label for="kondisi_fisik_temuan" class="form-label">Kondisi Fisik Temuan <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select select2-basic" id="kondisi_fisik_temuan"
                                        name="kondisi_fisik_temuan" required style="width: 100%;">
                                        <option value="">-- Pilih Kondisi --</option>
                                        @foreach ($kondisiFisikList as $key => $value)
                                            @if ($key !== DetailStokOpname::KONDISI_HILANG)
                                                {{-- Barang temuan tidak mungkin hilang --}}
                                                <option value="{{ $key }}">{{ $value }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="catatan_fisik_temuan" class="form-label">Catatan Fisik Temuan</label>
                                    <textarea class="form-control" id="catatan_fisik_temuan" name="catatan_fisik_temuan" rows="2"></textarea>
                                </div>

                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary"
                                    data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary" id="btnSimpanBarangTemuan"><i
                                        class="fas fa-plus"></i> Tambahkan ke Sesi</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    @endif

    {{-- Form tersembunyi untuk cancel dan finalize --}}
    <form id="formFinalizeStokOpname" method="POST" style="display: none;"> @csrf </form>
    <form id="formCancelStokOpname" method="POST" style="display: none;"> @csrf </form>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stokOpnameId = "{{ $stokOpname->id }}";
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            $('.select2-kondisi').select2({
                theme: "bootstrap-5",
                width: '100%',
                placeholder: "-- Pilih --",
                minimumResultsForSearch: Infinity // Sembunyikan search box karena list pendek
            });

            document.querySelectorAll('.btn-save-detail').forEach(button => {
                button.addEventListener('click', function() {
                    const detailId = this.dataset.detailId;
                    const row = document.getElementById(`row-detail-${detailId}`);
                    const kondisiFisikEl = row.querySelector('.kondisi-fisik-input');
                    const catatanFisikEl = row.querySelector('.catatan-fisik-input');

                    const kondisiFisik = kondisiFisikEl ? kondisiFisikEl.value : null;
                    const catatanFisik = catatanFisikEl ? catatanFisikEl.value : null;

                    if (!kondisiFisik) {
                        Swal.fire('Peringatan!', 'Kondisi fisik wajib dipilih untuk disimpan.',
                            'warning');
                        return;
                    }

                    const buttonOriginalHTML = this.innerHTML;
                    this.innerHTML =
                        `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
                    this.disabled = true;

                    fetch(`{{ route($rolePrefix . 'stok-opname.updateDetail', ['stokOpname' => $stokOpname->id, 'detail' => ':detailId']) }}`
                            .replace(':detailId', detailId), {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    kondisi_fisik: kondisiFisik,
                                    catatan_fisik: catatanFisik
                                })
                            })
                        .then(response => {
                            if (!response.ok) {
                                return response.json().then(err => {
                                    throw err;
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil',
                                    text: data.message,
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 2000
                                });
                                row.classList.add('table-success');
                                setTimeout(() => row.classList.remove('table-success'), 2500);
                            } else {
                                Swal.fire('Gagal!', data.message || 'Gagal menyimpan detail.',
                                    'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            let errorMsg = 'Terjadi kesalahan jaringan atau sistem.';
                            if (error && error.message) errorMsg = error.message;
                            if (error && error.errors) { // Handle validation errors
                                errorMsg = Object.values(error.errors).flat().join('<br>');
                            }
                            Swal.fire('Error!', errorMsg, 'error');
                        })
                        .finally(() => {
                            this.innerHTML = buttonOriginalHTML;
                            this.disabled = false;
                        });
                });
            });

            // Finalize SO
            const btnFinalize = document.querySelector('.btn-finalize-so');
            if (btnFinalize) {
                btnFinalize.addEventListener('click', function() {
                    const soId = this.dataset.id;
                    const ruanganNama = this.dataset.ruangan;
                    const tanggal = this.dataset.tanggal;
                    Swal.fire({
                        title: 'Finalisasi Stok Opname?',
                        html: `Anda yakin ingin memfinalisasi sesi Stok Opname untuk ruangan <strong>${ruanganNama}</strong> pada tanggal <strong>${tanggal}</strong>? <br><strong class='text-danger'>Setelah difinalisasi, data pemeriksaan fisik tidak dapat diubah lagi, dan sistem akan memperbarui status serta kondisi barang berdasarkan hasil opname.</strong> Pastikan semua item telah diperiksa dengan benar.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, Finalisasi!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.getElementById('formFinalizeStokOpname');
                            form.action =
                                `{{ route($rolePrefix . 'stok-opname.finalize', ['stokOpname' => ':id']) }}`
                                .replace(':id', soId);
                            form.submit();
                        }
                    });
                });
            }

            // Cancel SO
            const btnCancel = document.querySelector('.btn-cancel-so');
            if (btnCancel) {
                btnCancel.addEventListener('click', function() {
                    const soId = this.dataset.id;
                    const ruanganNama = this.dataset.ruangan;
                    const tanggal = this.dataset.tanggal;
                    Swal.fire({
                        title: 'Batalkan Sesi Stok Opname?',
                        html: `Anda yakin ingin membatalkan sesi Stok Opname untuk ruangan <strong>${ruanganNama}</strong> pada tanggal <strong>${tanggal}</strong>? Tindakan ini tidak dapat diurungkan.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Batalkan Sesi!',
                        cancelButtonText: 'Tidak'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.getElementById('formCancelStokOpname');
                            form.action =
                                `{{ route($rolePrefix . 'stok-opname.cancel', ['stokOpname' => ':id']) }}`
                                .replace(':id', soId);
                            form.submit();
                        }
                    });
                });
            }

            // Modal Tambah Barang Temuan
            $('#modalTambahBarangTemuan .select2-basic').select2({
                theme: "bootstrap-5",
                width: '100%',
                dropdownParent: $("#modalTambahBarangTemuan") // Penting untuk Select2 di dalam modal
            });

            $('#id_barang_induk_temuan').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const usesSerial = selectedOption.data('uses-serial');
                if (usesSerial === true || usesSerial === 'true') {
                    $('#no_seri_pabrik_temuan_group').show();
                } else {
                    $('#no_seri_pabrik_temuan_group').hide();
                    $('#no_seri_pabrik_temuan').val('');
                }
            }).trigger('change');


            $('.select2-barang-qr-ajax').select2({
                theme: "bootstrap-5",
                width: '100%',
                placeholder: "Ketik Kode Inventaris / No. Seri...",
                allowClear: true,
                dropdownParent: $("#modalTambahBarangTemuan"),
                minimumInputLength: 2,
                ajax: {
                    url: "{{ route($rolePrefix . 'stok-opname.search-barang-qr') }}", // Anda perlu membuat route ini
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term, // search term
                            id_ruangan_opname: '{{ $stokOpname->id_ruangan }}', // untuk info tambahan jika perlu
                            id_stok_opname: stokOpnameId
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: $.map(data.items, function(item) {
                                let text =
                                    `${item.nama_barang_induk} (${item.kode_inventaris_sekolah})`;
                                if (item.no_seri_pabrik) text +=
                                    ` - SN: ${item.no_seri_pabrik}`;
                                if (item.ruangan_saat_ini) text +=
                                    ` - Lokasi: ${item.ruangan_saat_ini}`;
                                else if (item.pemegang_saat_ini) text +=
                                    ` - Pemegang: ${item.pemegang_saat_ini}`;
                                else if (item.deleted_at) text += ` - (Diarsipkan)`;
                                else text += ` - (Tanpa Lokasi)`;
                                return {
                                    id: item.id,
                                    text: text,
                                    item_data: item // Simpan semua data item
                                }
                            })
                        };
                    },
                    cache: true
                }
            });

            $('#search_kode_inventaris_temuan').on('select2:select', function(e) {
                var data = e.params.data.item_data;
                if (data) {
                    // Jika barang existing dipilih, nonaktifkan input untuk barang baru
                    $('#id_barang_induk_temuan').val(null).trigger('change').prop('disabled', true);
                    $('#no_seri_pabrik_temuan').val(data.no_seri_pabrik || '').prop('disabled', true);
                    $('#harga_perolehan_unit_temuan').val(data.harga_perolehan_unit || '').prop('disabled',
                        true);
                    $('#tanggal_perolehan_unit_temuan').val(data.tanggal_perolehan_unit_raw || '').prop(
                        'disabled', true);
                }
            });
            $('#search_kode_inventaris_temuan').on('select2:unselect select2:clear', function(e) {
                // Jika pilihan barang existing dibatalkan, aktifkan kembali input untuk barang baru
                $('#id_barang_induk_temuan').prop('disabled', false).trigger('change');
                $('#no_seri_pabrik_temuan').prop('disabled', false);
                $('#harga_perolehan_unit_temuan').prop('disabled', false);
                $('#tanggal_perolehan_unit_temuan').prop('disabled', false);
            });


            $('#formTambahBarangTemuan').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const btnSimpan = $('#btnSimpanBarangTemuan');
                const btnOriginalHTML = btnSimpan.html();
                btnSimpan.html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menambahkan...'
                ).prop('disabled', true);

                let kondisiFisikTemuan = $('#kondisi_fisik_temuan').val();
                if (!kondisiFisikTemuan) {
                    Swal.fire('Peringatan!', 'Kondisi Fisik Barang Temuan wajib diisi.', 'warning');
                    btnSimpan.html(btnOriginalHTML).prop('disabled', false);
                    return;
                }
                // Validasi tambahan jika menambahkan unit baru
                if (!$('#search_kode_inventaris_temuan').val()) { // Jika tidak memilih barang existing
                    if (!$('#id_barang_induk_temuan').val()) {
                        Swal.fire('Peringatan!', 'Pilih Jenis Barang Induk untuk unit baru.', 'warning');
                        btnSimpan.html(btnOriginalHTML).prop('disabled', false);
                        return;
                    }
                    if (!$('#harga_perolehan_unit_temuan').val() || !$('#tanggal_perolehan_unit_temuan')
                        .val()) {
                        Swal.fire('Peringatan!',
                            'Harga dan Tanggal Perolehan Unit Temuan Baru wajib diisi.', 'warning');
                        btnSimpan.html(btnOriginalHTML).prop('disabled', false);
                        return;
                    }
                }


                fetch("{{ route($rolePrefix . 'stok-opname.add-barang-temuan') }}", { // Anda perlu membuat route ini
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            // Content-Type tidak di set agar browser otomatis mendeteksi FormData
                        },
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(err => {
                                throw err;
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Berhasil!', data.message, 'success');
                            $('#modalTambahBarangTemuan').modal('hide');
                            $('#formTambahBarangTemuan')[0].reset();
                            $("#search_kode_inventaris_temuan").val(null).trigger('change');
                            $("#id_barang_induk_temuan").val(null).trigger('change').prop('disabled',
                                false);
                            $('#no_seri_pabrik_temuan_group').hide();


                            // Tambahkan baris baru ke tabel detail SO (jika diperlukan refresh parsial)
                            // Atau cukup reload halaman/bagian tabel
                            if (data.detail_html) {
                                $('#detail-stok-opname-body').append(data.detail_html);
                                // Re-initialize select2 untuk baris baru jika perlu
                                $('#row-detail-' + data.new_detail_id + ' .select2-kondisi').select2({
                                    theme: "bootstrap-5",
                                    width: '100%',
                                    placeholder: "-- Pilih --",
                                    minimumResultsForSearch: Infinity
                                });
                                // Re-attach event listener ke tombol simpan baru
                                $('#row-detail-' + data.new_detail_id + ' .btn-save-detail').on('click',
                                    function() {
                                        // Logika simpan AJAX seperti di atas
                                        const detailId = this.dataset.detailId;
                                        const row = document.getElementById(
                                            `row-detail-${detailId}`);
                                        const kondisiFisikEl = row.querySelector(
                                            '.kondisi-fisik-input');
                                        const catatanFisikEl = row.querySelector(
                                            '.catatan-fisik-input');

                                        const kondisiFisik = kondisiFisikEl ? kondisiFisikEl.value :
                                            null;
                                        const catatanFisik = catatanFisikEl ? catatanFisikEl.value :
                                            null;

                                        if (!kondisiFisik) {
                                            Swal.fire('Peringatan!',
                                                'Kondisi fisik wajib dipilih untuk disimpan.',
                                                'warning');
                                            return;
                                        }
                                        const buttonOriginalText = this.innerHTML;
                                        this.innerHTML =
                                            `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
                                        this.disabled = true;

                                        fetch(`{{ route('admin.stok-opname.updateDetail', ['stokOpname' => $stokOpname->id, 'detail' => ':detailId']) }}`
                                                .replace(':detailId', detailId), {
                                                    method: 'PUT',
                                                    headers: {
                                                        'Content-Type': 'application/json',
                                                        'X-CSRF-TOKEN': csrfToken,
                                                        'Accept': 'application/json',
                                                    },
                                                    body: JSON.stringify({
                                                        kondisi_fisik: kondisiFisik,
                                                        catatan_fisik: catatanFisik
                                                    })
                                                })
                                            .then(response => {
                                                if (!response.ok) {
                                                    return response.json().then(err => {
                                                        throw err;
                                                    });
                                                }
                                                return response.json();
                                            })
                                            .then(data => {
                                                if (data.success) {
                                                    Swal.fire({
                                                        icon: 'success',
                                                        title: 'Berhasil',
                                                        text: data.message,
                                                        toast: true,
                                                        position: 'top-end',
                                                        showConfirmButton: false,
                                                        timer: 2000
                                                    });
                                                    row.classList.add('table-success');
                                                    setTimeout(() => row.classList.remove(
                                                        'table-success'), 2500);
                                                } else {
                                                    Swal.fire('Gagal!', data.message ||
                                                        'Gagal menyimpan detail.', 'error');
                                                }
                                            })
                                            .catch(error => {
                                                console.error('Error:', error);
                                                let errorMsg =
                                                    'Terjadi kesalahan jaringan atau sistem.';
                                                if (error && error.message) errorMsg = error
                                                    .message;
                                                if (error && error.errors) {
                                                    errorMsg = Object.values(error.errors)
                                                        .flat().join('<br>');
                                                }
                                                Swal.fire('Error!', errorMsg, 'error');
                                            })
                                            .finally(() => {
                                                this.innerHTML = buttonOriginalText;
                                                this.disabled = false;
                                            });
                                    });
                            } else {
                                // Fallback jika tidak ada HTML, misal reload halaman untuk lihat perubahan
                                // window.location.reload();
                            }

                        } else {
                            Swal.fire('Gagal!', data.message || 'Gagal menambahkan barang temuan.',
                                'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        let errorMsg = 'Terjadi kesalahan jaringan atau sistem.';
                        if (error && error.message) errorMsg = error.message;
                        if (error && error.errors) { // Handle validation errors Laravel
                            errorMsg = Object.values(error.errors).flat().join('<br>');
                        }
                        Swal.fire('Error!', errorMsg, 'error');
                    })
                    .finally(() => {
                        btnSimpan.html(btnOriginalHTML).prop('disabled', false);
                    });
            });


            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endpush
