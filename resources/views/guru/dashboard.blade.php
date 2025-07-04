@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <style>
        .card-h-100 {
            height: calc(100% - 1.5rem);
        }

        .quick-action-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }

        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, .15);
        }

        .quick-action-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .list-group-item-action:hover {
            background-color: #f8f9fa;
        }
    </style>
@endpush

@php
    $rolePrefix = Auth::user()->getRolePrefix();
    use Carbon\Carbon; // Pastikan Carbon diimpor
@endphp

@section('content')
    <div class="container-fluid">
        {{-- Page Title & Welcome Message --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Dashboard</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route($rolePrefix . 'dashboard') }}">SIMA</a></li>
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-primary" role="alert">
            <h4 class="alert-heading">Selamat Datang, {{ Auth::user()->username }}!</h4>
            <p>Ini adalah halaman utama Anda. Di sini Anda dapat membuat pengajuan peminjaman barang dan melaporkan
                kerusakan aset yang Anda gunakan.</p>
        </div>

        {{-- Kartu Statistik dan Aksi Cepat --}}
        <div class="row">
            <div class="col-lg-8">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="font-size-15 text-muted">Peminjaman Aktif Saya</h6>
                                        <h4 class="mt-2 text-primary">{{ $peminjamanAktif ?? 0 }} <small
                                                class="text-muted">Transaksi</small></h4>
                                    </div>
                                    <div class="avatar-sm"><span class="avatar-title bg-light text-primary rounded-3"><i
                                                class="fas fa-people-carry font-size-24"></i></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="font-size-15 text-muted">Pengajuan Menunggu</h6>
                                        <h4 class="mt-2 text-warning">{{ $pengajuanMenunggu ?? 0 }} <small
                                                class="text-muted">Pengajuan</small></h4>
                                    </div>
                                    <div class="avatar-sm"><span class="avatar-title bg-light text-warning rounded-3"><i
                                                class="fas fa-hourglass-half font-size-24"></i></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- TAMBAHAN: Kartu Peminjaman Terlambat Guru --}}
                    <div class="col-md-6">
                        <div class="card card-h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="font-size-15 text-muted">Peminjaman Terlambat Saya</h6>
                                        <h4 class="mt-2 text-danger">{{ $jumlahPeminjamanTerlambatGuru ?? 0 }} <small
                                                class="text-muted">Transaksi</small></h4>
                                    </div>
                                    <div class="avatar-sm"><span class="avatar-title bg-light text-danger rounded-3"><i
                                                class="fas fa-exclamation-triangle font-size-24"></i></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-user-check me-2"></i>Daftar Aset Dipegang Personal</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @forelse ($asetDipegangPersonal as $aset)
                                <a href="{{ route('guru.barang-qr-code.show', $aset->id) }}"
                                    class="list-group-item list-group-item-action">
                                    <div class="d-flex align-items-center">
                                        {{-- Informasi Aset --}}
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">{{ optional($aset->barang)->nama_barang ?? 'N/A' }}</div>
                                            <small class="text-muted">
                                                Kode: <code>{{ $aset->kode_inventaris_sekolah }}</code>
                                                @if ($aset->no_seri_pabrik)
                                                    • SN: {{ $aset->no_seri_pabrik }}
                                                @endif
                                            </small>
                                        </div>

                                        {{-- Status Kondisi dan Aksi --}}
                                        <div class="flex-shrink-0" style="width: 200px; text-align: right;">
                                            <span
                                                class="badge {{ \App\Models\BarangQrCode::getKondisiColor($aset->kondisi) }}">
                                                Kondisi: {{ $aset->kondisi }}
                                            </span>
                                            {{-- Cek apakah ada record pemeliharaan yang aktif untuk aset ini --}}
                                            @php
                                                $pemeliharaanAktif = $aset->pemeliharaanRecords->first(function ($rec) {
                                                    return in_array($rec->status_pengajuan, [
                                                        \App\Models\Pemeliharaan::STATUS_PENGAJUAN_DIAJUKAN,
                                                        \App\Models\Pemeliharaan::STATUS_PENGAJUAN_DISETUJUI,
                                                    ]) &&
                                                        in_array($rec->status_pengerjaan, [
                                                            \App\Models\Pemeliharaan::STATUS_PENGERJAAN_BELUM_DIKERJAKAN,
                                                            \App\Models\Pemeliharaan::STATUS_PENGERJAAN_SEDANG_DILAKUKAN,
                                                        ]) &&
                                                        is_null($rec->deleted_at);
                                                });
                                            @endphp
                                            @if ($pemeliharaanAktif)
                                                <a href="{{ route($rolePrefix . 'pemeliharaan.show', $pemeliharaanAktif->id) }}"
                                                    class="btn btn-info btn-sm mt-1" data-bs-toggle="tooltip"
                                                    title="Lihat detail laporan">
                                                    <i class="fas fa-info-circle me-1"></i> Dlm. Proses Lapor
                                                </a>
                                            @else
                                                <a href="{{ route($rolePrefix . 'pemeliharaan.create', ['id_barang_qr_code' => $aset->id]) }}"
                                                    class="btn btn-warning btn-sm mt-1" data-bs-toggle="tooltip"
                                                    title="Laporkan kerusakan untuk aset ini">
                                                    <i class="fas fa-tools me-1"></i> Laporkan Kerusakan
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="list-group-item">
                                    <p class="text-center text-muted my-3">Anda tidak sedang memegang aset personal saat
                                        ini.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Aksi Cepat --}}
        <div class="row mt-3">
            <div class="col-md-6">
                <a href="{{ route('guru.peminjaman.create') }}"
                    class="card card-h-100 text-decoration-none quick-action-card">
                    <div class="card-body">
                        <i class="fas fa-share-square quick-action-icon text-primary"></i>
                        <h5 class="card-title">Buat Pengajuan Peminjaman</h5>
                        <p class="card-text text-muted">Ajukan peminjaman laptop, proyektor, atau aset lainnya untuk
                            keperluan mengajar.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6">
                <a href="{{ route('guru.pemeliharaan.create') }}"
                    class="card card-h-100 text-decoration-none quick-action-card">
                    <div class="card-body">
                        <i class="fas fa-tools quick-action-icon text-danger"></i>
                        <h5 class="card-title">Lapor Kerusakan Aset</h5>
                        <p class="card-text text-muted">Laporkan jika ada kerusakan pada aset yang Anda pinjam atau yang
                            menjadi tanggung jawab Anda.</p>
                    </div>
                </a>
            </div>
        </div>

        {{-- Daftar Aktivitas Peminjaman dan Pemeliharaan --}}
        <div class="row mt-3">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Riwayat 5 Pengajuan Peminjaman Terakhir Saya</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <tbody>
                                    @forelse ($peminjamanTerbaru as $p)
                                        <tr>
                                            <td>
                                                <a href="{{ route('guru.peminjaman.show', $p->id) }}"
                                                    class="fw-medium">Tujuan:
                                                    {{ Str::limit($p->tujuan_peminjaman, 50) }}</a>
                                                <small class="d-block text-muted">{{ $p->detail_peminjaman_count }} item •
                                                    Diajukan {{ $p->tanggal_pengajuan->diffForHumans() }}</small>
                                            </td>
                                            <td class="text-end"><span
                                                    class="badge {{ \App\Models\Peminjaman::statusColor($p->status) }}">{{ $p->status }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="text-center text-muted py-3" colspan="2">Anda belum pernah
                                                membuat pengajuan
                                                peminjaman.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAMBAHAN: Daftar Peminjaman Terlambat Saya --}}
            <div class="col-lg-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Peminjaman Terlambat Saya</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse ($peminjamanTerlambatGuru as $p)
                                <a href="{{ route('guru.peminjaman.show', $p->id) }}"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center bg-danger text-white">
                                    <div>
                                        <div class="fw-medium">Tujuan: {{ Str::limit($p->tujuan_peminjaman, 35) }}</div>
                                        <small class="text-white">Status: {{ $p->status }} |
                                            {{ $p->jumlah_hari_terlambat }} Hari Terlambat</small>
                                    </div><span
                                        class="text-white font-size-12">{{ Carbon::parse($p->tanggal_harus_kembali)->isoFormat('D MMM') }}</span>
                                </a>
                            @empty
                                <li class="list-group-item text-muted text-center py-3">Tidak ada peminjaman terlambat.
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Riwayat 5 Laporan Pemeliharaan Terakhir Saya</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <tbody>
                                    @forelse ($pemeliharaanTerbaru as $pm)
                                        <tr>
                                            <td>
                                                <a href="{{ route('guru.pemeliharaan.show', $pm->id) }}"
                                                    class="fw-medium">Aset:
                                                    {{ Str::limit(optional(optional($pm->barangQrCode)->barang)->nama_barang, 40) }}</a>
                                                <small class="d-block text-muted">
                                                    Status: {{ $pm->status_pengajuan }} • Diajukan
                                                    {{ $pm->tanggal_pengajuan->diffForHumans() }}
                                                </small>
                                            </td>
                                            <td class="text-end">
                                                <span
                                                    class="badge bg-{{ $pm->prioritas == 'tinggi' ? 'danger' : 'warning text-dark' }}">{{ $pm->prioritas }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="text-center text-muted py-3" colspan="2">Anda belum pernah
                                                melaporkan kerusakan aset.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
