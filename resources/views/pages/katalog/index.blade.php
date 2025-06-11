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

        {{-- Filter Pencarian & Dropdown --}}
        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('guru.katalog.index') }}" method="GET">
                    <div class="row g-3 align-items-end">
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
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" type="submit">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Daftar Barang dalam bentuk Card --}}
        <div class="row">
            @forelse ($barangTersedia as $item)
                <div class="col-xl-3 col-md-4 col-sm-6">
                    <div class="card">
                        {{-- Di sini Anda bisa menambahkan gambar barang jika ada --}}
                        {{-- <img class="card-img-top img-fluid" src="..." alt="Card image cap"> --}}
                        <div class="card-body">
                            <h5 class="card-title">{{ $item->barang->nama_barang }}</h5>
                            <p class="card-text text-muted">{{ $item->barang->merk_model ?? 'Tidak ada merk' }}</p>
                            <p class="card-text">
                                <strong>Kode Unit:</strong> {{ $item->kode_inventaris_sekolah }}<br>
                                <strong>Lokasi:</strong> {{ $item->ruangan->nama_ruangan ?? 'N/A' }}<br>
                                <strong>Kondisi:</strong> <span
                                    class="badge {{ \App\Models\BarangQrCode::getKondisiColor($item->kondisi) }}">{{ $item->kondisi }}</span>
                            </p>
                            <a href="{{ route('guru.peminjaman.create', ['id_barang_qr_code' => $item->id]) }}"
                                class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-plus-circle me-1"></i> Ajukan Pinjam
                            </a>
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
