@extends('layouts.app')

@section('title', 'Dashboard Admin')

@push('styles')
    <style>
        .card-h-100 {
            height: calc(100% - 1.5rem);
        }

        .dashboard-stat-icon {
            font-size: 2.5rem;
            opacity: 0.2;
        }

        .list-group-item-action:hover {
            background-color: #f8f9fa;
        }

        .stat-card-title {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .stat-card-number {
            font-size: 2.1rem;
        }

        .chart-container {
            position: relative;
            height: 320px;
            width: 100%;
        }
    </style>
@endpush

@php
    $rolePrefix = Auth::user()->getRolePrefix();
@endphp

@section('content')
    <div class="container-fluid">
        {{-- Page Title & Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Dashboard Admin</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route($rolePrefix . 'dashboard') }}">SIMA</a></li>
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baris Statistik Utama --}}
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="stat-card-title mb-2">Total Unit Aset</p>
                                <h3 class="mb-0 text-white">{{ number_format($jumlahUnitBarang ?? 0) }}</h3>
                            </div>
                            <div class="flex-shrink-0"><i class="fas fa-boxes dashboard-stat-icon"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="stat-card-title mb-2">Total Nilai Aset</p>
                                <h3 class="mb-0 text-white">Rp {{ number_format($totalNilaiAset ?? 0, 0, ',', '.') }}</h3>
                            </div>
                            <div class="flex-shrink-0"><i class="fas fa-money-bill-wave dashboard-stat-icon"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="stat-card-title mb-2">Jenis Barang</p>
                                <h3 class="mb-0 text-white">{{ number_format($jumlahJenisBarang ?? 0) }}</h3>
                            </div>
                            <div class="flex-shrink-0"><i class="fas fa-archive dashboard-stat-icon"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-light text-dark">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="stat-card-title text-muted mb-2">Total Pengguna</p>
                                <h3 class="mb-0">{{ number_format($jumlahUser ?? 0) }}</h3>
                            </div>
                            <div class="flex-shrink-0"><i class="fas fa-users dashboard-stat-icon text-primary"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baris Statistik Aktivitas --}}
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-size-15">Peminjaman Baru</h6>
                                <h4 class="mt-2 text-warning">{{ $peminjamanMenunggu ?? 0 }} <small
                                        class="text-muted">Pengajuan</small></h4>
                            </div>
                            <div class="avatar-sm"><span class="avatar-title bg-light text-warning rounded-3"><i
                                        class="fas fa-hourglass-half font-size-24"></i></span></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-size-15">Aset Dipinjam</h6>
                                <h4 class="mt-2 text-info">{{ $jumlahUnitDipinjam ?? 0 }} <small
                                        class="text-muted">Unit</small></h4>
                            </div>
                            <div class="avatar-sm"><span class="avatar-title bg-light text-info rounded-3"><i
                                        class="fas fa-people-carry font-size-24"></i></span></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-size-15">Laporan Kerusakan</h6>
                                <h4 class="mt-2 text-danger">{{ $pemeliharaanMenungguPersetujuan ?? 0 }} <small
                                        class="text-muted">Laporan</small></h4>
                            </div>
                            <div class="avatar-sm"><span class="avatar-title bg-light text-danger rounded-3"><i
                                        class="fas fa-tools font-size-24"></i></span></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-size-15">Dlm. Pemeliharaan</h6>
                                <h4 class="mt-2 text-secondary">{{ $jumlahUnitDalamPemeliharaan ?? 0 }} <small
                                        class="text-muted">Unit</small></h4>
                            </div>
                            <div class="avatar-sm"><span class="avatar-title bg-light text-secondary rounded-3"><i
                                        class="fas fa-cogs font-size-24"></i></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baris Grafik (Layout 2x2) --}}
        <div class="row">
            <div class="col-xl-7">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Aktivitas Peminjaman (6 Bulan Terakhir)</h4>
                    </div>
                    <div class="card-body">
                        <div id="trenPeminjamanChart" class="apex-charts" dir="ltr"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Distribusi Status Unit</h4>
                    </div>
                    <div class="card-body">
                        <div id="statusUnitBarangChart" class="apex-charts" dir="ltr"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-5">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Aset per Kategori (Top 5)</h4>
                    </div>
                    <div class="card-body">
                        <div id="barangPerKategoriChart" class="apex-charts" dir="ltr"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-7">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Distribusi Kondisi Unit</h4>
                    </div>
                    <div class="card-body">
                        <div id="kondisiUnitBarangChart" class="apex-charts" dir="ltr"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baris Daftar Aktivitas & Log --}}
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tugas: Persetujuan Peminjaman</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse ($peminjamanTerbaruMenunggu as $p)
                                <a href="{{ route('admin.peminjaman.show', $p->id) }}"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-medium">Tujuan: {{ Str::limit($p->tujuan_peminjaman, 35) }}</div>
                                        <small class="text-muted">Oleh: {{ $p->guru->username ?? 'N/A' }} |
                                            {{ $p->detailPeminjaman->count() }} item</small>
                                    </div><span
                                        class="text-muted font-size-12">{{ $p->tanggal_pengajuan->diffForHumans() }}</span>
                                </a>
                            @empty
                                <li class="list-group-item text-muted text-center py-3">Tidak ada pengajuan untuk diproses.
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tugas: Persetujuan Pemeliharaan</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse ($pemeliharaanTerbaruDiajukan as $pm)
                                <a href="{{ route('admin.pemeliharaan.show', $pm->id) }}"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-medium">Aset:
                                            {{ Str::limit(optional(optional($pm->barangQrCode)->barang)->nama_barang, 35) }}
                                        </div><small class="text-muted">Pelapor:
                                            {{ $pm->pengaju->username ?? 'N/A' }}</small>
                                    </div><span
                                        class="badge bg-{{ $pm->prioritas == 'tinggi' ? 'danger' : 'warning text-dark' }}">{{ $pm->prioritas }}</span>
                                </a>
                            @empty
                                <li class="list-group-item text-muted text-center py-3">Tidak ada laporan kerusakan baru.
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-2">
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
            // Data dari Controller
            const trenPeminjamanData = @json($trenPeminjaman ?? []);
            const kondisiUnitBarangData = @json($kondisiUnitBarang ?? []);
            const statusUnitBarangData = @json($statusUnitBarang ?? []);
            const barangPerKategoriData = @json($barangPerKategori ?? []);
            const defaultChartHeight = 330;

            // Grafik 1: Tren Peminjaman
            if (document.querySelector("#trenPeminjamanChart") && trenPeminjamanData.length > 0) {
                new ApexCharts(document.querySelector("#trenPeminjamanChart"), {
                    series: [{
                        name: 'Jml. Pengajuan',
                        data: trenPeminjamanData.map(item => item.jumlah)
                    }],
                    chart: {
                        height: defaultChartHeight,
                        type: 'area',
                        toolbar: {
                            show: false
                        },
                        zoom: {
                            enabled: false
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth',
                        width: 3
                    },
                    colors: ['#556ee6'],
                    xaxis: {
                        categories: trenPeminjamanData.map(item => item.bulan),
                        labels: {
                            style: {
                                colors: '#495057', // Warna teks lebih gelap
                                fontSize: '12px'
                            }
                        }
                    },
                    yaxis: {
                        labels: {
                            formatter: (val) => parseInt(val),
                            style: {
                                colors: '#495057', // Warna teks lebih gelap
                                fontSize: '12px'
                            }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: (val) => val + " pengajuan"
                        },
                        style: {
                            fontSize: '14px'
                        }
                    },
                    grid: {
                        borderColor: '#f1f1f1' // Garis grid lebih terang
                    }
                }).render();
            } else {
                document.querySelector("#trenPeminjamanChart").innerHTML =
                    '<div class="d-flex align-items-center justify-content-center h-100 text-muted p-4">Tidak ada data tren peminjaman.</div>';
            }

            // Grafik 2: Aset per Kategori
            if (document.querySelector("#barangPerKategoriChart") && barangPerKategoriData.length > 0) {
                new ApexCharts(document.querySelector("#barangPerKategoriChart"), {
                    series: [{
                        name: 'Jml. Jenis Barang',
                        data: barangPerKategoriData.map(item => item.jumlah_jenis_barang)
                    }],
                    chart: {
                        type: 'bar',
                        height: defaultChartHeight,
                        toolbar: {
                            show: false
                        }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 4,
                            horizontal: true,
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        textAnchor: 'start',
                        style: {
                            colors: ['#fff'],
                            fontSize: '12px',
                            fontWeight: 'bold'
                        },
                        offsetX: 10,
                        formatter: (val, opt) => opt.w.globals.labels[opt.dataPointIndex]
                    },
                    xaxis: {
                        categories: barangPerKategoriData.map(item => item.nama_kategori),
                        labels: {
                            show: false
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: '#495057', // Warna teks lebih gelap
                                fontSize: '12px'
                            }
                        }
                    },
                    colors: ['#34c38f', '#50a5f1', '#f1b44c', '#f46a6a', '#556ee6'],
                    tooltip: {
                        y: {
                            title: {
                                formatter: seriesName => 'Jumlah Jenis Barang'
                            }
                        },
                        style: {
                            fontSize: '14px'
                        }
                    },
                    grid: {
                        borderColor: '#f1f1f1' // Garis grid lebih terang
                    }
                }).render();
            } else {
                document.querySelector("#barangPerKategoriChart").innerHTML =
                    '<div class="d-flex align-items-center justify-content-center h-100 text-muted p-4">Tidak ada data aset per kategori.</div>';
            }

            // Grafik 3: Distribusi Status Unit
            if (document.querySelector("#statusUnitBarangChart") && Object.keys(statusUnitBarangData).length > 0) {
                new ApexCharts(document.querySelector("#statusUnitBarangChart"), {
                    series: Object.values(statusUnitBarangData),
                    chart: {
                        height: defaultChartHeight,
                        type: 'donut'
                    },
                    labels: Object.keys(statusUnitBarangData),
                    colors: Object.keys(statusUnitBarangData).map(label => ({
                        'Tersedia': '#34c38f',
                        'Dipinjam': '#50a5f1',
                        'Dalam Pemeliharaan': '#f1b44c'
                    })[label] || '#74788d'),
                    legend: {
                        position: 'bottom',
                        labels: {
                            colors: '#495057', // Warna teks legenda lebih gelap
                            useSeriesColors: false
                        }
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                labels: {
                                    show: true,
                                    name: {
                                        fontSize: '14px',
                                        color: '#495057' // Warna teks lebih gelap
                                    },
                                    value: {
                                        fontSize: '16px',
                                        fontWeight: 'bold',
                                        color: '#212529' // Warna teks lebih gelap
                                    },
                                    total: {
                                        show: true,
                                        label: 'Total Unit',
                                        color: '#495057', // Warna teks lebih gelap
                                        fontSize: '14px',
                                        formatter: function(w) {
                                            return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                        }
                                    }
                                }
                            }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontSize: '14px',
                            fontWeight: 'bold',
                            colors: ['#fff']
                        },
                        dropShadow: {
                            enabled: true
                        }
                    }
                }).render();
            } else {
                document.querySelector("#statusUnitBarangChart").innerHTML =
                    '<div class="d-flex align-items-center justify-content-center h-100 text-muted p-4">Tidak ada data status aset.</div>';
            }

            // Grafik 4: Distribusi Kondisi Unit
            if (document.querySelector("#kondisiUnitBarangChart") && Object.keys(kondisiUnitBarangData).length >
                0) {
                new ApexCharts(document.querySelector("#kondisiUnitBarangChart"), {
                    series: Object.values(kondisiUnitBarangData),
                    chart: {
                        height: defaultChartHeight,
                        type: 'pie'
                    },
                    labels: Object.keys(kondisiUnitBarangData),
                    colors: Object.keys(kondisiUnitBarangData).map(label => ({
                        'Baik': '#34c38f',
                        'Kurang Baik': '#f1b44c',
                        'Rusak Berat': '#f46a6a',
                        'Hilang': '#343a40'
                    })[label] || '#74788d'),
                    legend: {
                        position: 'bottom',
                        labels: {
                            colors: '#495057', // Warna teks legenda lebih gelap
                            useSeriesColors: false
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontSize: '14px',
                            fontWeight: 'bold',
                            colors: ['#fff']
                        },
                        dropShadow: {
                            enabled: true
                        }
                    },
                    tooltip: {
                        style: {
                            fontSize: '14px'
                        }
                    }
                }).render();
            } else {
                document.querySelector("#kondisiUnitBarangChart").innerHTML =
                    '<div class="d-flex align-items-center justify-content-center h-100 text-muted p-4">Tidak ada data kondisi aset.</div>';
            }
        });
    </script>
@endpush
