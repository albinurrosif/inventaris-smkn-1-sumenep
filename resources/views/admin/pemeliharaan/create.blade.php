{{-- File: resources/views/admin/pemeliharaan/create.blade.php --}}
@extends('layouts.app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('title', 'Buat Laporan Pemeliharaan Baru')

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
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Lapor Kerusakan/Pemeliharaan Aset</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.pemeliharaan.index') }}">Pemeliharaan</a>
                            </li>
                            <li class="breadcrumb-item active">Lapor Baru</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Form Laporan Pemeliharaan</h5>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('admin.pemeliharaan.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="id_barang_qr_code" class="form-label">Unit Barang yang Dilaporkan <span
                                        class="text-danger">*</span></label>
                                <select class="form-control select2-barang" id="id_barang_qr_code" name="id_barang_qr_code"
                                    required>
                                    <option value="">Pilih Unit Barang...</option>
                                    {{-- Opsi akan diisi oleh PemeliharaanController saat mengirim view --}}
                                    {{-- Atau jika form ini juga untuk Operator, barangQrOptions bisa lebih terbatas --}}
                                    @if (isset($barangQrCode) && $barangQrCode) {{-- Jika barang dipilih dari halaman sebelumnya --}}
                                        <option value="{{ $barangQrCode->id }}" selected>
                                            {{ $barangQrCode->barang->nama_barang }}
                                            ({{ $barangQrCode->kode_inventaris_sekolah }}) - Kondisi:
                                            {{ $barangQrCode->kondisi }}
                                        </option>
                                    @elseif(isset($barangQrOptions))
                                        @foreach ($barangQrOptions as $item)
                                            <option value="{{ $item['id'] }}"
                                                {{ old('id_barang_qr_code') == $item['id'] ? 'selected' : '' }}>
                                                {{ $item['text'] }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @if (isset($error_barang_qr_code_tidak_valid))
                                    <div class="invalid-feedback d-block">{{ $error_barang_qr_code_tidak_valid }}</div>
                                @endif
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tanggal_pengajuan" class="form-label">Tanggal Pengajuan <span
                                            class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" id="tanggal_pengajuan"
                                        name="tanggal_pengajuan"
                                        value="{{ old('tanggal_pengajuan', now()->format('Y-m-d\TH:i')) }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="prioritas" class="form-label">Prioritas <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="prioritas" name="prioritas" required>
                                        @foreach (App\Models\Pemeliharaan::getValidPrioritas() as $key => $value)
                                            <option value="{{ $key }}"
                                                {{ old('prioritas', App\Models\Pemeliharaan::PRIORITAS_SEDANG) == $key ? 'selected' : '' }}>
                                                {{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="catatan_pengajuan" class="form-label">Deskripsi Kerusakan/Keluhan <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control" id="catatan_pengajuan" name="catatan_pengajuan" rows="4" required
                                    placeholder="Jelaskan kerusakan atau kebutuhan pemeliharaan secara detail...">{{ old('catatan_pengajuan') }}</textarea>
                            </div>

                            {{-- Admin bisa langsung setujui atau tugaskan --}}
                            @if (Auth::user()->hasRole(App\Models\User::ROLE_ADMIN))
                                <hr>
                                <h6 class="mb-3">Opsi Administratif (Opsional)</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="id_operator_pengerjaan_create" class="form-label">Tugaskan ke Operator
                                            (PIC)</label>
                                        <select class="form-select select2-pic" id="id_operator_pengerjaan_create"
                                            name="id_operator_pengerjaan">
                                            <option value="">Pilih Operator Jika Langsung Ditugaskan</option>
                                            @foreach ($picList ?? [] as $pic)
                                                <option value="{{ $pic->id }}"
                                                    {{ old('id_operator_pengerjaan') == $pic->id ? 'selected' : '' }}>
                                                    {{ $pic->username }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="status_pengajuan_create" class="form-label">Status Pengajuan
                                            Awal</label>
                                        <select class="form-select" id="status_pengajuan_create"
                                            name="status_pengajuan_awal">
                                            <option value="{{ App\Models\Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN }}"
                                                selected>Diajukan</option>
                                            <option value="{{ App\Models\Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI }}">
                                                Langsung Disetujui</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="deskripsi_pekerjaan_create" class="form-label">Deskripsi Pekerjaan Awal
                                        (Jika langsung ditugaskan)</label>
                                    <textarea class="form-control" id="deskripsi_pekerjaan_create" name="deskripsi_pekerjaan" rows="3"
                                        placeholder="Misal: Periksa layar, ganti lampu proyektor, dll.">{{ old('deskripsi_pekerjaan') }}</textarea>
                                </div>
                            @endif

                            <div class="d-flex justify-content-end">
                                <a href="{{ route(Auth::user()->getRolePrefix() . 'pemeliharaan.index') }}"
                                    class="btn btn-light me-2">Batal</a>
                                <button type="submit" class="btn btn-primary">Kirim Laporan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2-barang').select2({
                placeholder: "Cari kode unit atau nama barang...",
                theme: "bootstrap-5",
                width: '100%',
                ajax: {
                    url: "{{ route('admin.stok-opname.search-barang-qr') }}", // Menggunakan route yang sudah ada untuk search barang
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term, // search term
                            page: params.page || 1
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.items.map(function(item) {
                                let displayText = item.nama_barang_induk + ' (' + item
                                    .kode_inventaris_sekolah + ')';
                                if (item.no_seri_pabrik) displayText += ' - SN: ' + item
                                    .no_seri_pabrik;
                                displayText += ' - Kondisi: ' + item.kondisi_saat_ini;
                                if (item.ruangan_saat_ini) displayText += ' - Lokasi: ' + item
                                    .ruangan_saat_ini;
                                else if (item.pemegang_saat_ini) displayText +=
                                    ' - Pemegang: ' + item.pemegang_saat_ini;
                                return {
                                    id: item.id,
                                    text: displayText
                                };
                            }),
                            pagination: {
                                more: (params.page * 15) < data
                                    .total_count // Ganti data.total_count jika API Anda menyediakannya
                            }
                        };
                    },
                    cache: true
                }
            });

            $('.select2-pic').select2({
                placeholder: "Pilih Operator (PIC)",
                theme: "bootstrap-5",
                width: '100%',
                allowClear: true
            });

            // Jika ada barangQrCode yang sudah dipilih (misal dari halaman detail unit barang)
            @if (isset($barangQrCode) && $barangQrCode)
                var selectedBarang = {
                    id: "{{ $barangQrCode->id }}",
                    text: "{{ $barangQrCode->barang->nama_barang }} ({{ $barangQrCode->kode_inventaris_sekolah }}) - Kondisi: {{ $barangQrCode->kondisi }}"
                };
                var option = new Option(selectedBarang.text, selectedBarang.id, true, true);
                $('.select2-barang').append(option).trigger('change');
                $('.select2-barang').prop('disabled',
                true); // Langsung disable jika sudah dipilih dari halaman sebelumnya
            @endif
        });
    </script>
@endpush
