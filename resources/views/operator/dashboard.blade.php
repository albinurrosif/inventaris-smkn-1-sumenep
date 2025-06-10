@extends('layouts.app') {{-- Menggunakan layout yang sama --}}

@section('title', 'Dashboard Operator')

@push('styles')
    {{-- CSS yang sama dengan dashboard admin untuk konsistensi --}}
    <style>
        .card-h-100 {
            height: calc(100% - 1.5rem);
        }

        .dashboard-stat-icon {
            font-size: 2.5rem;
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
            width: 100%;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Dashboard Operator</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">SIMA</a></li>
                            <li class="breadcrumb-item active">Dashboard Operator</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baris Statistik Khusus Operator --}}
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100 bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-white-50 mb-2">Jumlah Ruangan Dikelola</p>
                                <h4 class="mb-0 text-white">{{ $jumlahRuanganDikelola ?? 0 }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="mdi mdi-door-open dashboard-stat-icon"></i>
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
                                <p class="text-uppercase fw-medium text-white-50 mb-2">Total Unit di Ruangan Anda</p>
                                <h4 class="mb-0 text-white">{{ $jumlahUnitDiRuangan ?? 0 }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="mdi mdi-cube-outline dashboard-stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100 bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-dark-50 mb-2">Peminjaman Perlu Diproses</p>
                                <h4 class="mb-0">{{ $peminjamanPerluDiproses ?? 0 }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="mdi mdi-file-document-edit-outline dashboard-stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card card-h-100 bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-uppercase fw-medium text-white-50 mb-2">Laporan Perbaikan Baru</p>
                                <h4 class="mb-0 text-white">{{ $pemeliharaanBaru ?? 0 }}</h4>
                            </div>
                            <div class="flex-shrink-0">
                                <i class="mdi mdi-tools dashboard-stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baris Grafik Khusus Operator --}}
        <div class="row mt-4">
            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Kondisi Unit di Ruangan Anda</h4>
                    </div>
                    <div class="card-body">
                        <div id="kondisiUnitOperatorChart" class="chart-container"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Status Unit di Ruangan Anda</h4>
                    </div>
                    <div class="card-body">
                        <div id="statusUnitOperatorChart" class="chart-container"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baris Daftar Tugas Operator --}}
        <div class="row mt-4">
            <div class="col-lg-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Pengajuan Peminjaman Baru</h5>
                    </div>
                    <div class="card-body p-0">
                        @if (empty($peminjamanTerbaru) || $peminjamanTerbaru->isEmpty())
                            <p class="text-muted p-3">Tidak ada pengajuan peminjaman baru untuk diproses.</p>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach ($peminjamanTerbaru as $p)
                                    <li
                                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div>
                                            {{-- Arahkan ke route operator --}}
                                            <a href="{{ route('operator.peminjaman.show', $p->id) }}" class="fw-medium">
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
                            @if ($peminjamanPerluDiproses > 5)
                                <div class="text-center p-2 border-top">
                                    {{-- Arahkan ke route operator --}}
                                    <a href="{{ route('operator.peminjaman.index', ['status' => App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN]) }}"
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
                        <h5 class="card-title mb-0">Laporan Pemeliharaan Baru</h5>
                    </div>
                    <div class="card-body p-0">
                        @if (empty($pemeliharaanTerbaru) || $pemeliharaanTerbaru->isEmpty())
                            <p class="text-muted p-3">Tidak ada laporan pemeliharaan baru.</p>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach ($pemeliharaanTerbaru as $pm)
                                    <li
                                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div>
                                            {{-- Arahkan ke route operator --}}
                                            <a href="{{ route('operator.pemeliharaan.show', $pm->id) }}" class="fw-medium">
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
                            @if ($pemeliharaanBaru > 5)
                                <div class="text-center p-2 border-top">
                                    {{-- Arahkan ke route operator --}}
                                    <a href="{{ route('operator.pemeliharaan.index', ['status_pemeliharaan' => App\Models\Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN]) }}"
                                        class="text-primary">Lihat Semua <i class="mdi mdi-arrow-right"></i></a>
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
    <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Grafik Kondisi Unit Barang (Bar Chart) - Scoped for Operator
            var kondisiUnitOperatorData = @json($kondisiUnitBarangOperator ?? []);
            var kondisiUnitLabels = Object.keys(kondisiUnitOperatorData);
            var kondisiUnitCounts = Object.values(kondisiUnitOperatorData);

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
                        categories: kondisiUnitLabels
                    },
                    colors: ["#2ab57d", "#ffbf53", "#fd625e", "#5156be"],
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '55%',
                            borderRadius: 5
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    yaxis: {
                        title: {
                            text: 'Jumlah Unit'
                        },
                        labels: {
                            formatter: val => parseInt(val)
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: val => val + " unit"
                        }
                    }
                };
                var chartKondisiUnit = new ApexCharts(document.querySelector("#kondisiUnitOperatorChart"),
                    optionsKondisiUnit);
                chartKondisiUnit.render();
            } else {
                document.querySelector("#kondisiUnitOperatorChart").innerHTML =
                    '<p class="text-center text-muted py-5">Tidak ada data kondisi unit untuk ditampilkan.</p>';
            }

            // Grafik Status Unit Barang (Donut Chart) - Scoped for Operator
            var statusUnitOperatorData = @json($statusUnitBarangOperator ?? []);
            var statusUnitLabels = Object.keys(statusUnitOperatorData);
            var statusUnitCounts = Object.values(statusUnitOperatorData);

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
                    colors: ["#2ab57d", "#5156be", "#ffbf53", "#fd625e", "#4ba6ef"],
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
                };
                var chartStatusUnit = new ApexCharts(document.querySelector("#statusUnitOperatorChart"),
                    optionsStatusUnit);
                chartStatusUnit.render();
            } else {
                document.querySelector("#statusUnitOperatorChart").innerHTML =
                    '<p class="text-center text-muted py-5">Tidak ada data status unit untuk ditampilkan.</p>';
            }
        });
    </script>
@endpush
