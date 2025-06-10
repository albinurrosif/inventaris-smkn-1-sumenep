{{-- File: resources/views/admin/pemeliharaan/edit.blade.php --}}
@extends('layouts.app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('title', 'Edit Laporan Pemeliharaan')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        .select2-container--bootstrap-5 .select2-selection--single {
            height: calc(1.5em + .75rem + 2px) !important;
            padding: .375rem .75rem !important;
            font-size: 1rem !important;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            line-height: 1.5 !important;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + .75rem) !important;
        }

        .card-item-detail-edit {
            background-color: #e9ecef;
            border-radius: .25rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Edit Laporan Pemeliharaan: ID #{{ $pemeliharaan->id }}</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.pemeliharaan.index') }}">Pemeliharaan</a>
                            </li>
                            <li class="breadcrumb-item active">Edit Laporan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-10 offset-lg-1">
                <form action="{{ route('admin.pemeliharaan.update', $pemeliharaan->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informasi Unit Barang</h5>
                        </div>
                        <div class="card-body">
                            @if ($pemeliharaan->barangQrCode)
                                @php $barangQr = $pemeliharaan->barangQrCode; @endphp
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label d-block">Kode Unit:</label>
                                        <a href="{{ route('admin.barang-qr-code.show', $barangQr->id) }}"
                                            target="_blank"><code>{{ $barangQr->kode_inventaris_sekolah }}</code></a>
                                    </div>
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label d-block">Nama Barang:</label>
                                        <span>{{ $barangQr->barang->nama_barang ?? 'N/A' }}
                                            ({{ $barangQr->barang->merk_model ?? '-' }})</span>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label d-block">Lokasi Terkini:</label>
                                        <span>{{ $barangQr->ruangan->nama_ruangan ?? ($barangQr->id_pemegang_personal ? 'Pemegang: ' . optional($barangQr->pemegangPersonal)->username : 'Tidak Diketahui') }}</span>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label d-block">Kondisi Terkini Unit:</label>
                                        <span
                                            class="badge bg-{{ App\Models\BarangQrCode::getKondisiColor($barangQr->kondisi) }}">{{ $barangQr->kondisi }}</span>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label d-block">Status Terkini Unit:</label>
                                        <span
                                            class="badge bg-{{ App\Models\BarangQrCode::getStatusColor($barangQr->status) }}">{{ $barangQr->status }}</span>
                                    </div>
                                </div>
                                <input type="hidden" name="id_barang_qr_code" value="{{ $barangQr->id }}">
                            @else
                                <p class="text-danger">Data unit barang tidak ditemukan.</p>
                            @endif
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Detail Pengajuan</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="tanggal_pengajuan_edit" class="form-label">Tanggal Pengajuan <span
                                            class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" id="tanggal_pengajuan_edit"
                                        name="tanggal_pengajuan"
                                        value="{{ old('tanggal_pengajuan', $pemeliharaan->tanggal_pengajuan ? $pemeliharaan->tanggal_pengajuan->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
                                        required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="id_user_pengaju_edit" class="form-label">Pelapor</label>
                                    <select class="form-control select2-users" id="id_user_pengaju_edit"
                                        name="id_user_pengaju" disabled>
                                        <option value="{{ $pemeliharaan->pengaju->id ?? '' }}" selected>
                                            {{ $pemeliharaan->pengaju->username ?? 'N/A' }}</option>
                                    </select>
                                    {{-- Jika Admin bisa ganti pelapor --}}
                                    {{-- <select class="form-control select2-users" id="id_user_pengaju_edit" name="id_user_pengaju">
                                    @foreach ($usersListAll ?? [] as $user)
                                        <option value="{{ $user->id }}" {{ old('id_user_pengaju', $pemeliharaan->id_user_pengaju) == $user->id ? 'selected' : '' }}>{{ $user->username }}</option>
                                    @endforeach
                                </select> --}}
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="prioritas_edit" class="form-label">Prioritas <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="prioritas_edit" name="prioritas" required>
                                        @foreach (App\Models\Pemeliharaan::getValidPrioritas() as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ old('prioritas', $pemeliharaan->prioritas) == $key ? 'selected' : '' }}>
                                                {{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="catatan_pengajuan_edit" class="form-label">Deskripsi Kerusakan/Keluhan <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control" id="catatan_pengajuan_edit" name="catatan_pengajuan" rows="3" required>{{ old('catatan_pengajuan', $pemeliharaan->catatan_pengajuan) }}</textarea>
                            </div>
                        </div>
                    </div>

                    @if (Auth::user()->hasRole(App\Models\User::ROLE_ADMIN))
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Proses Persetujuan (Admin)</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="status_pengajuan_edit" class="form-label">Status Pengajuan <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select" id="status_pengajuan_edit" name="status_pengajuan"
                                            required>
                                            @foreach (App\Models\Pemeliharaan::getValidStatusPengajuan() as $value)
                                                <option value="{{ $value }}"
                                                    {{ old('status_pengajuan', $pemeliharaan->status_pengajuan) == $value ? 'selected' : '' }}>
                                                    {{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="id_user_penyetuju_edit" class="form-label">Disetujui/Ditolak
                                            Oleh</label>
                                        <select class="form-select select2-users" id="id_user_penyetuju_edit"
                                            name="id_user_penyetuju">
                                            <option value="">Pilih User</option>
                                            @foreach ($adminOperatorList ?? ($usersListAll ?? []) as $userAdminOp)
                                                <option value="{{ $userAdminOp->id }}"
                                                    {{ old('id_user_penyetuju', $pemeliharaan->id_user_penyetuju ?? Auth::id()) == $userAdminOp->id ? 'selected' : '' }}>
                                                    {{ $userAdminOp->username }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="tanggal_persetujuan_edit" class="form-label">Tanggal
                                            Persetujuan/Penolakan</label>
                                        <input type="datetime-local" class="form-control" id="tanggal_persetujuan_edit"
                                            name="tanggal_persetujuan"
                                            value="{{ old('tanggal_persetujuan', $pemeliharaan->tanggal_persetujuan ? $pemeliharaan->tanggal_persetujuan->format('Y-m-d\TH:i') : '') }}">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="catatan_persetujuan_edit" class="form-label">Catatan
                                        Persetujuan/Penolakan</label>
                                    <textarea class="form-control" id="catatan_persetujuan_edit" name="catatan_persetujuan" rows="3">{{ old('catatan_persetujuan', $pemeliharaan->catatan_persetujuan) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Proses Pengerjaan (Admin/PIC)</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="id_operator_pengerjaan_edit" class="form-label">PIC Pengerjaan</label>
                                        <select class="form-select select2-pic" id="id_operator_pengerjaan_edit"
                                            name="id_operator_pengerjaan">
                                            <option value="">Pilih Operator/PIC</option>
                                            @foreach ($picList ?? ($usersListAll ?? []) as $pic)
                                                <option value="{{ $pic->id }}"
                                                    {{ old('id_operator_pengerjaan', $pemeliharaan->id_operator_pengerjaan) == $pic->id ? 'selected' : '' }}>
                                                    {{ $pic->username }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="status_pengerjaan_edit" class="form-label">Status Pengerjaan <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select" id="status_pengerjaan_edit" name="status_pengerjaan"
                                            required>
                                            @foreach (App\Models\Pemeliharaan::getValidStatusPengerjaan() as $value)
                                                <option value="{{ $value }}"
                                                    {{ old('status_pengerjaan', $pemeliharaan->status_pengerjaan) == $value ? 'selected' : '' }}>
                                                    {{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="biaya_edit" class="form-label">Biaya (Rp)</label>
                                        <input type="number" class="form-control" id="biaya_edit" name="biaya"
                                            step="500" value="{{ old('biaya', $pemeliharaan->biaya) }}">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="tanggal_mulai_pengerjaan_edit" class="form-label">Tanggal Mulai
                                            Pengerjaan</label>
                                        <input type="datetime-local" class="form-control"
                                            id="tanggal_mulai_pengerjaan_edit" name="tanggal_mulai_pengerjaan"
                                            value="{{ old('tanggal_mulai_pengerjaan', $pemeliharaan->tanggal_mulai_pengerjaan ? $pemeliharaan->tanggal_mulai_pengerjaan->format('Y-m-d\TH:i') : '') }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="tanggal_selesai_pengerjaan_edit" class="form-label">Tanggal Selesai
                                            Pengerjaan</label>
                                        <input type="datetime-local" class="form-control"
                                            id="tanggal_selesai_pengerjaan_edit" name="tanggal_selesai_pengerjaan"
                                            value="{{ old('tanggal_selesai_pengerjaan', $pemeliharaan->tanggal_selesai_pengerjaan ? $pemeliharaan->tanggal_selesai_pengerjaan->format('Y-m-d\TH:i') : '') }}">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="deskripsi_pekerjaan_edit" class="form-label">Deskripsi Pekerjaan yang
                                        Dilakukan</label>
                                    <textarea class="form-control" id="deskripsi_pekerjaan_edit" name="deskripsi_pekerjaan" rows="3">{{ old('deskripsi_pekerjaan', $pemeliharaan->deskripsi_pekerjaan) }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="hasil_pemeliharaan_edit" class="form-label">Hasil Pemeliharaan</label>
                                    <textarea class="form-control" id="hasil_pemeliharaan_edit" name="hasil_pemeliharaan" rows="3">{{ old('hasil_pemeliharaan', $pemeliharaan->hasil_pemeliharaan) }}</textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="kondisi_barang_setelah_pemeliharaan_edit" class="form-label">Kondisi
                                        Barang Setelah Pemeliharaan <span class="text-danger">*</span></label>
                                    <select class="form-select" id="kondisi_barang_setelah_pemeliharaan_edit"
                                        name="kondisi_barang_setelah_pemeliharaan" required>
                                        <option value="">-- Pilih Kondisi --</option>
                                        @foreach (App\Models\BarangQrCode::getValidKondisi() as $kondisi)
                                            <option value="{{ $kondisi }}"
                                                {{ old('kondisi_barang_setelah_pemeliharaan', $pemeliharaan->kondisi_barang_setelah_pemeliharaan) == $kondisi ? 'selected' : '' }}>
                                                {{ $kondisi }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="catatan_pengerjaan_edit" class="form-label">Catatan Pengerjaan
                                        Tambahan</label>
                                    <textarea class="form-control" id="catatan_pengerjaan_edit" name="catatan_pengerjaan" rows="3">{{ old('catatan_pengerjaan', $pemeliharaan->catatan_pengerjaan) }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endif {{-- End Admin Role Check --}}

                    <div class="card-footer text-end">
                        <a href="{{ route('admin.pemeliharaan.show', $pemeliharaan->id) }}"
                            class="btn btn-light me-2">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            function initSelect2(selector, placeholderText) {
                $(selector).select2({
                    placeholder: placeholderText,
                    theme: "bootstrap-5",
                    width: '100%',
                    allowClear: true
                });
            }

            initSelect2('.select2-users', 'Pilih Pengguna...');
            initSelect2('.select2-pic', 'Pilih Operator/PIC...');

            $('#status_pengerjaan_edit').on('change', function() {
                const statusPengerjaan = $(this).val();
                const kondisiSetelahContainer = $('#kondisi_barang_setelah_pemeliharaan_edit').closest(
                    '.mb-3');
                const tanggalSelesaiInput = $('#tanggal_selesai_pengerjaan_edit');

                if (statusPengerjaan === '{{ App\Models\Pemeliharaan::STATUS_PENGERJAAN_SELESAI }}' ||
                    statusPengerjaan ===
                    '{{ App\Models\Pemeliharaan::STATUS_PENGERJAAN_TIDAK_DAPAT_DIPERBAIKI }}') {
                    kondisiSetelahContainer.show();
                    $('#kondisi_barang_setelah_pemeliharaan_edit').prop('required', true);
                    tanggalSelesaiInput.prop('required', true);
                    if (!tanggalSelesaiInput.val()) {
                        tanggalSelesaiInput.val('{{ now()->format('Y-m-d\TH:i') }}');
                    }
                } else {
                    kondisiSetelahContainer.hide();
                    $('#kondisi_barang_setelah_pemeliharaan_edit').prop('required', false).val('');
                    tanggalSelesaiInput.prop('required', false);
                }
            }).trigger('change'); // Trigger change untuk set initial state

            $('#status_pengajuan_edit').on('change', function() {
                const statusPengajuan = $(this).val();
                const tglPersetujuanInput = $('#tanggal_persetujuan_edit');
                if (statusPengajuan === '{{ App\Models\Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI }}' ||
                    statusPengajuan === '{{ App\Models\Pemeliharaan::STATUS_PENGAJUAN_DITOLAK }}') {
                    tglPersetujuanInput.prop('required', true);
                    if (!tglPersetujuanInput.val()) {
                        tglPersetujuanInput.val('{{ now()->format('Y-m-d\TH:i') }}');
                    }
                } else {
                    tglPersetujuanInput.prop('required', false);
                }
            }).trigger('change');

            $('#tanggal_mulai_pengerjaan_edit').on('change', function() {
                var tglMulai = $(this).val();
                $('#tanggal_selesai_pengerjaan_edit').attr('min', tglMulai);
                if ($('#tanggal_selesai_pengerjaan_edit').val() && $('#tanggal_selesai_pengerjaan_edit')
                    .val() < tglMulai) {
                    $('#tanggal_selesai_pengerjaan_edit').val('');
                }
            });
            // Inisialisasi min untuk tanggal selesai pengerjaan
            var tglMulaiAwal = $('#tanggal_mulai_pengerjaan_edit').val();
            if (tglMulaiAwal) {
                $('#tanggal_selesai_pengerjaan_edit').attr('min', tglMulaiAwal);
            }


        });
    </script>
@endpush
