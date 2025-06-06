@extends('layouts.app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('title', 'Dashboard Admin')

@push('styles')
    {{-- Jika Anda menggunakan grafik tertentu yang memerlukan CSS khusus --}}
    <style>
        .card-h-100 {
            height: calc(100% - 1.5rem);
            /* 1.5rem adalah default margin-bottom untuk .card */
        }

        .dashboard-stat-icon {
            font-size: 2.5rem;
            /* Ukuran ikon pada kartu statistik */
            opacity: 0.8;
        }

        .list-group-item-action {
            transition: background-color 0.2s ease-in-out;
        }

        .list-group-item-action:hover {
            background-color: #f8f9fa;
        }

        .chart-container {
            position: relative;
            height: 300px;
            /* Sesuaikan tinggi default grafik */
            width: 100%;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Dashboard Admin</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">SIMA</a></li>
                            <li class="breadcrumb-item active">Dashboard Admin</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baris Statistik Utama --}}
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100 bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-white-50 mb-2">Total Jenis Barang</p>
                                <h4 class="mb-0 text-white">{{ $jumlahJenisBarang ?? 0 }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="mdi mdi-archive-outline dashboard-stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100 bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-white-50 mb-2">Total Unit Barang Aktif</p>
                                <h4 class="mb-0 text-white">{{ $jumlahUnitBarang ?? 0 }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="mdi mdi-cube-send dashboard-stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100 bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-white-50 mb-2">Total Pengguna</p>
                                <h4 class="mb-0 text-white">{{ $jumlahUser ?? 0 }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="mdi mdi-account-group-outline dashboard-stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100 bg-warning text-dark"> {{-- Diubah ke text-dark agar kontras --}}
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-dark-50 mb-2">Peminjaman Menunggu</p>
                                <h4 class="mb-0">{{ $peminjamanMenunggu ?? 0 }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="mdi mdi-file-document-edit-outline dashboard-stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100 bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-white-50 mb-2">Unit Sedang Dipinjam</p>
                                <h4 class="mb-0 text-white">{{ $jumlahUnitDipinjam ?? 0 }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="mdi mdi-briefcase-arrow-left-right-outline dashboard-stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100 bg-secondary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-white-50 mb-2">Unit Dlm. Pemeliharaan</p>
                                <h4 class="mb-0 text-white">{{ $jumlahUnitDalamPemeliharaan ?? 0 }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="mdi mdi-tools dashboard-stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100 bg-light text-dark">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-muted mb-2">Pemeliharaan Menunggu</p>
                                <h4 class="mb-0">{{ $pemeliharaanMenungguPersetujuan ?? 0 }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="mdi mdi-timer-sand dashboard-stat-icon text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Tambahkan card statistik lain jika perlu --}}
        </div>

        {{-- Baris Grafik --}}
        <div class="row">
            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Barang Aktif per Kategori (Top 5)</h4>
                    </div>
                    <div class="card-body">
                        <div id="barangPerKategoriChart" class="chart-container"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Tren Pengajuan Peminjaman (6 Bulan Terakhir)</h4>
                    </div>
                    <div class="card-body">
                        <div id="trenPeminjamanChart" class="chart-container"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Distribusi Status Unit Barang Aktif</h4>
                    </div>
                    <div class="card-body">
                        <div id="statusUnitBarangChart" class="chart-container"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Distribusi Kondisi Unit Barang Aktif</h4>
                    </div>
                    <div class="card-body">
                        <div id="kondisiUnitBarangChart" class="chart-container"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baris Daftar Ringkas --}}
        <div class="row mt-4">
            <div class="col-lg-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Peminjaman Terbaru Menunggu Persetujuan</h5>
                    </div>
                    <div class="card-body p-0">
                        @if ($peminjamanTerbaruMenunggu->isEmpty())
                            <p class="text-muted p-3">Tidak ada peminjaman yang menunggu persetujuan.</p>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach ($peminjamanTerbaruMenunggu as $p)
                                    <li
                                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div>
                                            <a href="{{ route('admin.peminjaman.show', $p->id) }}" class="fw-medium">
                                                PMJ-{{ str_pad($p->id, 5, '0', STR_PAD_LEFT) }}
                                            </a>
                                            <small class="d-block text-muted">
                                                Oleh: {{ $p->guru->username ?? 'N/A' }} |
                                                {{ $p->detailPeminjaman->count() }} item
                                                <br>Tujuan: {{ Str::limit($p->tujuan_peminjaman, 50) }}
                                            </small>
                                        </div>
                                        <span
                                            class="text-muted font-size-12">{{ $p->tanggal_pengajuan->diffForHumans() }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            @if ($peminjamanMenunggu > 5)
                                <div class="text-center p-2 border-top">
                                    <a href="{{ route('admin.peminjaman.index', ['status' => App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN]) }}"
                                        class="text-primary">Lihat Semua <i class="mdi mdi-arrow-right"></i></a>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Laporan Pemeliharaan Terbaru Diajukan</h5>
                    </div>
                    <div class="card-body p-0">
                        @if ($pemeliharaanTerbaruDiajukan->isEmpty())
                            <p class="text-muted p-3">Tidak ada laporan pemeliharaan yang menunggu persetujuan.</p>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach ($pemeliharaanTerbaruDiajukan as $pm)
                                    <li
                                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div>
                                            <a href="{{ route('admin.pemeliharaan.show', $pm->id) }}" class="fw-medium">
                                                #PEM{{ str_pad($pm->id, 4, '0', STR_PAD_LEFT) }} -
                                                {{ Str::limit($pm->catatan_pengajuan, 30) }}
                                            </a>
                                            <small class="d-block text-muted">
                                                Unit: {{ $pm->barangQrCode->barang->nama_barang ?? 'N/A' }}
                                                ({{ $pm->barangQrCode->kode_inventaris_sekolah ?? 'N/A' }})
                                                <br>Pelapor: {{ $pm->pengaju->username ?? 'N/A' }}
                                            </small>
                                        </div>
                                        <span
                                            class="text-muted font-size-12">{{ $pm->tanggal_pengajuan->diffForHumans() }}</span>
                                    </li>
                                @endforeach
                            </ul>
                            @if ($pemeliharaanMenungguPersetujuan > 5)
                                <div class="text-center p-2 border-top">
                                    <a href="{{ route('admin.pemeliharaan.index', ['status_pemeliharaan' => App\Models\Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN]) }}"
                                        class="text-primary">Lihat Semua <i class="mdi mdi-arrow-right"></i></a>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Log Aktivitas Sistem Terbaru</h5>
                    </div>
                    <div class="card-body p-0">
                        @if ($logAktivitasTerbaru->isEmpty())
                            <p class="text-muted p-3">Belum ada aktivitas tercatat.</p>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach ($logAktivitasTerbaru as $log)
                                    <li class="list-group-item">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <i class="mdi mdi-circle-medium text-primary align-middle me-2"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <span class="fw-medium">{{ $log->aktivitas }}</span>
                                                <span class="text-muted">- oleh
                                                    {{ $log->user->username ?? 'Sistem' }}</span>
                                                <small class="d-block text-muted">{{ $log->deskripsi }}</small>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <span
                                                    class="font-size-12 text-muted">{{ $log->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                            @if (App\Models\LogAktivitas::count() > 7)
                                <div class="text-center p-2 border-top">
                                    <a href="{{ route('admin.log-aktivitas.index') }}" class="text-primary">Lihat Semua
                                        Log Aktivitas <i class="mdi mdi-arrow-right"></i></a>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    {{-- Load ApexCharts JS --}}
    <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script> {{-- Sesuaikan path jika berbeda --}}

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Grafik Barang per Kategori (Pie Chart)
            var barangPerKategoriData = @json($barangPerKategori);
            if (barangPerKategoriData && barangPerKategoriData.length > 0) {
                var kategoriLabels = barangPerKategoriData.map(item => item.nama_kategori);
                var kategoriCounts = barangPerKategoriData.map(item => item.jumlah_barang_aktif);

                var optionsBarangPerKategori = {
                    chart: {
                        type: 'pie',
                        height: 320,
                        toolbar: {
                            show: true
                        }
                    },
                    series: kategoriCounts,
                    labels: kategoriLabels,
                    colors: ["#5156be", "#2ab57d", "#fd625e", "#4ba6ef", "#ffbf53"], // Sesuaikan warna
                    legend: {
                        position: 'bottom'
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                width: 200
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                };
                var chartBarangPerKategori = new ApexCharts(document.querySelector("#barangPerKategoriChart"),
                    optionsBarangPerKategori);
                chartBarangPerKategori.render();
            } else {
                document.querySelector("#barangPerKategoriChart").innerHTML =
                    '<p class="text-center text-muted py-5">Tidak ada data barang per kategori untuk ditampilkan.</p>';
            }


            // 2. Grafik Tren Peminjaman (Line Chart)
            var trenPeminjamanData = @json($trenPeminjaman);
            if (trenPeminjamanData && trenPeminjamanData.length > 0) {
                var trenLabels = trenPeminjamanData.map(item => item.bulan + ' ' + item.tahun);
                var trenCounts = trenPeminjamanData.map(item => item.jumlah);

                var optionsTrenPeminjaman = {
                    chart: {
                        type: 'line',
                        height: 320,
                        toolbar: {
                            show: true
                        }
                    },
                    series: [{
                        name: 'Jumlah Peminjaman',
                        data: trenCounts
                    }],
                    xaxis: {
                        categories: trenLabels,
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 2
                    },
                    colors: ["#5156be"],
                    markers: {
                        size: 4
                    },
                    tooltip: {
                        x: {
                            format: 'MMMM yyyy'
                        },
                    }
                };
                var chartTrenPeminjaman = new ApexCharts(document.querySelector("#trenPeminjamanChart"),
                    optionsTrenPeminjaman);
                chartTrenPeminjaman.render();
            } else {
                document.querySelector("#trenPeminjamanChart").innerHTML =
                    '<p class="text-center text-muted py-5">Tidak ada data tren peminjaman untuk ditampilkan.</p>';
            }


            // 3. Grafik Status Unit Barang (Donut Chart)
            var statusUnitBarangData = @json($statusUnitBarang);
            var statusUnitLabels = Object.keys(statusUnitBarangData);
            var statusUnitCounts = Object.values(statusUnitBarangData);

            if (statusUnitLabels.length > 0) {
                var optionsStatusUnit = {
                    chart: {
                        type: 'donut',
                        height: 320,
                        toolbar: {
                            show: true
                        }
                    },
                    series: statusUnitCounts,
                    labels: statusUnitLabels,
                    colors: ["#2ab57d", "#5156be", "#ffbf53", "#fd625e",
                        "#4ba6ef"
                    ], // Tersedia, Dipinjam, Dalam Pemeliharaan, (lainnya)
                    legend: {
                        position: 'bottom'
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                labels: {
                                    show: true,
                                    name: {
                                        show: true
                                    },
                                    value: {
                                        show: true
                                    },
                                    total: {
                                        show: true,
                                        label: 'Total Unit',
                                        formatter: function(w) {
                                            return w.globals.seriesTotals.reduce((a, b) => {
                                                return a + b
                                            }, 0)
                                        }
                                    }
                                }
                            }
                        }
                    }
                };
                var chartStatusUnit = new ApexCharts(document.querySelector("#statusUnitBarangChart"),
                    optionsStatusUnit);
                chartStatusUnit.render();
            } else {
                document.querySelector("#statusUnitBarangChart").innerHTML =
                    '<p class="text-center text-muted py-5">Tidak ada data status unit barang untuk ditampilkan.</p>';
            }

            // 4. Grafik Kondisi Unit Barang (Bar Chart)
            var kondisiUnitBarangData = @json($kondisiUnitBarang);
            var kondisiUnitLabels = Object.keys(kondisiUnitBarangData);
            var kondisiUnitCounts = Object.values(kondisiUnitBarangData);

            if (kondisiUnitLabels.length > 0) {
                var optionsKondisiUnit = {
                    chart: {
                        type: 'bar',
                        height: 320,
                        toolbar: {
                            show: true
                        }
                    },
                    series: [{
                        name: 'Jumlah Unit',
                        data: kondisiUnitCounts
                    }],
                    xaxis: {
                        categories: kondisiUnitLabels,
                    },
                    colors: ["#2ab57d", "#ffbf53", "#fd625e",
                        "#5156be"
                    ], // Baik, Kurang Baik, Rusak Berat, Hilang
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '55%',
                            endingShape: 'rounded'
                        },
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        show: true,
                        width: 2,
                        colors: ['transparent']
                    },
                    yaxis: {
                        title: {
                            text: 'Jumlah Unit'
                        },
                        labels: {
                            formatter: function(val) {
                                return parseInt(val); // Pastikan bilangan bulat
                            }
                        }
                    },
                    fill: {
                        opacity: 1
                    },
                    tooltip: {
                        y: {
                            formatter: function(val) {
                                return val + " unit"
                            }
                        }
                    }
                };
                var chartKondisiUnit = new ApexCharts(document.querySelector("#kondisiUnitBarangChart"),
                    optionsKondisiUnit);
                chartKondisiUnit.render();
            } else {
                document.querySelector("#kondisiUnitBarangChart").innerHTML =
                    '<p class="text-center text-muted py-5">Tidak ada data kondisi unit barang untuk ditampilkan.</p>';
            }


        });
    </script>
@endpush
