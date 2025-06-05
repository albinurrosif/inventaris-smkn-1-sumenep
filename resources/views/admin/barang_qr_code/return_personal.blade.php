@extends('layouts.app') {{-- Sesuaikan dengan layout admin Anda --}}

@section('title', $pageTitle ?? 'Kembalikan Unit ke Ruangan')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ $pageTitle ?? 'Kembalikan Unit dari Personal ke Ruangan' }}</h3>
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

                        <form action="{{ route('barang-qr-code.return-personal', $barangQrCode->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="id_ruangan_tujuan">Pilih Ruangan Tujuan <span
                                        class="text-danger">*</span></label>
                                <select name="id_ruangan_tujuan" id="id_ruangan_tujuan"
                                    class="form-control @error('id_ruangan_tujuan') is-invalid @enderror" required>
                                    <option value="">-- Pilih Ruangan --</option>
                                    @forelse($ruangans as $ruangan)
                                        <option value="{{ $ruangan->id }}"
                                            {{ old('id_ruangan_tujuan') == $ruangan->id ? 'selected' : '' }}>
                                            {{ $ruangan->nama_ruangan }}
                                            ({{ $ruangan->kode_ruangan ?? '' }})
                                        </option>
                                    @empty
                                        <option value="" disabled>Tidak ada ruangan yang bisa dipilih.</option>
                                    @endforelse
                                </select>
                                @error('id_ruangan_tujuan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="catatan_pengembalian_ruangan">Catatan Pengembalian (Opsional)</label>
                                <textarea name="catatan_pengembalian_ruangan" id="catatan_pengembalian_ruangan"
                                    class="form-control @error('catatan_pengembalian_ruangan') is-invalid @enderror" rows="3">{{ old('catatan_pengembalian_ruangan') }}</textarea>
                                @error('catatan_pengembalian_ruangan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex mt-3 justify-content-between">
                                <button type="submit" class="btn btn-primary "
                                    @if ($ruangans->isEmpty()) disabled @endif>Kembalikan ke Ruangan</button>
                                <a href="{{ route('barang-qr-code.show', $barangQrCode->id) }}"
                                    class="btn btn-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- resources/views/admin/layouts/app.blade.php (atau halaman relevan) --}}
    <div class="modal fade" id="actionModal" tabindex="-1" role="dialog" aria-labelledby="actionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document"> {{-- modal-lg untuk form yang mungkin lebih lebar --}}
            <div class="modal-content">
                {{-- Konten akan dimuat di sini via AJAX --}}
                <div class="modal-body text-center">
                    <i class="fas fa-spinner fa-spin fa-3x"></i>
                    <p>Memuat formulir...</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Jika Anda menggunakan select2 atau plugin JS lain untuk select --}}
    <script>
        $(document).ready(function() {
            $('#id_ruangan_tujuan').select2({
                placeholder: "-- Pilih Ruangan --",
                allowClear: true
            });
        });
    </script>
@endpush
