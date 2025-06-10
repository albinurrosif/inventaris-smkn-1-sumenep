@extends('layouts.app')

@section('title', 'Dashboard Operator')

@push('styles')
    {{-- Menggunakan style yang sama dengan dashboard admin untuk konsistensi --}}
    <style>
        /* Unified Dashboard Styles */
        .card-h-100 {
            height: calc(100% - 1.5rem);
        }

        .dashboard-stat-icon {
            font-size: 2.5rem;
            opacity: 0.3;
        }

        .list-group-item-action:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }

        .stat-card-title {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .stat-card-number {
            font-size: 2.1rem;
            font-weight: 600;
        }

        .chart-container {
            position: relative;
            height: 320px;
            width: 100%;
        }

        .apexcharts-legend-text {
            font-size: 13px !important;
        }

        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, .05);
            padding: 1rem 1.25rem;
        }

        .card-title {
            font-weight: 600;
            margin-bottom: 0;
        }

        /* Status Badges */
        .badge-priority {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.35em 0.65em;
        }

        /* Activity List Items */
        .activity-item {
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .activity-item:hover {
            border-left-color: #556ee6;
            background-color: #f8f9fa;
        }

        /* Chart Cards */
        .chart-card {
            border: none;
            box-shadow: 0 0.75rem 1.5rem rgba(18, 38, 63, .03);
        }

        .chart-card .card-header {
            background-color: transparent;
            border-bottom: none;
            padding-bottom: 0;
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
                    <h4 class="mb-sm-0">Dashboard</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route($rolePrefix . 'dashboard') }}">SIMA</a></li>
                            <li class="breadcrumb-item active">Dashboard Operator</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baris Statistik Khusus Operator --}}
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="stat-card-title mb-2 text-white-50">Ruangan Dikelola</p>
                                <h3 class="mb-0 text-white">{{ number_format($jumlahRuanganDikelola ?? 0) }}</h3>
                            </div>
                            <div class="flex-shrink-0"><i class="fas fa-door-open dashboard-stat-icon"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="stat-card-title mb-2 text-white-50">Total Unit di Ruangan</p>
                                <h3 class="mb-0 text-white">{{ number_format($jumlahUnitDiRuangan ?? 0) }}</h3>
                            </div>
                            <div class="flex-shrink-0"><i class="fas fa-boxes dashboard-stat-icon"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="stat-card-title mb-2 text-dark-50">Peminjaman Diproses</p>
                                <h3 class="mb-0">{{ number_format($peminjamanPerluDiproses ?? 0) }}</h3>
                            </div>
                            <div class="flex-shrink-0"><i class="fas fa-file-signature dashboard-stat-icon"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="stat-card-title mb-2 text-white-50">Laporan Kerusakan</p>
                                <h3 class="mb-0 text-white">{{ number_format($pemeliharaanBaru ?? 0) }}</h3>
                            </div>
                            <div class="flex-shrink-0"><i class="fas fa-tools dashboard-stat-icon"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================== --}}
        {{-- == TAMBAHAN: Baris Statistik Aktivitas untuk Operator == --}}
        {{-- ========================================================== --}}
        <div class="row">
            <div class="col-xl-4 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-size-15">Aset Dipinjam</h6>
                                <h4 class="mt-2 text-info">{{ $jumlahUnitDipinjamDiRuangan ?? 0 }} <small
                                        class="text-muted">Unit</small></h4>
                            </div>
                            <div class="avatar-sm"><span class="avatar-title bg-light text-info rounded-3"><i
                                        class="fas fa-people-carry font-size-24"></i></span></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-size-15">Dlm. Pemeliharaan</h6>
                                <h4 class="mt-2 text-secondary">{{ $jumlahUnitDalamPemeliharaanDiRuangan ?? 0 }} <small
                                        class="text-muted">Unit</small></h4>
                            </div>
                            <div class="avatar-sm"><span class="avatar-title bg-light text-secondary rounded-3"><i
                                        class="fas fa-cogs font-size-24"></i></span></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card card-h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-size-15">Tugas Stok Opname</h6>
                                <h4 class="mt-2 text-primary">{{ $tugasStokOpnameBerjalan ?? 0 }} <small
                                        class="text-muted">Sesi</small></h4>
                            </div>
                            <div class="avatar-sm"><span class="avatar-title bg-light text-primary rounded-3"><i
                                        class="fas fa-clipboard-check font-size-24"></i></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baris Grafik Khusus Operator --}}
        <div class="row">
            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Kondisi Unit di Ruangan Anda</h4>
                    </div>
                    <div class="card-body">
                        <div id="kondisiUnitOperatorChart" class="chart-container apex-charts" dir="ltr"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Status Unit di Ruangan Anda</h4>
                    </div>
                    <div class="card-body">
                        <div id="statusUnitOperatorChart" class="chart-container apex-charts" dir="ltr"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baris Daftar Tugas Operator --}}
        <div class="row mt-3">
            <div class="col-lg-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tugas: Proses Peminjaman Baru</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse ($peminjamanTerbaru as $p)
                                <a href="{{ route('operator.peminjaman.show', $p->id) }}"
                                    class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-medium">Tujuan: {{ Str::limit($p->tujuan_peminjaman, 35) }}
                                            </div>
                                            <small class="text-muted">Oleh: {{ $p->guru->username ?? 'N/A' }} |
                                                {{ $p->detailPeminjaman->count() }} item</small>
                                        </div>
                                        <span
                                            class="text-muted font-size-12">{{ $p->tanggal_pengajuan->diffForHumans() }}</span>
                                    </div>
                                </a>
                            @empty
                                <li class="list-group-item text-muted text-center py-3">Tidak ada pengajuan peminjaman
                                    untuk
                                    diproses.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tugas: Laporan Pemeliharaan Baru</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse ($pemeliharaanTerbaru as $pm)
                                <a href="{{ route('operator.pemeliharaan.show', $pm->id) }}"
                                    class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-medium">Aset:
                                                {{ Str::limit(optional(optional($pm->barangQrCode)->barang)->nama_barang, 35) }}
                                            </div>
                                            <small class="text-muted">Pelapor:
                                                {{ $pm->pengaju->username ?? 'N/A' }}</small>
                                        </div>
                                        <span
                                            class="badge bg-{{ $pm->prioritas == 'tinggi' ? 'danger' : 'warning text-dark' }}">{{ $pm->prioritas }}</span>
                                    </div>
                                </a>
                            @empty
                                <li class="list-group-item text-muted text-center py-3">Tidak ada laporan kerusakan baru di
                                    lingkup Anda.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Load ApexCharts JS --}}
    <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const defaultChartHeight = 320;
            // Data dari Controller
            const kondisiData = @json($kondisiUnitBarangOperator ?? []);
            const statusData = @json($statusUnitBarangOperator ?? []);

            // Grafik 1: Kondisi Unit
            if (document.querySelector("#kondisiUnitOperatorChart") && Object.keys(kondisiData).length > 0) {
                const kondisiLabels = Object.keys(kondisiData);
                const kondisiValues = Object.values(kondisiData);
                const kondisiColors = kondisiLabels.map(label => ({
                    'Baik': '#34c38f',
                    'Kurang Baik': '#f1b44c',
                    'Rusak Berat': '#f46a6a',
                    'Hilang': '#343a40'
                })[label] || '#74788d');

                new ApexCharts(document.querySelector("#kondisiUnitOperatorChart"), {
                    series: [{
                        name: 'Jumlah Unit',
                        data: kondisiValues
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
                            horizontal: false,
                            columnWidth: '50%',
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    xaxis: {
                        categories: kondisiLabels
                    },
                    colors: kondisiColors,
                    yaxis: {
                        labels: {
                            formatter: (val) => parseInt(val)
                        }
                    }
                }).render();
            } else {
                document.querySelector("#kondisiUnitOperatorChart").innerHTML =
                    '<div class="d-flex align-items-center justify-content-center h-100 text-muted p-4">Tidak ada data kondisi aset di ruangan Anda.</div>';
            }

            // Grafik 2: Status Unit
            if (document.querySelector("#statusUnitOperatorChart") && Object.keys(statusData).length > 0) {
                new ApexCharts(document.querySelector("#statusUnitOperatorChart"), {
                    series: Object.values(statusData),
                    chart: {
                        height: defaultChartHeight,
                        type: 'donut'
                    },
                    labels: Object.keys(statusData),
                    colors: Object.keys(statusData).map(label => ({
                        'Tersedia': '#34c38f',
                        'Dipinjam': '#50a5f1',
                        'Dalam Pemeliharaan': '#f1b44c'
                    })[label] || '#74788d'),
                    legend: {
                        position: 'bottom'
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'Total Unit'
                                    }
                                }
                            }
                        }
                    }
                }).render();
            } else {
                document.querySelector("#statusUnitOperatorChart").innerHTML =
                    '<div class="d-flex align-items-center justify-content-center h-100 text-muted p-4">Tidak ada data status aset di ruangan Anda.</div>';
            }
        });
    </script>
@endpush
