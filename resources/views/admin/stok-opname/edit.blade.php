@php
    use App\Models\User;
    use App\Models\StokOpname;
@endphp

@extends('layouts.app')

@section('title', 'Edit Sesi Stok Opname')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section('content')
    <div class="container-fluid">
        <form action="{{ route('admin.stok-opname.update', $stokOpname->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Edit Informasi Sesi Stok Opname</h5>
                        </div>
                        <div class="card-body">
                            @if (session('error'))
                                <div class="alert alert-danger">{{ session('error') }}</div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <p class="fw-bold"><i class="fas fa-exclamation-triangle me-1"></i>Terjadi kesalahan:
                                    </p>
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">Ruangan</label>
                                <input type="text" class="form-control"
                                    value="{{ $stokOpname->ruangan->nama_ruangan }} ({{ $stokOpname->ruangan->kode_ruangan }})"
                                    readonly>
                            </div>

                            <div class="mb-3">
                                <label for="tanggal_opname" class="form-label">Tanggal Pelaksanaan <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('tanggal_opname') is-invalid @enderror"
                                    id="tanggal_opname" name="tanggal_opname"
                                    value="{{ old('tanggal_opname', $stokOpname->tanggal_opname->format('Y-m-d')) }}"
                                    {{ $stokOpname->status !== StokOpname::STATUS_DRAFT ? 'readonly disabled' : '' }}>
                                @error('tanggal_opname')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if (Auth::user()->hasRole(User::ROLE_ADMIN))
                                <div class="mb-3">
                                    <label for="id_operator_pelaksana" class="form-label">Operator Pelaksana</label>
                                    <select
                                        class="form-select select2-basic @error('id_operator_pelaksana') is-invalid @enderror"
                                        id="id_operator_pelaksana" name="id_operator_pelaksana"
                                        {{ $stokOpname->status !== StokOpname::STATUS_DRAFT ? 'disabled' : '' }}>
                                        <option value="">-- Pilih Operator --</option>
                                        @foreach ($operatorPelaksanaList as $operator)
                                            <option value="{{ $operator->id }}"
                                                {{ old('id_operator_pelaksana', $stokOpname->id_operator) == $operator->id ? 'selected' : '' }}>
                                                {{ $operator->username }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('id_operator_pelaksana')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @else
                                <div class="mb-3">
                                    <label class="form-label">Operator Pelaksana</label>
                                    <input type="text" class="form-control"
                                        value="{{ $stokOpname->operator->username }}" readonly>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label for="catatan" class="form-label">Catatan Tambahan</label>
                                <textarea class="form-control @error('catatan') is-invalid @enderror" id="catatan" name="catatan" rows="3"
                                    {{ $stokOpname->status !== StokOpname::STATUS_DRAFT ? 'readonly disabled' : '' }}>{{ old('catatan', $stokOpname->catatan ?? '') }}</textarea>
                                @error('catatan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if ($stokOpname->status !== StokOpname::STATUS_DRAFT)
                                <div class="alert alert-warning">Sesi ini sudah <strong>{{ $stokOpname->status }}</strong>
                                    dan tidak dapat diedit lagi.</div>
                            @endif
                        </div>

                        <div class="card-footer text-end">
                            <a href="{{ route('admin.stok-opname.show', $stokOpname->id) }}"
                                class="btn btn-outline-secondary">Batal</a>
                            @if ($stokOpname->status === StokOpname::STATUS_DRAFT)
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan
                                    Perubahan</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(function() {
            $('.select2-basic').select2({
                theme: "bootstrap-5",
                width: '100%',
                placeholder: "-- Pilih --",
                allowClear: true
            });

            @if ($errors->any())
                const firstInvalid = document.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    firstInvalid.focus();
                }
            @endif
        });
    </script>
@endpush
