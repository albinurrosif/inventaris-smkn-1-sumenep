@extends('layouts.app') {{-- Sesuaikan dengan path layout admin Anda --}}

@section('title', 'Detail Log Aktivitas #' . $logAktivitas->id)

@push('styles')
    <style>
        .data-json-detail {
            max-height: 350px;
            /* Sedikit lebih tinggi */
            overflow-y: auto;
            background-color: #272822;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.9em;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .card-header .btn-back {
            /* Tidak perlu float jika menggunakan d-flex di parent */
        }

        .table-detail th {
            width: 25%;
            /* Atur lebar kolom header di tabel detail */
            background-color: #f8f9fa;
            /* Beri sedikit perbedaan warna pada header */
        }

        .table-detail td,
        .table-detail th {
            vertical-align: top;
            /* Agar konten panjang rata atas */
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">@yield('title')</h1>
            <a href="{{ url()->previous() ?? route('admin.log-aktivitas.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Informasi Log</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12"> {{-- Buat satu kolom agar tabel tidak terlalu sempit --}}
                        <table class="table table-bordered table-detail">
                            <tr>
                                <th>ID Log</th>
                                <td>{{ $logAktivitas->id }}</td>
                            </tr>
                            <tr>
                                <th>Waktu</th>
                                <td>{{ $logAktivitas->created_at->isoFormat('dddd, DD MMMM YYYY, HH:mm:ss Z') }}
                                    ({{ $logAktivitas->created_at->diffForHumans() }})</td>
                            </tr>
                            <tr>
                                <th>Pengguna</th>
                                <td>
                                    @if ($logAktivitas->user)
                                        {{ $logAktivitas->user->username }} (ID: {{ $logAktivitas->user->id }}, Role:
                                        {{ Str::ucfirst($logAktivitas->user->role) }})
                                    @else
                                        Sistem / Tidak Diketahui
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Aktivitas</th>
                                <td>{{ $logAktivitas->aktivitas }}</td>
                            </tr>
                            <tr>
                                <th>Deskripsi Tambahan</th>
                                <td>
                                    @php
                                        $deskripsiLengkap = '';
                                        if (
                                            property_exists($logAktivitas, 'deskripsi') &&
                                            !empty($logAktivitas->deskripsi)
                                        ) {
                                            $deskripsiLengkap = $logAktivitas->deskripsi; // Jika ada kolom 'deskripsi' di model
                                        } else {
                                            $namaEntitas = '';
                                            $dataUntukCek = $logAktivitas->data_baru ?? $logAktivitas->data_lama;
                                            if (is_array($dataUntukCek)) {
                                                if (isset($dataUntukCek['nama_barang'])) {
                                                    $namaEntitas = $dataUntukCek['nama_barang'];
                                                } elseif (isset($dataUntukCek['nama_kategori'])) {
                                                    $namaEntitas = $dataUntukCek['nama_kategori'];
                                                } elseif (isset($dataUntukCek['name'])) {
                                                    $namaEntitas = $dataUntukCek['name'];
                                                } elseif (isset($dataUntukCek['username'])) {
                                                    $namaEntitas = $dataUntukCek['username'];
                                                } elseif (isset($dataUntukCek['judul'])) {
                                                    $namaEntitas = $dataUntukCek['judul'];
                                                }
                                                // Tambahkan field umum lainnya
                                            }

                                            $deskripsiLengkap = Str::ucfirst($logAktivitas->aktivitas);
                                            if ($logAktivitas->model_terkait) {
                                                $deskripsiLengkap .= ' ' . class_basename($logAktivitas->model_terkait);
                                                if ($logAktivitas->id_model_terkait) {
                                                    $deskripsiLengkap .= ' #' . $logAktivitas->id_model_terkait;
                                                }
                                            }
                                            if (!empty($namaEntitas)) {
                                                $deskripsiLengkap .= ' ("' . $namaEntitas . '")';
                                            }
                                        }
                                    @endphp
                                    {{ $deskripsiLengkap ?: '-' }}
                                </td>
                            </tr>
                            <tr>
                                <th>Model Terkait</th>
                                <td>{{ $logAktivitas->model_terkait ? class_basename($logAktivitas->model_terkait) : '-' }}
                                </td>
                            </tr>
                            <tr>
                                <th>ID Model Terkait</th>
                                <td>
                                    {{ $logAktivitas->id_model_terkait ?: '-' }}
                                    @if ($modelTerkaitInstance)
                                        @php
                                            $linkRouteName = '';
                                            $linkRouteParams = is_array($logAktivitas->id_model_terkait)
                                                ? $logAktivitas->id_model_terkait
                                                : ['id' => $logAktivitas->id_model_terkait]; // Default parameter
                                            $modelBaseName = class_basename($logAktivitas->model_terkait);

                                            // Penyesuaian nama rute berdasarkan model
                                            // Pastikan nama rute ini sesuai dengan yang ada di web.php
                                            // Dan pastikan parameter yang dibutuhkan rute sesuai
                                            $routeMappings = [
                                                'Barang' => 'barang.show', // Global route
                                                'BarangQrCode' => 'barang-qr-code.show', // Global route
                                                'User' => 'admin.users.show',
                                                'Ruangan' => 'admin.ruangan.show',
                                                'KategoriBarang' => 'admin.kategori-barang.show',
                                                'Peminjaman' => 'peminjaman.show', // Global route
                                                'Pemeliharaan' => 'admin.pemeliharaan.show', // Atau global 'pemeliharaan.show'
                                                'StokOpname' => 'admin.stok-opname.show', // Atau global 'stok-opname.show'
                                                'ArsipBarang' => 'admin.arsip-barang.show',
                                                // Tambahkan model lain jika perlu
                                            ];

                                            if (array_key_exists($modelBaseName, $routeMappings)) {
                                                $linkRouteName = $routeMappings[$modelBaseName];
                                                // Beberapa rute mungkin butuh parameter berbeda dari sekadar 'id'
                                                // Contoh jika BarangQrCode menggunakan 'barangQrCode' sebagai nama parameter:
                                                if ($modelBaseName === 'BarangQrCode') {
                                                    $linkRouteParams = [
                                                        'barangQrCode' => $logAktivitas->id_model_terkait,
                                                    ];
                                                }
                                                // Sesuaikan parameter lain jika perlu
                                            }
                                        @endphp
                                        @if ($linkRouteName && Route::has($linkRouteName))
                                            <a href="{{ route($linkRouteName, $linkRouteParams) }}" target="_blank"
                                                class="ml-2 badge badge-info">(Lihat Data Terkait)</a>
                                        @else
                                            <span class="ml-2 badge badge-secondary">(Data Terkait Ada, Rute Tampilan Tidak
                                                Ditemukan)</span>
                                        @endif
                                    @elseif($logAktivitas->model_terkait && $logAktivitas->id_model_terkait)
                                        <span class="ml-2 badge badge-warning">(Data Terkait Mungkin Telah Dihapus)</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>IP Address</th>
                                <td>{{ $logAktivitas->ip_address ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>User Agent</th>
                                <td style="white-space: pre-wrap; word-break: break-all;">
                                    <small>{{ $logAktivitas->user_agent ?? '-' }}</small></td>
                            </tr>
                        </table>
                    </div>
                </div>

                {{-- GANTI BLOK LAMA DENGAN YANG INI --}}
                <hr>

                <div class="row mt-4">
                    <div class="col-lg-12">

                        {{-- Kasus 1: Ada perubahan data (log tipe 'updated') --}}
                        @if (!empty($perubahan))
                            <h6><i class="fas fa-exchange-alt mr-2"></i>Detail Perubahan Data</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" style="table-layout: fixed; width: 100%;">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 25%;">Kolom</th>
                                            <th style="width: 37.5%;">Data Lama</th>
                                            <th style="width: 37.5%;">Data Baru</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($perubahan as $item)
                                            <tr>
                                                <td><strong>{{ $item['field'] }}</strong></td>
                                                <td style="word-wrap: break-word; background-color: #ffeef0;">
                                                    @if (empty($item['lama']))
                                                        <em class="text-muted">(Tidak ada data)</em>
                                                    @else
                                                        <span class="text-danger">{{ $item['lama'] }}</span>
                                                    @endif
                                                </td>
                                                <td style="word-wrap: break-word; background-color: #e6ffed;">
                                                    @if (empty($item['baru']))
                                                        <em class="text-muted">(Data dihapus/dikosongkan)</em>
                                                    @else
                                                        <span class="text-success">{{ $item['baru'] }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Kasus 2: Tidak ada perubahan, tapi ada data baru (log tipe 'created') --}}
                        @elseif (!empty($dataBaru))
                            <h6><i class="fas fa-plus-circle mr-2"></i>Detail Data Baru yang Dibuat</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tbody>
                                        @foreach ($dataBaru as $key => $value)
                                            @if (!in_array($key, ['password', 'remember_token']))
                                                <tr>
                                                    <td style="width: 25%;">
                                                        <strong>{{ $fieldMappings[$key] ?? ucwords(str_replace('_', ' ', $key)) }}</strong>
                                                    </td>
                                                    <td>{{ $this->formatValue($value) }}</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Kasus 3: Tidak ada data yang perlu ditampilkan --}}
                        @else
                            <p class="text-muted mt-3">Tidak ada detail data lama atau baru yang tercatat.</p>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Tambahan JS khusus jika ada --}}
@endpush
