@extends('layouts.app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('title', $pageTitle ?? 'Transfer Pemegang Personal')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ $pageTitle ?? 'Transfer Pemegang Personal Unit Barang' }}</h3>
                    </div>
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <div class="row mb-3">
                            <div class="col-md-8"> {{-- Bisa dilebarkan sedikit jika perlu --}}
                                <h5>Detail Unit Barang:</h5>
                                <table class="table table-sm table-bordered"> {{-- table-sm untuk lebih ringkas --}}
                                    <tr>
                                        <th>Kode Unit</th>
                                        <td>{{ $barangQrCode->kode_inventaris_sekolah ?? 'N/A' }}</td>
                                        <th>Nomor Seri</th>
                                        <td>{{ $barangQrCode->no_seri_pabrik ?? '-' }}</td>
                                    </tr>

                                    <tr>
                                        <th>Nama Barang</th>
                                        <td colspan="3">{{ $barangQrCode->barang->nama_barang ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Merk/Model</th>
                                        <td colspan="3">{{ $barangQrCode->barang->merk_model ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Kategori</th>
                                        <td colspan="3">{{ $barangQrCode->barang->kategori->nama_kategori ?? 'N/A' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Lokasi Saat Ini</th>
                                        <td>
                                            @if ($barangQrCode->id_pemegang_personal)
                                                {{ $barangQrCode->pemegangPersonal->username ?? 'N/A' }} (Personal)
                                            @elseif($barangQrCode->id_ruangan)
                                                {{ $barangQrCode->ruangan->nama_ruangan ?? 'N/A' }}
                                                ({{ $barangQrCode->ruangan->kode_ruangan ?? '' }})
                                            @else
                                                Tidak di Ruangan & Tidak Dipegang Personal
                                            @endif
                                        </td>
                                        <th>Kondisi</th>
                                        <td>{{ $barangQrCode->kondisi }}</td> {{-- Asumsi 'kondisi' sudah cukup jelas, jika ada 'kondisi_formatted' bisa dipakai --}}
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <form action="{{ route('barang-qr-code.transfer-personal', $barangQrCode->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="new_id_pemegang_personal">Pilih Pemegang Personal Baru <span
                                        class="text-danger">*</span></label>
                                <select name="new_id_pemegang_personal" id="new_id_pemegang_personal"
                                    class="form-control @error('new_id_pemegang_personal') is-invalid @enderror" required>
                                    <option value="">-- Pilih Pengguna Baru --</option>
                                    @forelse($users as $user)
                                        <option value="{{ $user->id }}"
                                            {{ old('new_id_pemegang_personal') == $user->id ? 'selected' : '' }}>
                                            {{ $user->username }} ({{ $user->role }})
                                        </option>
                                    @empty
                                        <option value="" disabled>Tidak ada pengguna lain yang bisa dipilih.</option>
                                    @endforelse
                                </select>
                                @error('new_id_pemegang_personal')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="catatan_transfer_personal">Catatan Transfer (Opsional)</label>
                                <textarea name="catatan_transfer_personal" id="catatan_transfer_personal"
                                    class="form-control @error('catatan_transfer_personal') is-invalid @enderror" rows="3">{{ old('catatan_transfer_personal') }}</textarea>
                                @error('catatan_transfer_personal')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex mt-3 justify-content-between">
                                <button type="submit" class="btn btn-primary"
                                    @if ($users->isEmpty()) disabled @endif>Transfer ke Pemegang Baru</button>
                                <a href="{{ route('barang-qr-code.show', $barangQrCode->id) }}"
                                    class="btn btn-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
@endsection

@push('scripts')
    {{-- Script untuk Select2 jika digunakan --}}
    <script>
        $(document).ready(function() {
            $('#new_id_pemegang_personal').select2({
                placeholder: "-- Pilih Pengguna Baru --",
                allowClear: true
            });
        });
    </script>
@endpush
