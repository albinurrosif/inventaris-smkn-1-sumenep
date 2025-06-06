@extends('layouts.app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('title', 'Detail Peminjaman Aset')

@push('styles')
    {{-- Jika ada CSS khusus untuk halaman ini --}}
    <style>
        .item-actions .btn {
            margin-right: 5px;
            margin-bottom: 5px;
            /* Agar ada jarak jika tombol wrap ke baris baru */
        }

        .item-card {
            border-left-width: 5px;
            transition: all 0.3s ease-in-out;
        }

        .item-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .border-left-diajukan {
            border-left-color: #0dcaf0;
            /* Info */
        }

        .border-left-disetujui {
            border-left-color: #0d6efd;
            /* Primary */
        }

        .border-left-diambil {
            border-left-color: #6f42c1;
            /* Purple */
        }

        .border-left-dikembalikan {
            border-left-color: #198754;
            /* Success */
        }

        .border-left-rusak {
            border-left-color: #dc3545;
            /* Danger */
        }

        .border-left-hilang {
            border-left-color: #212529;
            /* Dark */
        }

        .timeline-item .event-date {
            font-weight: bold;
        }

        .timeline-item .event-description {
            font-size: 0.9rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Detail Peminjaman: PMJ-{{ str_pad($peminjaman->id, 5, '0', STR_PAD_LEFT) }}</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a
                                    href="{{ route('admin.peminjaman.index', request()->query()) }}">Manajemen
                                    Peminjaman</a></li> {{-- Bawa query string filter dari halaman index --}}
                            <li class="breadcrumb-item active">Detail Peminjaman</li>
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
            {{-- Kolom Informasi Peminjaman --}}
            <div class="col-xl-4 col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informasi Peminjaman</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>ID Peminjaman:</strong> PMJ-{{ str_pad($peminjaman->id, 5, '0', STR_PAD_LEFT) }}</p>
                        <p><strong>Peminjam:</strong> {{ $peminjaman->guru->username ?? 'N/A' }}
                            ({{ $peminjaman->guru->role ?? 'N/A' }})</p>
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
                                <span class="badge bg-danger">Terlambat</span>
                            @endif
                        </p>
                        <p><strong>Ruangan Tujuan Penggunaan:</strong>
                            {{ $peminjaman->ruanganTujuanPeminjaman->nama_ruangan ?? '-' }}</p>
                        <p><strong>Catatan Peminjam:</strong> {{ $peminjaman->catatan_peminjam ?: '-' }}</p>
                        <p><strong>Disetujui Oleh:</strong> {{ $peminjaman->disetujuiOlehUser->username ?? '-' }}
                            @if ($peminjaman->tanggal_disetujui)
                                (pada {{ $peminjaman->tanggal_disetujui->format('d M Y, H:i') }})
                            @endif
                        </p>
                        <p><strong>Ditolak Oleh:</strong> {{ $peminjaman->ditolakOlehUser->username ?? '-' }}
                            @if ($peminjaman->tanggal_ditolak)
                                (pada {{ $peminjaman->tanggal_ditolak->format('d M Y, H:i') }})
                            @endif
                        </p>
                        <p><strong>Catatan Operator:</strong> {{ $peminjaman->catatan_operator ?: '-' }}</p>
                        <p><strong>Tanggal Semua Diambil:</strong>
                            {{ $peminjaman->tanggal_semua_diambil ? $peminjaman->tanggal_semua_diambil->format('d M Y, H:i') : '-' }}
                        </p>
                        <p><strong>Tanggal Selesai Peminjaman:</strong>
                            {{ $peminjaman->tanggal_selesai ? $peminjaman->tanggal_selesai->format('d M Y, H:i') : '-' }}
                        </p>

                    </div>
                    <div class="card-footer">
                        @if (!$peminjaman->trashed())
                            @can('update', $peminjaman)
                                @if (in_array($peminjaman->status, [
                                        App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN,
                                        App\Models\Peminjaman::STATUS_DISETUJUI,
                                    ]))
                                    <a href="{{ route('admin.peminjaman.edit', $peminjaman->id) }}"
                                        class="btn btn-sm btn-info me-1"><i data-feather="edit-2" class="me-1"></i>Edit
                                        Pengajuan</a>
                                @endif
                            @endcan

                            @can('manage', $peminjaman)
                                @if ($peminjaman->status === App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN)
                                    <button class="btn btn-sm btn-success me-1 btn-approve" data-id="{{ $peminjaman->id }}"><i
                                            data-feather="check-circle" class="me-1"></i>Setujui</button>
                                    <button class="btn btn-sm btn-danger btn-reject" data-id="{{ $peminjaman->id }}"><i
                                            data-feather="x-circle" class="me-1"></i>Tolak</button>
                                @endif
                            @endcan
                            @can('cancelByUser', $peminjaman)
                                {{-- Admin juga bisa cancel --}}
                                @if (in_array($peminjaman->status, [
                                        App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN,
                                        App\Models\Peminjaman::STATUS_DISETUJUI,
                                    ]) &&
                                        !$peminjaman->detailPeminjaman()->where('status_unit', App\Models\DetailPeminjaman::STATUS_ITEM_DIAMBIL)->exists())
                                    <button class="btn btn-sm btn-warning btn-cancel-by-user"
                                        data-id="{{ $peminjaman->id }}"><i data-feather="slash"
                                            class="me-1"></i>Batalkan</button>
                                @endif
                            @endcan
                        @endif
                    </div>
                </div>
            </div>

            {{-- Kolom Detail Item Peminjaman --}}
            <div class="col-xl-8 col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Item Barang Dipinjam ({{ $peminjaman->detailPeminjaman->count() }}
                            item)</h5>
                    </div>
                    <div class="card-body">
                        @if ($peminjaman->detailPeminjaman->isEmpty())
                            <p class="text-center">Tidak ada item barang dalam peminjaman ini.</p>
                        @else
                            @foreach ($peminjaman->detailPeminjaman as $detail)
                                <div
                                    class="card mb-3 item-card 
                            @if ($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DIAJUKAN) border-left-diajukan
                            @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DISETUJUI) border-left-disetujui
                            @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DIAMBIL) border-left-diambil
                            @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DIKEMBALIKAN) border-left-dikembalikan
                            @elseif(in_array($detail->status_unit, [App\Models\DetailPeminjaman::STATUS_ITEM_RUSAK_SAAT_DIPINJAM])) border-left-rusak
                            @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_HILANG_SAAT_DIPINJAM) border-left-hilang @endif">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h6 class="card-title">
                                                    {{ $detail->barangQrCode->barang->nama_barang ?? 'N/A' }}
                                                    <small
                                                        class="text-muted">({{ $detail->barangQrCode->kode_inventaris_sekolah ?? 'N/A' }})</small>
                                                </h6>
                                                <p class="card-text mb-1">
                                                    <small>
                                                        No. Seri Pabrik: {{ $detail->barangQrCode->no_seri_pabrik ?: '-' }}
                                                        <br>
                                                        Lokasi Asal:
                                                        {{ $detail->barangQrCode->ruangan->nama_ruangan ?? ($detail->barangQrCode->pemegangPersonal->username ?? 'Tidak Diketahui') }}
                                                        <br>
                                                        Kondisi Saat Diajukan/Disetujui:
                                                        {{ $detail->kondisi_sebelum ?? $detail->barangQrCode->kondisi }}
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
                                                            {{ $detail->tanggal_diambil->format('d M Y, H:i') }}</small>
                                                    </p>
                                                @endif
                                                @if ($detail->tanggal_dikembalikan)
                                                    <p class="card-text mb-1"><small>Dikembalikan:
                                                            {{ $detail->tanggal_dikembalikan->format('d M Y, H:i') }} |
                                                            Kondisi Setelah: {{ $detail->kondisi_setelah ?? '-' }}</small>
                                                    </p>
                                                @endif
                                                @if ($detail->catatan_unit)
                                                    <p class="card-text mb-0"><small>Catatan Unit:
                                                            {{ $detail->catatan_unit }}</small></p>
                                                @endif
                                            </div>
                                            <div class="col-md-4 text-md-end item-actions">
                                                @if (!$peminjaman->trashed())
                                                    @can('processHandover', $detailPeminjaman->peminjaman)
                                                        {{-- Otorisasi pada Peminjaman induk --}}
                                                        @if ($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DISETUJUI)
                                                            <button class="btn btn-sm btn-outline-primary btn-handover"
                                                                data-detail-id="{{ $detail->id }}"
                                                                data-peminjaman-id="{{ $peminjaman->id }}"
                                                                data-bs-toggle="tooltip" title="Konfirmasi Barang Diambil">
                                                                <i data-feather="check-square" class="me-1"></i> Tandai
                                                                Diambil
                                                            </button>
                                                        @endif
                                                    @endcan

                                                    @can('processReturn', $detailPeminjaman->peminjaman)
                                                        {{-- Otorisasi pada Peminjaman induk --}}
                                                        @if (in_array($detail->status_unit, [
                                                                App\Models\DetailPeminjaman::STATUS_ITEM_DIAMBIL,
                                                                App\Models\DetailPeminjaman::STATUS_ITEM_RUSAK_SAAT_DIPINJAM,
                                                            ]))
                                                            <button class="btn btn-sm btn-outline-success btn-return"
                                                                data-detail-id="{{ $detail->id }}"
                                                                data-peminjaman-id="{{ $peminjaman->id }}"
                                                                data-bs-toggle="tooltip" title="Verifikasi Pengembalian Barang">
                                                                <i data-feather="corner-down-left" class="me-1"></i>
                                                                Verifikasi Kembali
                                                            </button>
                                                        @endif
                                                    @endcan
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal untuk Persetujuan, Penolakan, Pembatalan --}}
    {{-- Copy modal dari halaman index atau buat yang baru jika ada perbedaan field --}}
    {{-- Modal untuk Persetujuan --}}
    <div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="approveForm" method="POST"> {{-- Action akan di-set oleh JS --}}
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="approveModalLabel">Setujui Peminjaman</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Anda yakin ingin menyetujui pengajuan peminjaman ini (ID: <strong
                                id="approvePeminjamanIdText"></strong>)?</p>
                        <div class="mb-3">
                            <label for="catatan_operator_approve" class="form-label">Catatan Operator (Opsional):</label>
                            <textarea class="form-control" id="catatan_operator_approve" name="catatan_operator_approve" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Ya, Setujui</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal untuk Penolakan --}}
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="rejectForm" method="POST"> {{-- Action akan di-set oleh JS --}}
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectModalLabel">Tolak Peminjaman</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Anda yakin ingin menolak pengajuan peminjaman ini (ID: <strong
                                id="rejectPeminjamanIdText"></strong>)?</p>
                        <div class="mb-3">
                            <label for="catatan_operator_reject" class="form-label">Alasan Penolakan <span
                                    class="text-danger">*</span>:</label>
                            <textarea class="form-control" id="catatan_operator_reject" name="catatan_operator_reject" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Ya, Tolak</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal untuk Pembatalan oleh User/Admin --}}
    <div class="modal fade" id="cancelByUserModal" tabindex="-1" aria-labelledby="cancelByUserModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="cancelByUserForm" method="POST"> {{-- Action akan di-set oleh JS --}}
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelByUserModalLabel">Batalkan Pengajuan Peminjaman</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Anda yakin ingin membatalkan pengajuan peminjaman ini (ID: <strong
                                id="cancelPeminjamanIdText"></strong>)?</p>
                        <div class="mb-3">
                            <label for="alasan_pembatalan_user" class="form-label">Alasan Pembatalan (Opsional):</label>
                            <textarea class="form-control" id="alasan_pembatalan_user" name="alasan_pembatalan" rows="3"></textarea>
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

    {{-- Modal untuk Konfirmasi Pengambilan Barang --}}
    <div class="modal fade" id="handoverModal" tabindex="-1" aria-labelledby="handoverModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="handoverForm" method="POST"> {{-- Action di-set oleh JS --}}
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="handoverModalLabel">Konfirmasi Pengambilan Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Konfirmasi bahwa barang <strong id="handoverItemName"></strong> (<span
                                id="handoverItemKode"></span>) telah diambil oleh peminjam?</p>
                        {{-- Tidak perlu catatan di sini, kondisi sudah dicatat saat disetujui --}}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Ya, Sudah Diambil</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal untuk Verifikasi Pengembalian Barang --}}
    <div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="returnForm" method="POST"> {{-- Action di-set oleh JS --}}
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="returnModalLabel">Verifikasi Pengembalian Barang</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Verifikasi pengembalian barang <strong id="returnItemName"></strong> (<span
                                id="returnItemKode"></span>).</p>
                        <div class="mb-3">
                            <label for="kondisi_setelah_kembali" class="form-label">Kondisi Barang Setelah Kembali <span
                                    class="text-danger">*</span></label>
                            <select class="form-select" id="kondisi_setelah_kembali" name="kondisi_setelah_kembali"
                                required>
                                @foreach ($kondisiList as $kondisiValue => $kondisiLabel)
                                    <option value="{{ $kondisiValue }}">{{ $kondisiLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="catatan_pengembalian_unit" class="form-label">Catatan Pengembalian
                                (Opsional):</label>
                            <textarea class="form-control" id="catatan_pengembalian_unit" name="catatan_pengembalian_unit" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Proses Pengembalian</button>
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
            // Inisialisasi tooltip Bootstrap
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })

            // Modal Approve
            const approveButtons = document.querySelectorAll('.btn-approve');
            const approveModalEl = document.getElementById('approveModal');
            const approveModal = approveModalEl ? new bootstrap.Modal(approveModalEl) : null;
            const approveForm = document.getElementById('approveForm');
            const approvePeminjamanIdText = document.getElementById('approvePeminjamanIdText');

            approveButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const peminjamanId = this.dataset.id;
                    if (approveForm) approveForm.action =
                        `{{ url('admin/peminjaman') }}/${peminjamanId}/approve`;
                    if (approvePeminjamanIdText) approvePeminjamanIdText.textContent =
                        `PMJ-${String(peminjamanId).padStart(5, '0')}`;
                    if (approveModal) approveModal.show();
                });
            });

            // Modal Reject
            const rejectButtons = document.querySelectorAll('.btn-reject');
            const rejectModalEl = document.getElementById('rejectModal');
            const rejectModal = rejectModalEl ? new bootstrap.Modal(rejectModalEl) : null;
            const rejectForm = document.getElementById('rejectForm');
            const rejectPeminjamanIdText = document.getElementById('rejectPeminjamanIdText');


            rejectButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const peminjamanId = this.dataset.id;
                    if (rejectForm) rejectForm.action =
                        `{{ url('admin/peminjaman') }}/${peminjamanId}/reject`;
                    if (rejectPeminjamanIdText) rejectPeminjamanIdText.textContent =
                        `PMJ-${String(peminjamanId).padStart(5, '0')}`;
                    if (rejectModal) rejectModal.show();
                });
            });

            // Modal Cancel by User
            const cancelByUserButtons = document.querySelectorAll('.btn-cancel-by-user');
            const cancelByUserModalEl = document.getElementById('cancelByUserModal');
            const cancelByUserModal = cancelByUserModalEl ? new bootstrap.Modal(cancelByUserModalEl) : null;
            const cancelByUserForm = document.getElementById('cancelByUserForm');
            const cancelPeminjamanIdText = document.getElementById('cancelPeminjamanIdText');

            cancelByUserButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const peminjamanId = this.dataset.id;
                    if (cancelByUserForm) cancelByUserForm.action =
                        `{{ route('admin.peminjaman.cancelByUser', ['peminjaman' => ':peminjamanId']) }}`
                        .replace(':peminjamanId', peminjamanId);
                    if (cancelPeminjamanIdText) cancelPeminjamanIdText.textContent =
                        `PMJ-${String(peminjamanId).padStart(5, '0')}`;
                    if (cancelByUserModal) cancelByUserModal.show();
                });
            });


            // Modal Handover Item
            const handoverButtons = document.querySelectorAll('.btn-handover');
            const handoverModalEl = document.getElementById('handoverModal');
            const handoverModal = handoverModalEl ? new bootstrap.Modal(handoverModalEl) : null;
            const handoverForm = document.getElementById('handoverForm');
            const handoverItemName = document.getElementById('handoverItemName');
            const handoverItemKode = document.getElementById('handoverItemKode');

            handoverButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const detailId = this.dataset.detailId;
                    const itemName = this.closest('.card-body').querySelector('.card-title')
                        .innerText.split('(')[0].trim();
                    const itemKode = this.closest('.card-body').querySelector(
                        '.card-title .text-muted').innerText.replace(/[()]/g, '');

                    if (handoverForm) handoverForm.action =
                        `{{ url('admin/peminjaman/detail') }}/${detailId}/handover`;
                    if (handoverItemName) handoverItemName.textContent = itemName;
                    if (handoverItemKode) handoverItemKode.textContent = itemKode;
                    if (handoverModal) handoverModal.show();
                });
            });

            // Modal Return Item
            const returnButtons = document.querySelectorAll('.btn-return');
            const returnModalEl = document.getElementById('returnModal');
            const returnModal = returnModalEl ? new bootstrap.Modal(returnModalEl) : null;
            const returnForm = document.getElementById('returnForm');
            const returnItemName = document.getElementById('returnItemName');
            const returnItemKode = document.getElementById('returnItemKode');
            const kondisiSetelahKembaliSelect = document.getElementById('kondisi_setelah_kembali');

            returnButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const detailId = this.dataset.detailId;
                    const itemName = this.closest('.card-body').querySelector('.card-title')
                        .innerText.split('(')[0].trim();
                    const itemKode = this.closest('.card-body').querySelector(
                        '.card-title .text-muted').innerText.replace(/[()]/g, '');

                    if (returnForm) returnForm.action =
                        `{{ url('admin/peminjaman/detail') }}/${detailId}/return`;
                    if (returnItemName) returnItemName.textContent = itemName;
                    if (returnItemKode) returnItemKode.textContent = itemKode;
                    // Reset select to default (Baik)
                    if (kondisiSetelahKembaliSelect) kondisiSetelahKembaliSelect.value = 'Baik';
                    if (returnModal) returnModal.show();
                });
            });

            // Submit form modal dengan AJAX (contoh untuk handover dan return)
            if (handoverForm) {
                handoverForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    handleFormSubmit(this, handoverModal);
                });
            }
            if (returnForm) {
                returnForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    handleFormSubmit(this, returnModal);
                });
            }

            function handleFormSubmit(form, modalInstance) {
                const formData = new FormData(form);
                const actionUrl = form.action;
                const method = form.method;

                fetch(actionUrl, {
                        method: method,
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                                'content'), // Jika ada CSRF token
                            'Accept': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (modalInstance) modalInstance.hide();
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload(); // Reload halaman untuk melihat perubahan
                            });
                        } else {
                            // Tampilkan error validasi atau error umum
                            let errorMessages = data.message || 'Terjadi kesalahan.';
                            if (data.errors) {
                                errorMessages = Object.values(data.errors).map(err => err.join('<br>')).join(
                                    '<br>');
                            }
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                html: errorMessages,
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Terjadi kesalahan saat mengirim data.',
                        });
                    });
            }


        });
    </script>
@endpush
