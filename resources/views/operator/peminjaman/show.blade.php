@extends('layouts.app') {{-- Sesuaikan dengan layout operator Anda --}}

@section('title', 'Detail Peminjaman Aset (Operator)')

@push('styles')
<style>
    .item-actions .btn {
        margin-right: 5px;
        margin-bottom: 5px;
    }
    .item-card {
        border-left-width: 5px;
        transition: all 0.3s ease-in-out;
    }
    .item-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .border-left-diajukan { border-left-color: #0dcaf0; }
    .border-left-disetujui { border-left-color: #0d6efd; }
    .border-left-diambil { border-left-color: #6f42c1; }
    .border-left-dikembalikan { border-left-color: #198754; }
    .border-left-rusak { border-left-color: #dc3545; }
    .border-left-hilang { border-left-color: #212529; }
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
                        <li class="breadcrumb-item"><a href="{{ route('operator.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('operator.peminjaman.index', request()->query()) }}">Peminjaman Aset</a></li>
                        <li class="breadcrumb-item active">Detail Peminjaman</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if(session('error'))
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
                    <p><strong>Peminjam:</strong> {{ $peminjaman->guru->username ?? 'N/A' }} ({{ $peminjaman->guru->role ?? 'N/A' }})</p>
                    <p><strong>Tujuan Peminjaman:</strong> {{ $peminjaman->tujuan_peminjaman }}</p>
                    <p><strong>Tanggal Pengajuan:</strong> {{ $peminjaman->tanggal_pengajuan ? $peminjaman->tanggal_pengajuan->format('d M Y, H:i') : '-' }}</p>
                    <p><strong>Rencana Pinjam:</strong> {{ $peminjaman->tanggal_rencana_pinjam ? $peminjaman->tanggal_rencana_pinjam->format('d M Y') : '-' }}</p>
                    <p><strong>Harus Kembali:</strong> {{ $peminjaman->tanggal_harus_kembali ? $peminjaman->tanggal_harus_kembali->format('d M Y') : '-' }}</p>
                    <p><strong>Status Peminjaman:</strong>
                        <span class="badge bg-{{ App\Models\Peminjaman::statusColor($peminjaman->status) }}">{{ $peminjaman->status }}</span>
                         @if($peminjaman->ada_item_terlambat && $peminjaman->status !== App\Models\Peminjaman::STATUS_SELESAI)
                            <span class="badge bg-danger ms-1">Terlambat</span>
                        @endif
                    </p>
                    <p><strong>Ruangan Tujuan Penggunaan:</strong> {{ $peminjaman->ruanganTujuanPeminjaman->nama_ruangan ?? '-' }}</p>
                    <p><strong>Catatan Peminjam:</strong> {{ $peminjaman->catatan_peminjam ?: '-' }}</p>
                    <p><strong>Disetujui Oleh:</strong> {{ $peminjaman->disetujuiOlehUser->username ?? '-' }}
                        @if($peminjaman->tanggal_disetujui)
                         (pada {{ $peminjaman->tanggal_disetujui->format('d M Y, H:i') }})
                        @endif
                    </p>
                     <p><strong>Ditolak Oleh:</strong> {{ $peminjaman->ditolakOlehUser->username ?? '-' }}
                        @if($peminjaman->tanggal_ditolak)
                         (pada {{ $peminjaman->tanggal_ditolak->format('d M Y, H:i') }})
                        @endif
                    </p>
                    <p><strong>Catatan Operator:</strong> {{ $peminjaman->catatan_operator ?: '-' }}</p>
                     <p><strong>Tanggal Semua Diambil:</strong> {{ $peminjaman->tanggal_semua_diambil ? $peminjaman->tanggal_semua_diambil->format('d M Y, H:i') : '-' }}</p>
                    <p><strong>Tanggal Selesai Peminjaman:</strong> {{ $peminjaman->tanggal_selesai ? $peminjaman->tanggal_selesai->format('d M Y, H:i') : '-' }}</p>
                </div>
                <div class="card-footer">
                    @can('manage', $peminjaman)
                        @if ($peminjaman->status === App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN)
                            <button class="btn btn-sm btn-success me-1 btn-approve" data-id="{{ $peminjaman->id }}"><i data-feather="check-circle" class="me-1"></i>Setujui</button>
                            <button class="btn btn-sm btn-danger btn-reject" data-id="{{ $peminjaman->id }}"><i data-feather="x-circle" class="me-1"></i>Tolak</button>
                        @endif
                    @endcan
                     @can('update', $peminjaman) {{-- Untuk update catatan operator --}}
                        @if(in_array($peminjaman->status, [App\Models\Peminjaman::STATUS_MENUNGGU_PERSETUJUAN, App\Models\Peminjaman::STATUS_DISETUJUI, App\Models\Peminjaman::STATUS_SEDANG_DIPINJAM]))
                            <a href="{{ route('operator.peminjaman.edit', $peminjaman->id) }}" class="btn btn-sm btn-outline-info"><i data-feather="edit-3" class="me-1"></i>Edit Catatan</a>
                        @endif
                    @endcan
                </div>
            </div>
        </div>

        {{-- Kolom Detail Item Peminjaman --}}
        <div class="col-xl-8 col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Item Barang Dipinjam ({{ $peminjaman->detailPeminjaman->count() }} item)</h5>
                </div>
                <div class="card-body">
                    @if ($peminjaman->detailPeminjaman->isEmpty())
                        <p class="text-center">Tidak ada item barang dalam peminjaman ini.</p>
                    @else
                        @foreach ($peminjaman->detailPeminjaman as $detail)
                        @php
                            $barangQr = $detail->barangQrCode; // Ambil relasi sekali
                            $itemRuanganOperator = false;
                            if ($barangQr && $barangQr->id_ruangan && Auth::user()->ruanganYangDiKelola()->where('id', $barangQr->id_ruangan)->exists()) {
                                $itemRuanganOperator = true;
                            }
                        @endphp
                        <div class="card mb-3 item-card 
                            @if($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DIAJUKAN) border-left-diajukan
                            @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DISETUJUI) border-left-disetujui
                            @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DIAMBIL) border-left-diambil
                            @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DIKEMBALIKAN) border-left-dikembalikan
                            @elseif(in_array($detail->status_unit, [App\Models\DetailPeminjaman::STATUS_ITEM_RUSAK_SAAT_DIPINJAM])) border-left-rusak
                            @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_HILANG_SAAT_DIPINJAM) border-left-hilang
                            @endif">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h6 class="card-title">
                                            {{ $barangQr->barang->nama_barang ?? 'N/A' }}
                                            <small class="text-muted">({{ $barangQr->kode_inventaris_sekolah ?? 'N/A' }})</small>
                                        </h6>
                                        <p class="card-text mb-1">
                                            <small>
                                                No. Seri Pabrik: {{ $barangQr->no_seri_pabrik ?: '-' }} <br>
                                                Lokasi Asal: {{ $barangQr->ruangan->nama_ruangan ?? ($barangQr->id_pemegang_personal ? 'Pemegang: '.optional($barangQr->pemegangPersonal)->username : 'Tidak Diketahui') }} <br>
                                                Kondisi Saat Diajukan/Disetujui: {{ $detail->kondisi_sebelum ?? $barangQr->kondisi }}
                                            </small>
                                        </p>
                                        <p class="card-text mb-1">
                                            <strong>Status Unit:</strong>
                                            <span class="badge 
                                                @if($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DIAJUKAN) bg-info
                                                @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DISETUJUI) bg-primary
                                                @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DIAMBIL) bg-purple
                                                @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DIKEMBALIKAN) bg-success
                                                @elseif(in_array($detail->status_unit, [App\Models\DetailPeminjaman::STATUS_ITEM_RUSAK_SAAT_DIPINJAM])) bg-danger
                                                @elseif($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_HILANG_SAAT_DIPINJAM) bg-dark
                                                @else bg-secondary @endif">
                                                {{ $detail->status_unit }}
                                            </span>
                                            @if($detail->terlambat)
                                                <span class="badge bg-danger ms-1">Terlambat</span>
                                            @endif
                                        </p>
                                        @if($detail->tanggal_diambil)
                                        <p class="card-text mb-1"><small>Diambil: {{ $detail->tanggal_diambil->format('d M Y, H:i') }}</small></p>
                                        @endif
                                        @if($detail->tanggal_dikembalikan)
                                        <p class="card-text mb-1"><small>Dikembalikan: {{ $detail->tanggal_dikembalikan->format('d M Y, H:i') }} | Kondisi Setelah: {{ $detail->kondisi_setelah ?? '-' }}</small></p>
                                        @endif
                                         @if($detail->catatan_unit)
                                        <p class="card-text mb-0"><small>Catatan Unit: {{ $detail->catatan_unit }}</small></p>
                                        @endif
                                    </div>
                                    <div class="col-md-4 text-md-end item-actions">
                                        {{-- Tombol hanya muncul jika Operator berwenang atas ruangan barang ini --}}
                                        @if ($itemRuanganOperator || Auth::user()->hasRole(App\Models\User::ROLE_ADMIN))
                                            @can('processHandover', $detail) {{-- Menggunakan DetailPeminjamanPolicy --}}
                                                @if ($detail->status_unit === App\Models\DetailPeminjaman::STATUS_ITEM_DISETUJUI)
                                                    <button class="btn btn-sm btn-outline-primary btn-handover" data-detail-id="{{ $detail->id }}" data-bs-toggle="tooltip" title="Konfirmasi Barang Diambil">
                                                        <i data-feather="check-square" class="me-1"></i> Tandai Diambil
                                                    </button>
                                                @endif
                                            @endcan

                                            @can('processReturn', $detail) {{-- Menggunakan DetailPeminjamanPolicy --}}
                                                @if (in_array($detail->status_unit, [App\Models\DetailPeminjaman::STATUS_ITEM_DIAMBIL, App\Models\DetailPeminjaman::STATUS_ITEM_RUSAK_SAAT_DIPINJAM]))
                                                    <button class="btn btn-sm btn-outline-success btn-return" data-detail-id="{{ $detail->id }}" data-bs-toggle="tooltip" title="Verifikasi Pengembalian Barang">
                                                        <i data-feather="corner-down-left" class="me-1"></i> Verifikasi Kembali
                                                    </button>
                                                @endif
                                            @endcan
                                        @else
                                            <span class="badge bg-light text-dark">Tidak ada aksi untuk item ini</span>
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

