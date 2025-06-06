{{-- File: resources/views/guru/peminjaman/show.blade.php --}}
@extends('layouts.app') {{-- Sesuaikan dengan layout guru Anda --}}

@section('title', 'Detail Pengajuan Peminjaman')

@push('styles')
    <style>
        .item-card {
            border-left-width: 5px;
        }

        .border-left-diajukan {
            border-left-color: #0dcaf0;
        }

        .border-left-disetujui {
            border-left-color: #0d6efd;
        }

        .border-left-diambil {
            border-left-color: #6f42c1;
        }

        .border-left-dikembalikan {
            border-left-color: #198754;
        }

        .border-left-rusak {
            border-left-color: #dc3545;
        }

        .border-left-hilang {
            border-left-color: #212529;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Detail Pengajuan: PMJ-{{ str_pad($peminjaman->id, 5, '0', STR_PAD_LEFT) }}</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('guru.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route('guru.peminjaman.index', request()->query()) }}">Peminjaman Saya</a>
                            </li>
                            <li class="breadcrumb-item active">Detail Pengajuan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-xl-4 col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informasi Pengajuan</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>ID Peminjaman:</strong> PMJ-{{ str_pad($peminjaman->id, 5, '0', STR_PAD_LEFT) }}</p>
                        <p><strong>Tujuan Peminjaman:</strong> {{ $peminjaman->tujuan_peminjaman }}</p>
                        <p><strong>Tanggal Pengajuan:</strong>
                            {{ $peminjaman->tanggal_pengajuan ? $peminjaman->tanggal_pengajuan->format('d M Y, H:i') : '-' }}
                        </p>
                        <p><strong>Rencana Pinjam:</strong>
                            {{ $peminjaman->tanggal_rencana_pinjam ? $peminjaman->tanggal_rencana_pinjam->format('d M Y') : '-' }}
                        </p>
                        <p><strong>Harus Kembali:</strong>
                            {{ $peminjaman->tanggal_harus_kembali ? $peminjaman->tanggal_harus_kembali->format('d M Y') : '-' }}
                        </p>
                        <p><strong>Status Peminjaman:</strong>
                            <span
                                class="badge bg-{{ App\Models\Peminjaman::statusColor($peminjaman->status) }}">{{ $peminjaman->status }}</span>
                            @if ($peminjaman->ada_item_terlambat && $peminjaman->status !== App\Models\Peminjaman::STATUS_SELESAI)
                                <span class="badge bg-danger ms-1">Terlambat</span>
                            @endif
                        </p>
                        <p><strong>Ruangan Tujuan Penggunaan:</strong>
                            {{ $peminjaman->ruanganTujuanPeminjaman->nama_ruangan ?? '-' }}</p>
                        <p><strong>Catatan Anda:</strong> {{ $peminjaman->catatan_peminjam ?: '-' }}</p>
                        @if ($peminjaman->disetujuiOlehUser)
                            <p><strong>Disetujui Oleh:</strong> {{ $peminjaman->disetujuiOlehUser->username }}
                                @if ($peminjaman->tanggal_disetujui)
                                    (pada {{ $peminjaman->tanggal_disetujui->format('d M Y, H:i') }})
                                @endif
                            </p>
                        @endif
                        @if ($peminjaman->ditolakOlehUser)
                            <p><strong>Ditolak Oleh:</strong> {{ $peminjaman->ditolakOlehUser->username }}
                                @if ($peminjaman->tanggal_ditolak)
                                    (pada {{ $peminjaman->tanggal_ditolak->format('d M Y, H:i') }})
                                @endif
                            </p>
                        @endif
                        @if ($peminjaman->catatan_operator)
                            <p><strong>Catatan Operator:</strong> {{ $peminjaman->catatan_operator }}</p>
                        @endif
                    </div>
                    <div class="card-footer text-end">
                        @can('update', $peminjaman)
                            @if ($peminjaman->status === App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN)
                                <a href="{{ route('guru.peminjaman.edit', $peminjaman->id) }}"
                                    class="btn btn-sm btn-warning me-1"><i data-feather="edit-2" class="me-1"></i>Edit
                                    Pengajuan</a>
                            @endif
                        @endcan
                        @can('cancelByUser', $peminjaman)
                            @if (in_array($peminjaman->status, [
                                    App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN,
                                    App\Models\Peminjaman::STATUS_DISETUJUI,
                                ]) &&
                                    !$peminjaman->detailPeminjaman()->where('status_unit', App\Models\DetailPeminjaman::STATUS_ITEM_DIAMBIL)->exists())
                                <button class="btn btn-sm btn-danger btn-cancel-by-user-guru"
                                    data-id="{{ $peminjaman->id }}"><i data-feather="slash" class="me-1"></i>Batalkan
                                    Pengajuan</button>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>

            <div class="col-xl-8 col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Item Barang Diajukan ({{ $peminjaman->detailPeminjaman->count() }}
                            item)</h5>
                    </div>
                    <div class="card-body">
                        @if ($peminjaman->detailPeminjaman->isEmpty())
                            <p class="text-center">Tidak ada item barang dalam pengajuan ini.</p>
                        @else
                            @foreach ($peminjaman->detailPeminjaman as $detail)
                                @php $barangQr = $detail->barangQrCode; @endphp
                                <div
                                    class="card mb-3 item-card 
                            @if ($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DIAJUKAN) border-left-diajukan
                            @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DISETUJUI) border-left-disetujui
                            @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DIAMBIL) border-left-diambil
                            @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DIKEMBALIKAN) border-left-dikembalikan
                            @elseif(in_array($detail->status_unit, [App\Models\DetailPeminjaman::STATUS_ITEM_RUSAK_SAAT_DIPINJAM])) border-left-rusak
                            @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_HILANG_SAAT_DIPINJAM) border-left-hilang @endif">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            {{ $barangQr->barang->nama_barang ?? 'N/A' }}
                                            <small
                                                class="text-muted">({{ $barangQr->kode_inventaris_sekolah ?? 'N/A' }})</small>
                                        </h6>
                                        <p class="card-text mb-1">
                                            <small>
                                                No. Seri Pabrik: {{ $barangQr->no_seri_pabrik ?: '-' }} <br>
                                                Lokasi Asal:
                                                {{ $barangQr->ruangan->nama_ruangan ?? ($barangQr->id_pemegang_personal ? 'Pemegang: ' . optional($barangQr->pemegangPersonal)->username : 'Tidak Diketahui') }}
                                                <br>
                                                Kondisi Saat Diajukan: {{ $detail->kondisi_sebelum ?? $barangQr->kondisi }}
                                            </small>
                                        </p>
                                        <p class="card-text mb-1">
                                            <strong>Status Unit:</strong>
                                            <span
                                                class="badge 
                                        @if ($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DIAJUKAN) bg-info
                                        @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DISETUJUI) bg-primary
                                        @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DIAMBIL) bg-purple
                                        @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DIKEMBALIKAN) bg-success
                                        @elseif(in_array($detail->status_unit, [App\Models\DetailPeminjaman::STATUS_ITEM_RUSAK_SAAT_DIPINJAM])) bg-danger
                                        @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_HILANG_SAAT_DIPINJAM) bg-dark
                                        @else bg-secondary @endif">
                                                {{ $detail->status_unit }}
                                            </span>
                                            @if ($detail->terlambat)
                                                <span class="badge bg-danger ms-1">Terlambat</span>
                                            @endif
                                        </p>
                                        @if ($detail->tanggal_diambil)
                                            <p class="card-text mb-1"><small>Diambil:
                                                    {{ $detail->tanggal_diambil->format('d M Y, H:i') }}</small></p>
                                        @endif
                                        @if ($detail->tanggal_dikembalikan)
                                            <p class="card-text mb-1"><small>Dikembalikan:
                                                    {{ $detail->tanggal_dikembalikan->format('d M Y, H:i') }} | Kondisi
                                                    Setelah: {{ $detail->kondisi_setelah ?? '-' }}</small></p>
                                        @endif
                                        @if ($detail->catatan_unit)
                                            <p class="card-text mb-0"><small>Catatan Unit:
                                                    {{ $detail->catatan_unit }}</small></p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal untuk Pembatalan --}}
    <div class="modal fade" id="cancelByUserModalGuruShow" tabindex="-1" aria-labelledby="cancelByUserModalGuruShowLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="cancelByUserFormGuruShow" method="POST"> {{-- Action akan di-set oleh JS --}}
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelByUserModalGuruShowLabel">Batalkan Pengajuan Peminjaman</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Anda yakin ingin membatalkan pengajuan peminjaman ID: <strong
                                id="cancelPeminjamanIdTextGuruShow"></strong>?</p>
                        <div class="mb-3">
                            <label for="alasan_pembatalan_guru_show" class="form-label">Alasan Pembatalan
                                (Opsional):</label>
                            <textarea class="form-control" id="alasan_pembatalan_guru_show" name="alasan_pembatalan" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-warning">Ya, Batalkan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })

            const cancelButtonsGuruShow = document.querySelectorAll('.btn-cancel-by-user-guru');
            const cancelModalElGuruShow = document.getElementById('cancelByUserModalGuruShow');
            const cancelModalGuruShow = cancelModalElGuruShow ? new bootstrap.Modal(cancelModalElGuruShow) : null;
            const cancelFormGuruShow = document.getElementById('cancelByUserFormGuruShow');
            const cancelPeminjamanIdTextGuruShow = document.getElementById('cancelPeminjamanIdTextGuruShow');

            cancelButtonsGuruShow.forEach(button => {
                button.addEventListener('click', function() {
                    const peminjamanId = this.dataset.id;
                    if (cancelFormGuruShow) cancelFormGuruShow.action =
                        `{{ route('guru.peminjaman.cancelByUser', ['peminjaman' => ':peminjamanId']) }}`
                        .replace(':peminjamanId', peminjamanId);
                    if (cancelPeminjamanIdTextGuruShow) cancelPeminjamanIdTextGuruShow.textContent =
                        `PMJ-${String(peminjamanId).padStart(5, '0')}`;
                    if (cancelModalGuruShow) cancelModalGuruShow.show();
                });
            });
        });
    </script>
@endpush
