@extends('layouts.app')

@section('title', 'Katalog Barang Tersedia')

@section('content')
    <div class="container-fluid">
        {{-- Page Title & Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Katalog Barang Tersedia</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('guru.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Katalog Barang</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- PESAN JIKA KERANJANG TERKUNCI PADA RUANGAN TERTENTU --}}
        @if ($ruanganTerkunci)
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-lock me-2"></i>
                    Keranjang Anda saat ini terkunci untuk item dari ruangan:
                    <strong>{{ $ruanganTerkunci->nama_ruangan }}</strong>.
                    Anda hanya dapat menambahkan barang lain dari ruangan yang sama.
                </div>
                {{-- Tombol untuk mengosongkan keranjang --}}
                {{-- Ganti form lama dengan yang ini di halaman katalog --}}
                <form id="form-reset-katalog" action="{{ route('guru.keranjang.reset') }}" method="POST">
                    @csrf
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                        data-bs-target="#universalConfirmModal" data-message="Anda yakin ingin mengosongkan keranjang?"
                        data-form-id="form-reset-katalog">
                        Reset Keranjang
                    </button>
                </form>
            </div>
        @endif

        {{-- Filter Pencarian & Dropdown --}}
        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('guru.katalog.index') }}" method="GET">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Nama Barang / Merk</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="Cari..."
                                value="{{ $request->search }}">
                        </div>
                        <div class="col-md-3">
                            <label for="id_kategori" class="form-label">Kategori</label>
                            <select name="id_kategori" id="id_kategori" class="form-select">
                                <option value="">-- Semua Kategori --</option>
                                @foreach ($kategoriList as $kategori)
                                    <option value="{{ $kategori->id }}" @selected(request('id_kategori') == $kategori->id)>
                                        {{ $kategori->nama_kategori }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="id_ruangan" class="form-label">Lokasi</label>
                            <select name="id_ruangan" id="id_ruangan" class="form-select">
                                <option value="">-- Semua Lokasi --</option>
                                @foreach ($ruanganList as $ruangan)
                                    <option value="{{ $ruangan->id }}" @selected(request('id_ruangan') == $ruangan->id)>
                                        {{ $ruangan->nama_ruangan }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-primary w-100" type="submit">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tombol Aksi Lanjutan --}}
        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('guru.peminjaman.create') }}"
                class="btn btn-success @if ($jumlahDiKeranjang == 0) disabled @endif">
                <i class="fas fa-arrow-right me-2"></i>
                Lanjutkan ke Halaman Pengajuan ({{ $jumlahDiKeranjang }} Barang)
            </a>
        </div>

        {{-- Daftar Barang dalam bentuk Card --}}
        <div class="row">
            @forelse ($barangTersedia as $item)
                <div class="col-xl-3 col-md-4 col-sm-6 mb-4">
                    {{-- PENYEMPURNAAN TAMPILAN CARD --}}
                    <div class="card h-100 shadow-sm">
                        <div class="bg-light text-center p-3">
                            {{-- Ganti dengan gambar jika ada, jika tidak gunakan ikon --}}
                            {{-- <i class="fas fa-laptop-code fa-4x text-muted"></i> --}}
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-truncate">{{ $item->barang->nama_barang }}</h5>
                            <p class="card-text text-muted small mb-2 text-truncate">
                                {{ $item->barang->merk_model ?? 'Tidak ada merk' }}</p>

                            <div class="mt-auto">
                                <p class="card-text small mb-2">
                                    <i class="fas fa-hashtag fa-fw me-2 text-muted"></i><strong>Kode:</strong>
                                    {{ $item->kode_inventaris_sekolah }}<br>
                                    <i class="fas fa-map-marker-alt fa-fw me-2 text-muted"></i><strong>Lokasi:</strong>
                                    {{ $item->ruangan->nama_ruangan ?? 'N/A' }}<br>
                                </p>
                                <p class="mb-3">
                                    <i class="fas fa-check-circle fa-fw me-2 text-muted"></i><strong>Kondisi:</strong>
                                    <span
                                        class="badge {{ \App\Models\BarangQrCode::getKondisiColor($item->kondisi) }}">{{ $item->kondisi }}</span>
                                </p>

                                <form action="{{ route('guru.keranjang.tambah') }}" method="POST" class="d-grid">
                                    @csrf
                                    <input type="hidden" name="id_barang_qr_code" value="{{ $item->id }}">
                                    <button type="submit" class="btn btn-primary"
                                        @if ($ruanganTerkunci && $item->id_ruangan !== $ruanganTerkunci->id) disabled 
                                        title="Barang ini tidak dapat ditambahkan karena berbeda ruangan dengan keranjang Anda." @endif>

                                        <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        Tidak ada barang yang tersedia untuk dipinjam saat ini, atau tidak ada yang cocok dengan pencarian
                        Anda.
                    </div>
                </div>
            @endforelse
        </div>

        {{-- Pagination Links --}}
        <div class="d-flex justify-content-center">
            {{ $barangTersedia->links() }}
        </div>
    </div>
@endsection
