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
                                <th style="width: 20%;">Catatan Fisik</th>
                                {{-- KOLOM BARU --}}
                                <th class="text-center" style="width: 10%;">Waktu Periksa</th>
                                <th class="text-center" style="width: 8%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="detail-stok-opname-body">
                            @forelse ($detailItems as $index => $detail)
                                {{-- Memanggil file partial yang sudah diperbarui --}}
                                @include('pages.stok-opname._item_detail_row', [
                                    'detail' => $detail,
                                    'index' => $index,
                                    'stokOpname' => $stokOpname,
                                    'kondisiFisikList' => $kondisiFisikList,
                                    'rolePrefix' => $rolePrefix,
                                ])
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">Belum ada unit barang dalam sesi stok opname ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- FORM BARU UNTUK CATATAN PENGERJAAN --}}
                @can('processDetails', $stokOpname)
                    @if ($stokOpname->status === StokOpname::STATUS_DRAFT && !$stokOpname->trashed())
                        <hr>
                        <form action="{{ route($rolePrefix . 'stok-opname.updateCatatan', $stokOpname->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="catatan_pengerjaan" class="form-label fw-bold">
                                            Catatan Pengerjaan / Ringkasan Hasil
                                        </label>
                                        <textarea class="form-control" id="catatan_pengerjaan" name="catatan_pengerjaan" rows="3"
                                            placeholder="Tuliskan ringkasan hasil pemeriksaan di sini... Contoh: Pemeriksaan selesai. Ditemukan 1 unit hilang dan 2 unit dalam kondisi kurang baik.">{{ old('catatan_pengerjaan', $stokOpname->catatan_pengerjaan) }}</textarea>
                                    </div>
                                    <button type="submit" class="btn btn-info btn-sm">
                                        <i class="fas fa-save me-1"></i> Simpan Catatan
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif
                @endcan

            </div>
            <div class="card-footer text-end">
                <a href="{{ route($rolePrefix . 'stok-opname.index') }}" class="btn btn-outline-secondary">Kembali ke
                    Daftar SO</a>

                @if ($stokOpname->status === StokOpname::STATUS_DRAFT && !$stokOpname->trashed())
                    @can('cancel', $stokOpname)
                        <button type="button" class="btn btn-danger btn-cancel-so" data-id="{{ $stokOpname->id }}"
                            data-ruangan="{{ $stokOpname->ruangan->nama_ruangan }}"
                            data-tanggal="{{ \Carbon\Carbon::parse($stokOpname->tanggal_opname)->isoFormat('DD MMM煨') }}">
                            <i class="fas fa-times-circle me-1"></i> Batalkan Sesi SO
                        </button>
                    @endcan
                    @can('finalize', $stokOpname)
                        {{-- Tombol Finalisasi sekarang hanya memicu modal, tidak lagi punya form sendiri --}}
                        <button type="button" class="btn btn-primary btn-finalize-so" data-bs-toggle="modal"
                            data-bs-target="#modalFinalizeStokOpname">
                            <i class="fas fa-check-circle me-1"></i> Finalisasi & Proses Hasil SO
                        </button>
                    @endcan
                @endif
            </div>
        </div>
    </div>

    {{-- MODAL DIALOGS --}}

    @can('finalize', $stokOpname)
        <div class="modal fade" id="modalFinalizeStokOpname" tabindex="-1" aria-labelledby="modalFinalizeLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalFinalizeLabel">Finalisasi Sesi Stok Opname</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    {{-- Form finalisasi sekarang tidak perlu input catatan --}}
                    <form id="formFinalizeStokOpname" method="POST"
                        action="{{ route($rolePrefix . 'stok-opname.finalize', $stokOpname->id) }}">
                        @csrf
                        <div class="modal-body">
                            <p>
                                Anda akan menyelesaikan sesi stok opname untuk ruangan:
                                <strong>{{ $stokOpname->ruangan->nama_ruangan }}</strong>.
                            </p>
                            <p class="text-danger">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Tindakan ini tidak dapat diurungkan. Sistem akan memproses semua hasil pemeriksaan fisik.
                                Pastikan semua item telah diperiksa.
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-success">Ya, Finalisasi Sekarang</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    {{-- Modal Tambah Barang Temuan --}}
    @if ($stokOpname->status === StokOpname::STATUS_DRAFT && !$stokOpname->trashed())
        @can('processDetails', $stokOpname)
            <div class="modal fade" id="modalTambahBarangTemuan" tabindex="-1"
                aria-labelledby="modalTambahBarangTemuanLabel" aria-hidden="true">
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

                                <div id="form-unit-baru-temuan">
                                    <h6 class="text-primary">Tambah Sebagai Unit Baru</h6>

                                    <div class="mb-3">
                                        <label for="id_barang_induk_temuan" class="form-label">Jenis Barang Induk <span
                                                class="text-danger">*</span></label>
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
                                        <label for="no_seri_pabrik_temuan" class="form-label">No. Seri Pabrik (Jika Ada &
                                            Jenis
                                            Barang Menggunakan)</label>
                                        <input type="text" class="form-control" id="no_seri_pabrik_temuan"
                                            name="no_seri_pabrik_new">
                                    </div>
                                    <div class="mb-3">
                                        <label for="harga_perolehan_unit_temuan" class="form-label">Harga Perolehan Unit
                                            Temuan
                                            <span class="text-danger"></span></label>
                                        <input type="number" class="form-control" id="harga_perolehan_unit_temuan"
                                            name="harga_perolehan_unit_new" step="0.01" min="0">
                                    </div>
                                    <div class="mb-3">
                                        <label for="tanggal_perolehan_unit_temuan" class="form-label">Tanggal Perolehan Unit
                                            Temuan <span class="text-danger"></span></label>
                                        <input type="date" class="form-control" id="tanggal_perolehan_unit_temuan"
                                            name="tanggal_perolehan_unit_new" value="{{ date('Y-m-d') }}">
                                    </div>
                                </div>
                                <hr>
                                <div id="detail-pemeriksaan-fisik">
                                    <h6 class="mt-3">Detail Pemeriksaan Fisik Barang Temuan</h6>
                                    <div class="mb-3">
                                        <label for="kondisi_fisik_temuan" class="form-label">Kondisi Fisik Temuan <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select" id="kondisi_fisik_temuan" name="kondisi_fisik_temuan"
                                            required style="width: 100%;">
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
        // Gunakan jQuery document ready untuk konsistensi
        $(function() {
            // ======================= VARIABEL & KONFIGURASI AWAL =======================
            const stokOpnameId = "{{ $stokOpname->id }}";
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            // Toast configuration untuk notifikasi
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            // ======================= INISIALISASI SAAT HALAMAN DIMUAT =======================
            initializeAllSelect2();
            attachAllEventHandlers();

            // ======================= DEFINISI FUNGSI-FUNGSI =======================

            /**
             * Menginisialisasi semua plugin Select2 di halaman ini.
             */
            function initializeAllSelect2() {
                // Select2 untuk kondisi fisik di tabel utama
                $('.select2-kondisi').select2({
                    theme: "bootstrap-5",
                    width: '100%',
                    placeholder: "-- Pilih --",
                    minimumResultsForSearch: Infinity
                });

                // Select2 standar di dalam modal
                $('#modalTambahBarangTemuan .select2-basic').select2({
                    theme: "bootstrap-5",
                    width: '100%',
                    dropdownParent: $("#modalTambahBarangTemuan")
                });

                // Select2 dengan AJAX untuk pencarian barang temuan - DIPERBAIKI
                initializeAjaxSelect2();
            }

            /**
             * Inisialisasi Select2 dengan AJAX - Fungsi terpisah untuk debugging
             */
            function initializeAjaxSelect2() {
                $('#search_kode_inventaris_temuan').select2({
                    theme: "bootstrap-5",
                    width: '100%',
                    placeholder: "Ketik minimal 3 karakter untuk mencari...",
                    allowClear: true,
                    dropdownParent: $("#modalTambahBarangTemuan"),
                    minimumInputLength: 3,
                    language: {
                        inputTooShort: function() {
                            return "Ketik minimal 3 karakter";
                        },
                        searching: function() {
                            return "Mencari...";
                        },
                        noResults: function() {
                            return "Tidak ada hasil ditemukan";
                        },
                        errorLoading: function() {
                            return "Gagal memuat data. Silakan coba lagi.";
                        }
                    },
                    ajax: {
                        url: "{{ route($rolePrefix . 'stok-opname.search-barang-qr') }}",
                        type: 'GET',
                        dataType: 'json',
                        delay: 300,
                        data: function(params) {
                            return {
                                q: params.term,
                                id_stok_opname: stokOpnameId,
                                page: params.page || 1
                            };
                        },
                        processResults: function(data, params) {
                            console.log('AJAX Response:', data); // Debug log

                            params.page = params.page || 1;

                            if (!data || typeof data !== 'object') {
                                console.error('Invalid response format:', data);
                                return {
                                    results: [],
                                    pagination: {
                                        more: false
                                    }
                                };
                            }

                            // Handle different response formats
                            let items = [];
                            if (data.items && Array.isArray(data.items)) {
                                items = data.items;
                            } else if (data.data && Array.isArray(data.data)) {
                                items = data.data;
                            } else if (Array.isArray(data)) {
                                items = data;
                            }

                            const results = items.map(function(item) {
                                let text = '';

                                // Build display text
                                if (item.nama_barang_induk || item.nama_barang) {
                                    text = `${item.nama_barang_induk || item.nama_barang}`;
                                }

                                if (item.kode_inventaris_sekolah) {
                                    text += ` (${item.kode_inventaris_sekolah})`;
                                }

                                if (item.no_seri_pabrik) {
                                    text += ` - Seri: ${item.no_seri_pabrik}`;
                                }

                                // Add status indicators
                                if (item.deleted_at) {
                                    text += ' - (Diarsipkan)';
                                }

                                // Add location info
                                let lokasi = '';
                                if (item.ruangan_saat_ini) {
                                    lokasi = item.ruangan_saat_ini;
                                } else if (item.pemegang_saat_ini) {
                                    lokasi = `Pemegang: ${item.pemegang_saat_ini}`;
                                } else {
                                    lokasi = 'Tanpa Lokasi';
                                }
                                text += ` | Lokasi: ${lokasi}`;

                                return {
                                    id: item.id,
                                    text: text,
                                    data: item // Store original data for later use
                                };
                            });

                            return {
                                results: results,
                                pagination: {
                                    more: (params.page * 10) < (data.total || items.length)
                                }
                            };
                        },
                        cache: true,
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', {
                                xhr,
                                status,
                                error
                            });

                            // Show user-friendly error message
                            Toast.fire({
                                icon: 'error',
                                title: 'Gagal memuat data pencarian'
                            });

                            return {
                                results: []
                            };
                        }
                    }
                });
            }

            /**
             * Memasang semua event listener yang diperlukan.
             */
            function attachAllEventHandlers() {
                $(document)
                    .off('click', '.btn-save-detail')
                    .on('click', '.btn-save-detail', function() {
                        saveDetailRow($(this));
                    });


                $(document)
                    .off('click', '.btn-cancel-so')
                    .on('click', '.btn-cancel-so', handleCancel);

                $('#id_barang_induk_temuan').off('change').on('change', toggleNoSeriInput).trigger('change');
                $('#search_kode_inventaris_temuan').off('select2:select select2:unselect select2:clear').on(
                    'select2:select', handleSelectExistingItem).on('select2:unselect select2:clear',
                    handleUnselectExistingItem);
                $('#formTambahBarangTemuan').off('submit').on('submit', handleFormTemuanSubmit);
            }
            /**
             * Reset form modal ketika ditutup
             */
            function resetModalForm() {
                $('#formTambahBarangTemuan')[0].reset();
                $('#search_kode_inventaris_temuan').val(null).trigger('change');
                $('#no_seri_pabrik_temuan_group').hide();
                $('#form-unit-baru-temuan :input').prop('disabled', false);

                // Reset Select2
                $('.select2-basic').val(null).trigger('change');
            }

            /**
             * Logika untuk menyimpan perubahan pada satu baris detail.
             */
            // Replace the saveDetailRow function in your JavaScript code with this fixed version:

            function saveDetailRow($button) {
                const detailId = $button.data('detail-id');
                const $row = $(`#row-detail-${detailId}`);
                const kondisiFisik = $row.find('.kondisi-fisik-input').val();
                const catatanFisik = $row.find('.catatan-fisik-input').val();

                if (!kondisiFisik) {
                    Swal.fire('Peringatan!', 'Kondisi fisik wajib dipilih.', 'warning');
                    return;
                }

                const originalHTML = $button.html();
                $button.html(`<span class="spinner-border spinner-border-sm"></span>`).prop('disabled', true);

                $.ajax({
                    // The fix is applied here: 'detail' is changed to 'detailId'
                    url: "{{ route($rolePrefix . 'stok-opname.updateDetail', ['detail' => ':detailId']) }}"
                        .replace(':detailId', detailId),
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    data: JSON.stringify({
                        kondisi_fisik: kondisiFisik,
                        catatan_fisik: catatanFisik
                    }),
                    success: function(data) {
                        if (data.success) {
                            Toast.fire({
                                icon: 'success',
                                title: data.message || 'Data berhasil disimpan'
                            });

                            if (data.waktu_diperiksa) {
                                $(`#waktu-periksa-${detailId}`).text(data.waktu_diperiksa);
                            }

                            $row.addClass('table-success');
                            setTimeout(() => $row.removeClass('table-success'), 2500);
                        } else {
                            Swal.fire('Gagal!', data.message || 'Gagal menyimpan data.', 'error');
                        }
                    },
                    error: function(xhr) {
                        console.error('Save detail error:', xhr);
                        let errorMsg = 'Terjadi kesalahan sistem.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        Swal.fire('Error!', errorMsg, 'error');
                    },
                    complete: function() {
                        $button.html(originalHTML).prop('disabled', false);
                    }
                });
            }

            /**
             * Handle ketika barang existing dipilih
             */
            function handleSelectExistingItem(e) {
                console.log('Item selected, disabling new unit form and highlighting physical check section.');

                // 1. Nonaktifkan form untuk membuat unit baru
                $('#form-unit-baru-temuan :input')
                    .prop('disabled', true)
                    .prop('required', false) // Hapus 'required' dari semua field di grup ini
                    .removeClass('is-invalid');
                $('.invalid-feedback').remove(); // Hapus pesan error lama jika ada

                // 2. Beri sorotan visual ke bagian yang harus diisi
                const fisikSection = $('#detail-pemeriksaan-fisik'); // Pastikan Anda punya ID ini di HTML
                fisikSection.addClass('bg-light p-3 rounded border border-primary');
                $('html, body').animate({
                    scrollTop: fisikSection.offset().top - 100 // Scroll ke bagian yang perlu diisi
                }, 500);

                Toast.fire({
                    icon: 'info',
                    title: 'Barang ditemukan! Silakan isi kondisi fisiknya di bawah.'
                });
            }

            /**
             * Handle ketika barang existing tidak dipilih
             */
            function handleUnselectExistingItem() {
                console.log('Item unselected, enabling new unit form.');

                // 1. Aktifkan kembali form unit baru
                $('#form-unit-baru-temuan :input').prop('disabled', false);

                // 2. Tambahkan kembali 'required' ke kolom yang wajib untuk unit baru
                $('#id_barang_induk_temuan').prop('required', true);
                $('#harga_perolehan_unit_temuan').prop('required', true);
                $('#tanggal_perolehan_unit_temuan').prop('required', true);
                // Kolom 'kondisi_fisik_temuan' sudah 'required' secara permanen, jadi tidak perlu diubah.

                // 3. Hapus sorotan visual
                $('#detail-pemeriksaan-fisik').removeClass('bg-light p-3 rounded border border-primary');

                Toast.fire({
                    icon: 'info',
                    title: 'Form unit baru diaktifkan kembali.'
                });
            }
            /**
             * Toggle input No. Seri berdasarkan jenis barang
             */
            function toggleNoSeriInput() {
                const $selected = $(this).find('option:selected');
                const usesSerial = $selected.data('uses-serial') === true || $selected.data('uses-serial') ===
                    'true';

                console.log('Toggle serial input:', usesSerial);

                if (usesSerial) {
                    $('#no_seri_pabrik_temuan_group').show();
                } else {
                    $('#no_seri_pabrik_temuan_group').hide();
                    $('#no_seri_pabrik_temuan').val('');
                }
            }

            /**
             * Handle submit form barang temuan
             */
            function handleFormTemuanSubmit(e) {
                e.preventDefault();

                console.log('Form temuan submitted');

                const $form = $(this);
                const $btnSimpan = $('#btnSimpanBarangTemuan');
                const btnOriginalHTML = $btnSimpan.html();

                // Clear previous validation errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                // Basic validation
                const kondisiFisik = $('#kondisi_fisik_temuan').val();
                if (!kondisiFisik) {
                    $('#kondisi_fisik_temuan').addClass('is-invalid');
                    Swal.fire('Peringatan!', 'Kondisi fisik temuan wajib dipilih.', 'warning');
                    return;
                }

                const existingItemId = $('#search_kode_inventaris_temuan').val();
                const barangIndukId = $('#id_barang_induk_temuan').val();

                if (!existingItemId && !barangIndukId) {
                    Swal.fire('Peringatan!', 'Pilih barang existing atau jenis barang induk untuk unit baru.',
                        'warning');
                    return;
                }

                if (!existingItemId) {
                    // Validate new unit form
                    const harga = $('#harga_perolehan_unit_temuan').val();
                    const tanggal = $('#tanggal_perolehan_unit_temuan').val();

                    if (!harga || parseFloat(harga) <= 0) {
                        $('#harga_perolehan_unit_temuan').addClass('is-invalid');
                        Swal.fire('Peringatan!', 'Harga perolehan harus diisi dan lebih dari 0.', 'warning');
                        return;
                    }

                    if (!tanggal) {
                        $('#tanggal_perolehan_unit_temuan').addClass('is-invalid');
                        Swal.fire('Peringatan!', 'Tanggal perolehan harus diisi.', 'warning');
                        return;
                    }
                }

                $btnSimpan.html(`<span class="spinner-border spinner-border-sm"></span> Menambahkan...`).prop(
                    'disabled', true);

                const formData = new FormData(this);

                // Debug form data
                console.log('Form data being sent:');
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }

                $.ajax({
                    url: "{{ route($rolePrefix . 'stok-opname.add-barang-temuan') }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    success: function(data) {
                        console.log('Add temuan success:', data);

                        if (data.success) {
                            // Tampilkan notifikasi sukses, dan SETELAH notifikasi ditutup, refresh halaman.
                            Swal.fire({
                                title: 'Berhasil!',
                                text: data.message || 'Barang temuan berhasil ditambahkan.',
                                icon: 'success',
                                timer: 2000, // Notifikasi akan hilang setelah 2 detik
                                showConfirmButton: false
                            }).then(() => {
                                location.reload(); // Refresh halaman untuk data yang sinkron
                            });
                        } else {
                            Swal.fire('Gagal!', data.message || 'Gagal menambahkan barang temuan.',
                                'error');
                        }
                    },
                    error: function(xhr) {
                        console.error('Add temuan error:', xhr);

                        let errorMsg = 'Terjadi kesalahan sistem.';

                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            }

                            // Handle validation errors
                            if (xhr.responseJSON.errors) {
                                const errors = xhr.responseJSON.errors;

                                // Display field-specific errors
                                Object.keys(errors).forEach(field => {
                                    const $field = $(`[name="${field}"]`);
                                    if ($field.length) {
                                        $field.addClass('is-invalid');
                                        $field.after(
                                            `<div class="invalid-feedback">${errors[field][0]}</div>`
                                        );
                                    }
                                });

                                errorMsg += '<br><ul class="text-start ps-3 mb-0">';
                                Object.values(errors).forEach(fieldErrors => {
                                    fieldErrors.forEach(error => {
                                        errorMsg += `<li>${error}</li>`;
                                    });
                                });
                                errorMsg += '</ul>';
                            }
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            html: errorMsg
                        });
                    },
                    complete: function() {
                        $btnSimpan.html(btnOriginalHTML).prop('disabled', false);
                    }
                });
            }



            /**
             * Handle pembatalan stok opname
             */
            function handleCancel() {
                const ruangan = $(this).data('ruangan');
                const tanggal = $(this).data('tanggal');

                Swal.fire({
                    title: 'Batalkan Sesi Stok Opname?',
                    html: `Anda akan membatalkan stok opname untuk:<br><strong>${ruangan}</strong><br>Tanggal: ${tanggal}<br><br>Tindakan ini tidak dapat diurungkan!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Batalkan!',
                    cancelButtonText: 'Tidak'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = $('#formCancelStokOpname');
                        form.attr('action',
                            "{{ route($rolePrefix . 'stok-opname.cancel', $stokOpname->id) }}");
                        form.submit();
                    }
                });
            }

            // ======================= UTILITY FUNCTIONS =======================

            /**
             * Debug function untuk memeriksa status AJAX
             */
            function debugAjaxStatus() {
                console.log('CSRF Token:', csrfToken);
                console.log('Stok Opname ID:', stokOpnameId);
                console.log('Search URL:', "{{ route($rolePrefix . 'stok-opname.search-barang-qr') }}");
            }

            // Call debug function on page load
            debugAjaxStatus();
        });

        // ======================= GLOBAL ERROR HANDLER =======================
        $(document).ajaxError(function(event, xhr, settings, thrownError) {
            console.error('Global AJAX Error:', {
                url: settings.url,
                status: xhr.status,
                statusText: xhr.statusText,
                responseText: xhr.responseText,
                thrownError: thrownError
            });

            // Don't show error for Select2 AJAX calls to avoid spam
            if (!settings.url.includes('search-barang-qr')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan Koneksi',
                    text: 'Terjadi kesalahan dalam komunikasi dengan server. Silakan refresh halaman dan coba lagi.'
                });
            }
        });
    </script>
@endpush