{{-- Modal untuk Persetujuan, Penolakan (Sama seperti di index) --}}
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="approveFormOpShow" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approveModalLabelOpShow">Setujui Peminjaman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Anda yakin ingin menyetujui pengajuan peminjaman ID: <strong id="approvePeminjamanIdTextOpShow"></strong>?</p>
                     <div class="mb-3">
                        <label for="catatan_operator_approve_op_show" class="form-label">Catatan Operator (Opsional):</label>
                        <textarea class="form-control" id="catatan_operator_approve_op_show" name="catatan_operator_approve" rows="3"></textarea>
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

<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="rejectFormOpShow" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabelOpShow">Tolak Peminjaman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                     <p>Anda yakin ingin menolak pengajuan peminjaman ID: <strong id="rejectPeminjamanIdTextOpShow"></strong>?</p>
                    <div class="mb-3">
                        <label for="catatan_operator_reject_op_show" class="form-label">Alasan Penolakan <span class="text-danger">*</span>:</label>
                        <textarea class="form-control" id="catatan_operator_reject_op_show" name="catatan_operator_reject" rows="3" required></textarea>
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


{{-- Modal untuk Konfirmasi Pengambilan Barang --}}
<div class="modal fade" id="handoverModalOp" tabindex="-1" aria-labelledby="handoverModalLabelOp" aria-hidden="true">
    <div class="modal-dialog">
        <form id="handoverFormOp" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="handoverModalLabelOp">Konfirmasi Pengambilan Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Konfirmasi bahwa barang <strong id="handoverItemNameOp"></strong> (<span id="handoverItemKodeOp"></span>) telah diambil oleh peminjam?</p>
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
<div class="modal fade" id="returnModalOp" tabindex="-1" aria-labelledby="returnModalLabelOp" aria-hidden="true">
    <div class="modal-dialog">
        <form id="returnFormOp" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="returnModalLabelOp">Verifikasi Pengembalian Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Verifikasi pengembalian barang <strong id="returnItemNameOp"></strong> (<span id="returnItemKodeOp"></span>).</p>
                    <div class="mb-3">
                        <label for="kondisi_setelah_kembali_op" class="form-label">Kondisi Barang Setelah Kembali <span class="text-danger">*</span></label>
                        <select class="form-select" id="kondisi_setelah_kembali_op" name="kondisi_setelah_kembali" required>
                            @foreach ($kondisiList as $kondisiValue => $kondisiLabel)
                                <option value="{{ $kondisiValue }}">{{ $kondisiLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="catatan_pengembalian_unit_op" class="form-label">Catatan Pengembalian (Opsional):</label>
                        <textarea class="form-control" id="catatan_pengembalian_unit_op" name="catatan_pengembalian_unit" rows="3"></textarea>
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
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        // --- Logika untuk Modal Approve/Reject Peminjaman (jika ada tombolnya di halaman show) ---
        const approveButtonsShow = document.querySelectorAll('.btn-approve'); // Tombol approve di halaman show
        const approveModalElShow = document.getElementById('approveModal'); // Modal approve
        const approveModalShow = approveModalElShow ? new bootstrap.Modal(approveModalElShow) : null;
        const approveFormOpShow = document.getElementById('approveFormOpShow'); // Form di modal approve
        const approvePeminjamanIdTextOpShow = document.getElementById('approvePeminjamanIdTextOpShow'); // Elemen untuk menampilkan ID

        approveButtonsShow.forEach(button => {
            button.addEventListener('click', function () {
                const peminjamanId = this.dataset.id;
                if(approveFormOpShow) approveFormOpShow.action = `{{ url('operator/peminjaman') }}/${peminjamanId}/approve`;
                if(approvePeminjamanIdTextOpShow) approvePeminjamanIdTextOpShow.textContent = `PMJ-${String(peminjamanId).padStart(5, '0')}`;
                if(approveModalShow) approveModalShow.show();
            });
        });

        const rejectButtonsShow = document.querySelectorAll('.btn-reject'); // Tombol reject di halaman show
        const rejectModalElShow = document.getElementById('rejectModal'); // Modal reject
        const rejectModalShow = rejectModalElShow ? new bootstrap.Modal(rejectModalElShow) : null;
        const rejectFormOpShow = document.getElementById('rejectFormOpShow'); // Form di modal reject
        const rejectPeminjamanIdTextOpShow = document.getElementById('rejectPeminjamanIdTextOpShow'); // Elemen untuk menampilkan ID

        rejectButtonsShow.forEach(button => {
            button.addEventListener('click', function () {
                const peminjamanId = this.dataset.id;
                if(rejectFormOpShow) rejectFormOpShow.action = `{{ url('operator/peminjaman') }}/${peminjamanId}/reject`;
                if(rejectPeminjamanIdTextOpShow) rejectPeminjamanIdTextOpShow.textContent = `PMJ-${String(peminjamanId).padStart(5, '0')}`;
                if(rejectModalShow) rejectModalShow.show();
            });
        });

        // --- Logika untuk Modal Handover Item ---
        const handoverButtonsOp = document.querySelectorAll('.btn-handover');
        const handoverModalElOp = document.getElementById('handoverModalOp');
        const handoverModalOp = handoverModalElOp ? new bootstrap.Modal(handoverModalElOp) : null;
        const handoverFormOp = document.getElementById('handoverFormOp');
        const handoverItemNameOp = document.getElementById('handoverItemNameOp');
        const handoverItemKodeOp = document.getElementById('handoverItemKodeOp');

        handoverButtonsOp.forEach(button => {
            button.addEventListener('click', function () {
                const detailId = this.dataset.detailId;
                // Ambil nama dan kode dari elemen terdekat yang relevan
                const cardBody = this.closest('.card-body');
                const itemName = cardBody.querySelector('.card-title').innerText.split('(')[0].trim();
                const itemKode = cardBody.querySelector('.card-title .text-muted').innerText.replace(/[()]/g, '');

                if(handoverFormOp) handoverFormOp.action = `{{ url(Auth::user()->hasRole(App\Models\User::ROLE_ADMIN) ? 'admin' : 'operator') }}/peminjaman/detail/${detailId}/handover`;
                if(handoverItemNameOp) handoverItemNameOp.textContent = itemName;
                if(handoverItemKodeOp) handoverItemKodeOp.textContent = itemKode;
                if(handoverModalOp) handoverModalOp.show();
            });
        });
        if(handoverFormOp) {
            handoverFormOp.addEventListener('submit', function(event) {
                event.preventDefault();
                handleFormSubmit(this, handoverModalOp);
            });
        }


        // --- Logika untuk Modal Return Item ---
        const returnButtonsOp = document.querySelectorAll('.btn-return');
        const returnModalElOp = document.getElementById('returnModalOp');
        const returnModalOp = returnModalElOp ? new bootstrap.Modal(returnModalElOp) : null;
        const returnFormOp = document.getElementById('returnFormOp');
        const returnItemNameOp = document.getElementById('returnItemNameOp');
        const returnItemKodeOp = document.getElementById('returnItemKodeOp');
        const kondisiSetelahKembaliSelectOp = document.getElementById('kondisi_setelah_kembali_op');


        returnButtonsOp.forEach(button => {
            button.addEventListener('click', function () {
                const detailId = this.dataset.detailId;
                const cardBody = this.closest('.card-body');
                const itemName = cardBody.querySelector('.card-title').innerText.split('(')[0].trim();
                const itemKode = cardBody.querySelector('.card-title .text-muted').innerText.replace(/[()]/g, '');

                if(returnFormOp) returnFormOp.action = `{{ url(Auth::user()->hasRole(App\Models\User::ROLE_ADMIN) ? 'admin' : 'operator') }}/peminjaman/detail/${detailId}/return`;
                if(returnItemNameOp) returnItemNameOp.textContent = itemName;
                if(returnItemKodeOp) returnItemKodeOp.textContent = itemKode;
                if(kondisiSetelahKembaliSelectOp) kondisiSetelahKembaliSelectOp.value = 'Baik'; // Reset ke default
                if(returnModalOp) returnModalOp.show();
            });
        });
         if(returnFormOp) {
            returnFormOp.addEventListener('submit', function(event) {
                event.preventDefault();
                handleFormSubmit(this, returnModalOp);
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
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
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
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    let errorMessages = data.message || 'Terjadi kesalahan.';
                    if (data.errors) {
                        errorMessages = Object.values(data.errors).map(err => err.join('<br>')).join('<br>');
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        html: errorMessages,
                    });
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Terjadi kesalahan saat mengirim data ke server.',
                });
            });
        }

    });
</script>
@endpush
