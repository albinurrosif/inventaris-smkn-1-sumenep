@php
    use App\Models\User;
@endphp

@extends('layouts.app')

@section('title', 'Buat Sesi Stok Opname Baru')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endpush

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Buat Sesi Stok Opname Baru</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.stok-opname.index') }}">Stok Opname</a>
                            </li>
                            <li class="breadcrumb-item active">Buat Sesi Baru</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.stok-opname.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informasi Sesi Stok Opname</h5>
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
                                <label for="id_ruangan" class="form-label">Ruangan <span
                                        class="text-danger">*</span></label>
                                <select class="form-select select2-basic @error('id_ruangan') is-invalid @enderror"
                                    id="id_ruangan" name="id_ruangan" required>
                                    <option value="">-- Pilih Ruangan --</option>
                                    @foreach ($ruanganList as $ruangan)
                                        <option value="{{ $ruangan->id }}"
                                            {{ old('id_ruangan', '') == $ruangan->id ? 'selected' : '' }}>
                                            {{ $ruangan->nama_ruangan }} ({{ $ruangan->kode_ruangan }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_ruangan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="tanggal_opname" class="form-label">Tanggal Pelaksanaan <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('tanggal_opname') is-invalid @enderror"
                                    id="tanggal_opname" name="tanggal_opname"
                                    value="{{ old('tanggal_opname', date('Y-m-d')) }}" required>
                                @error('tanggal_opname')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            @if (Auth::user()->hasRole(User::ROLE_ADMIN) && $operatorPelaksanaList->isNotEmpty())
                                <div class="mb-3">
                                    <label for="id_operator_pelaksana" class="form-label">Operator Pelaksana</label>
                                    <select
                                        class="form-select select2-basic @error('id_operator_pelaksana') is-invalid @enderror"
                                        id="id_operator_pelaksana" name="id_operator_pelaksana">
                                        <option value="">-- Pilih Operator --</option>
                                        @foreach ($operatorPelaksanaList as $operator)
                                            <option value="{{ $operator->id }}"
                                                {{ old('id_operator_pelaksana', '') == $operator->id ? 'selected' : '' }}>
                                                {{ $operator->username }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('id_operator_pelaksana')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Kosongkan jika Anda sebagai pelaksana.</small>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label for="catatan" class="form-label">Catatan Tambahan</label>
                                <textarea class="form-control @error('catatan') is-invalid @enderror" id="catatan" name="catatan" rows="3">{{ old('catatan', '') }}</textarea>
                                @error('catatan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <a href="{{ route('admin.stok-opname.index') }}" class="btn btn-outline-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i> Buat &
                                Lanjutkan</button>
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
