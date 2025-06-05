<div class="dl-horizontal">
    <div class="row mb-3">
        <div class="col-md-12">
            <h5 class="mb-3">
                Detail Log untuk Unit:
                @if ($log->barangQrCode)
                    <a href="{{ route('barang-qr-code.show', $log->barangQrCode->id) }}" target="_blank">
                        <code>{{ $log->barangQrCode->kode_inventaris_sekolah ?? 'N/A' }}</code>
                        ({{ $log->barangQrCode->barang->nama_barang ?? 'N/A' }})
                    </a>
                @else
                    N/A
                @endif
            </h5>
        </div>
    </div>

    <div class="row mb-2">
        <dt class="col-sm-4">ID Log Status</dt>
        <dd class="col-sm-8">{{ $log->id }}</dd>
    </div>

    <div class="row mb-2">
        <dt class="col-sm-4">Tanggal Pencatatan</dt>
        <dd class="col-sm-8">
            {{ \Carbon\Carbon::parse($log->tanggal_pencatatan)->isoFormat('dddd, DD MMMM YYYY HH:mm:ss') }}
        </dd>
    </div>

    <div class="row mb-2">
        <dt class="col-sm-4">Deskripsi Kejadian</dt>
        <dd class="col-sm-8">{{ $log->deskripsi_kejadian }}</dd>
    </div>

    <hr class="my-3">

    <div class="row mb-2">
        <dt class="col-sm-4">Kondisi Sebelumnya</dt>
        <dd class="col-sm-8">
            <span class="badge bg-light text-dark modal-detail-badge">{{ $log->kondisi_sebelumnya ?? '-' }}</span>
        </dd>
    </div>

    <div class="row mb-2">
        <dt class="col-sm-4">Kondisi Sesudahnya</dt>
        <dd class="col-sm-8">
            <span class="badge bg-info modal-detail-badge">{{ $log->kondisi_sesudahnya ?? '-' }}</span>
        </dd>
    </div>

    <hr class="my-3">

    <div class="row mb-2">
        <dt class="col-sm-4">Status Ketersediaan Sebelumnya</dt>
        <dd class="col-sm-8">
            <span
                class="badge bg-light text-dark modal-detail-badge">{{ $log->status_ketersediaan_sebelumnya ?? '-' }}</span>
        </dd>
    </div>

    <div class="row mb-2">
        <dt class="col-sm-4">Status Ketersediaan Sesudahnya</dt>
        <dd class="col-sm-8">
            <span class="badge bg-primary modal-detail-badge">{{ $log->status_ketersediaan_sesudahnya ?? '-' }}</span>
        </dd>
    </div>

    <hr class="my-3">

    <div class="row mb-2">
        <dt class="col-sm-4">Ruangan Sebelumnya</dt>
        <dd class="col-sm-8">
            @if ($log->ruanganSebelumnya)
                <span class="badge bg-secondary modal-detail-badge">{{ $log->ruanganSebelumnya->nama_ruangan }}</span>
            @else
                -
            @endif
        </dd>
    </div>

    <div class="row mb-2">
        <dt class="col-sm-4">Ruangan Sesudahnya</dt>
        <dd class="col-sm-8">
            @if ($log->ruanganSesudahnya)
                <span class="badge bg-secondary modal-detail-badge">{{ $log->ruanganSesudahnya->nama_ruangan }}</span>
            @else
                -
            @endif
        </dd>
    </div>

    <hr class="my-3">

    <div class="row mb-2">
        <dt class="col-sm-4">Pemegang Personal Sebelumnya</dt>
        <dd class="col-sm-8">
            @if ($log->pemegangPersonalSebelumnya)
                <span class="badge bg-warning text-dark modal-detail-badge">P:
                    {{ $log->pemegangPersonalSebelumnya->username }}</span>
            @else
                -
            @endif
        </dd>
    </div>

    <div class="row mb-2">
        <dt class="col-sm-4">Pemegang Personal Sesudahnya</dt>
        <dd class="col-sm-8">
            @if ($log->pemegangPersonalSesudahnya)
                <span class="badge bg-warning text-dark modal-detail-badge">P:
                    {{ $log->pemegangPersonalSesudahnya->username }}</span>
            @else
                -
            @endif
        </dd>
    </div>

    <hr class="my-3">

    <div class="row mb-2">
        <dt class="col-sm-4">Dicatat Oleh</dt>
        <dd class="col-sm-8">{{ $log->userPencatat->username ?? '-' }}</dd>
    </div>

    <div class="row mb-2">
        <dt class="col-sm-4">ID Peminjaman Terkait</dt>
        <dd class="col-sm-8">{{ $log->id_peminjaman ?? '-' }}</dd>
    </div>

    <div class="row mb-2">
        <dt class="col-sm-4">ID Pemeliharaan Terkait</dt>
        <dd class="col-sm-8">{{ $log->id_pemeliharaan ?? '-' }}</dd>
    </div>

    <div class="row mb-2">
        <dt class="col-sm-4">ID Stok Opname Terkait</dt>
        <dd class="col-sm-8">{{ $log->id_stok_opname ?? '-' }}</dd>
    </div>

    <div class="row mb-2">
        <dt class="col-sm-4">ID Mutasi Terkait</dt>
        <dd class="col-sm-8">{{ $log->id_mutasi ?? '-' }}</dd>
    </div>
</div>
