@extends('layouts.auth')

@section('title', 'Login - Sistem Inventaris Sekolah')

@section('content')
    <div class="auth-page">
        <div class="container-fluid d-flex align-items-center justify-content-center min-vh-100 p-0">
            <div class="row g-0 justify-content-center w-100">
                <div class="col-xxl-3 col-lg-4 col-md-6">
                    <div class="d-flex flex-column h-100">

                        {{-- Logo dan Judul --}}
                        <div class="mb-4 text-center">
                            <a href="{{ url('/') }}" class="d-block auth-logo">
                                <img src="{{ asset('assets/images/Logo-SMKN_1_Sumenep-removebg-preview.png') }}"
                                    alt="logo-smkn1" height="100">
                                <h4 class="logo-txt fw-bold text-primary mt-2">Sistem Inventaris</h4>
                                <p class="text-muted mt-1">SMKN 1 Sumenep</p>
                            </a>
                        </div>

                        {{-- Form Card --}}
                        <div class="card shadow-lg border-0" style="border-radius: 15px;">
                            <div class="card-body p-4 p-sm-5">
                                <div class="text-center mb-4">
                                    <h5 class="fw-bold text-dark">Selamat Datang Kembali</h5>
                                    <p class="text-muted">Silakan masuk untuk melanjutkan.</p>
                                </div>

                                <form class="mt-4" method="POST" action="{{ route('login') }}">
                                    @csrf

                                    {{-- Menampilkan error jika ada --}}
                                    @if ($errors->any())
                                        <div class="alert alert-danger p-2 small">
                                            {{ $errors->first() }}
                                        </div>
                                    @endif

                                    <div class="mb-3">
                                        <label for="email" class="form-label fw-medium">Email Institusi</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i
                                                    class="mdi mdi-email-outline text-muted"></i></span>
                                            <input type="email" name="email" class="form-control"
                                                value="{{ old('email') }}" placeholder="email@smkn1sumenep.sch.id" required
                                                autofocus>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="password" class="form-label fw-medium">Password</label>
                                        <div class="input-group auth-pass-inputgroup">
                                            <span class="input-group-text bg-light border-end-0"><i
                                                    class="mdi mdi-lock-outline text-muted"></i></span>
                                            <input type="password" name="password" class="form-control"
                                                placeholder="Masukkan password" required>
                                            <button class="btn btn-light shadow-none" type="button" id="password-addon"><i
                                                    class="mdi mdi-eye-outline"></i></button>
                                        </div>
                                    </div>

                                    {{-- Tombol Login --}}
                                    <div class="mt-4">
                                        <button class="btn btn-primary w-100 py-2 fw-medium" type="submit">
                                            <i class="mdi mdi-login me-1"></i> Masuk
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- Copyright Footer --}}
                        <div class="mt-5 text-center">
                            <p class="mb-0 text-muted">
                                <small>© {{ date('Y') }} Sistem Inventaris Aset. Dikembangkan untuk SMKN 1
                                    Sumenep.</small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.querySelector('input[name="password"]');
            const passwordAddon = document.getElementById('password-addon');

            if (passwordAddon) {
                passwordAddon.addEventListener('click', function() {
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        this.querySelector('i').classList.replace('mdi-eye-outline', 'mdi-eye-off-outline');
                    } else {
                        passwordInput.type = 'password';
                        this.querySelector('i').classList.replace('mdi-eye-off-outline', 'mdi-eye-outline');
                    }
                });
            }
        });
    </script>
@endpush
