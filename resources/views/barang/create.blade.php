@extends('layouts.app')

@section('title', 'Tambah Barang')

@section('content')

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Tambah Barang</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('barang.index') }}">Barang</a></li>
                            <li class="breadcrumb-item active">Tambah</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('barang.store') }}" method="POST">
                            @csrf

                            {{-- Nama Barang --}}
                            <div class="mb-3">
                                <label class="form-label">Nama Barang</label>
                                <input type="text" name="nama_barang" class="form-control"
                                    value="{{ old('nama_barang') }}" required>
                            </div>

                            {{-- Kode Barang --}}
                            <div class="mb-3">
                                <label class="form-label">Kode Barang</label>
                                <input type="text" name="kode_barang" class="form-control"
                                    value="{{ old('kode_barang') }}" required>
                            </div>

                            {{-- Merk / Model --}}
                            <div class="mb-3">
                                <label class="form-label">Merk / Model</label>
                                <input type="text" name="merk_model" class="form-control"
                                    value="{{ old('merk_model') }}">
                            </div>

                            {{-- Nomor Seri Pabrik --}}
                            <div class="mb-3">
                                <label class="form-label">No Seri Pabrik</label>
                                <input type="text" name="no_seri_pabrik" class="form-control"
                                    value="{{ old('no_seri_pabrik') }}">
                            </div>

                            {{-- Ukuran --}}
                            <div class="mb-3">
                                <label class="form-label">Ukuran</label>
                                <input type="text" name="ukuran" class="form-control" value="{{ old('ukuran') }}">
                            </div>

                            {{-- Bahan --}}
                            <div class="mb-3">
                                <label class="form-label">Bahan</label>
                                <input type="text" name="bahan" class="form-control" value="{{ old('bahan') }}">
                            </div>

                            {{-- Tahun Pembuatan / Pembelian --}}
                            <div class="mb-3">
                                <label class="form-label">Tahun Pembuatan/Pembelian</label>
                                <input type="number" name="tahun_pembuatan_pembelian" class="form-control"
                                    value="{{ old('tahun_pembuatan_pembelian') }}" min="1900"
                                    max="{{ date('Y') }}">
                            </div>

                            {{-- Jumlah --}}
                            <div class="mb-3">
                                <label class="form-label">Jumlah</label>
                                <input type="number" name="jumlah_barang" class="form-control"
                                    value="{{ old('jumlah_barang', 1) }}" required>
                            </div>

                            {{-- Harga Beli --}}
                            <div class="mb-3">
                                <label class="form-label">Harga Beli</label>
                                <input type="number" name="harga_beli" step="0.01" class="form-control"
                                    value="{{ old('harga_beli') }}">
                            </div>

                            {{-- Sumber --}}
                            <div class="mb-3">
                                <label class="form-label">Sumber</label>
                                <input type="text" name="sumber" class="form-control" value="{{ old('sumber') }}">
                            </div>

                            {{-- Keadaan Barang --}}
                            <div class="mb-3">
                                <label class="form-label">Keadaan Barang</label>
                                <select name="keadaan_barang" class="form-select" required>
                                    <option value="">-- Pilih Keadaan --</option>
                                    <option value="Baik" {{ old('keadaan_barang') == 'Baik' ? 'selected' : '' }}>Baik
                                    </option>
                                    <option value="Kurang Baik"
                                        {{ old('keadaan_barang') == 'Kurang Baik' ? 'selected' : '' }}>Kurang Baik</option>
                                    <option value="Rusak Berat"
                                        {{ old('keadaan_barang') == 'Rusak Berat' ? 'selected' : '' }}>Rusak Berat</option>
                                </select>
                            </div>

                            {{-- Keterangan Mutasi --}}
                            <div class="mb-3">
                                <label class="form-label">Keterangan Mutasi</label>
                                <textarea name="keterangan_mutasi" class="form-control" rows="2">{{ old('keterangan_mutasi') }}</textarea>
                            </div>

                            {{-- Ruangan --}}
                            <div class="mb-3">
                                <label class="form-label">Ruangan</label>
                                <select name="id_ruangan" class="form-select" required>
                                    <option value="">-- Pilih Ruangan --</option>
                                    @foreach ($ruangan as $r)
                                        <option value="{{ $r->id }}"
                                            {{ old('id_ruangan') == $r->id ? 'selected' : '' }}>{{ $r->nama_ruangan }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Tombol --}}
                            <div class="d-flex justify-content-end">
                                <a href="{{ route('barang.index') }}" class="btn btn-secondary me-2">Batal</a>
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
</div> @endsection
