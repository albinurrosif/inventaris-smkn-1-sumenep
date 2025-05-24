@extends('layouts.auth')

@section('title')
    Login - Sistem Inventaris Sekolah
@endsection

@section('content')
    <div class="auth-page">
        <div class="container-fluid p-0">
            <div class="row g-0">
                <!-- Left side (form) -->
                <div class="col-xxl-4 col-lg-5 col-md-6">
                    <div class="auth-full-page-content d-flex p-sm-5 p-4">
                        <div class="w-100">
                            <div class="d-flex flex-column h-100">
                                <div class="mb-4 mb-md-5 text-center">
                                    <a href="{{ route('redirect-dashboard') }}" class="d-block auth-logo">
                                        <img src="{{ asset('assets/images/Logo-SMKN_1_Sumenep-removebg-preview.png') }}"
                                            alt="logo-sm" height="100">
                                        <span class="logo-txt display-6 fw-bold text-primary">Sistem Inventaris</span>
                                        <p class="text-muted mt-1">SMKN 1 Sumenep</p>
                                    </a>
                                </div>

                                <div class="card shadow-lg border-0" style="border-radius: 15px;">
                                    <div class="card-body p-4">
                                        <div class="text-center mb-4">
                                            <h4 class="fw-bold text-dark">Masuk ke Sistem</h4>
                                            <p class="text-muted">Akses terbatas untuk staf inventaris sekolah</p>
                                        </div>
                                        <form class="mt-4" method="POST" action="{{ route('login') }}">
                                            @csrf
                                            <div class="mb-4">
                                                <label for="email" class="form-label fw-medium">Email Institusi</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0">
                                                        <i class="mdi mdi-email-outline text-muted"></i>
                                                    </span>
                                                    <input type="email" name="email"
                                                        class="form-control @error('email') is-invalid @enderror"
                                                        value="{{ old('email') }}" placeholder="email@smkn1sumenep.sch.id"
                                                        required autofocus>
                                                </div>
                                                @error('email')
                                                    <span class="text-danger small">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="mb-4">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label for="password" class="form-label fw-medium">Password</label>
                                                    {{-- <a href="{{ route('password.request') }}" class="text-muted small">Lupa
                                                        Password?</a> --}}
                                                </div>
                                                <div class="input-group auth-pass-inputgroup">
                                                    <span class="input-group-text bg-light border-end-0">
                                                        <i class="mdi mdi-lock-outline text-muted"></i>
                                                    </span>
                                                    <input type="password" name="password"
                                                        class="form-control @error('password') is-invalid @enderror"
                                                        placeholder="Minimal 8 karakter" required>
                                                    <button class="btn btn-light shadow-none" type="button"
                                                        id="password-addon">
                                                        <i class="mdi mdi-eye-outline"></i>
                                                    </button>
                                                </div>
                                                @error('password')
                                                    <span class="text-danger small">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="row mb-4">
                                                <div class="col">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="remember"
                                                            id="remember-check" {{ old('remember') ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="remember-check">
                                                            Ingat perangkat ini
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <button class="btn btn-primary w-100 py-2 fw-medium" type="submit">
                                                    <i class="mdi mdi-login me-1"></i> Masuk
                                                </button>
                                            </div>
                                        </form>

                                        {{-- <div class="mt-4 pt-3 text-center">
                                            <p class="text-muted mb-0">Butuh bantuan?
                                                <a href="mailto:it@smkn1sumenep.sch.id"
                                                    class="text-primary fw-medium">Hubungi Admin</a>
                                            </p>
                                        </div> --}}
                                    </div>
                                </div>

                                <div class="mt-4 mt-md-5 text-center">
                                    <p class="mb-0 text-muted">
                                        <small>© {{ date('Y') }} Sistem Inventaris SMKN 1 Sumenep</small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right side (visual) -->
                <div class="col-xxl-8 col-lg-7 col-md-6">
                    <div class="auth-bg pt-md-5 p-4 d-flex align-items-center justify-content-center">
                        <div class="bg-overlay"
                            style="background: linear-gradient(135deg, #3a7bd5 0%, #00d2ff 100%); opacity: 0.9;"></div>
                        <div class="w-100 text-center position-relative px-4">
                            <img src="{{ asset('assets/images/inventory-illustration.png') }}" alt="Inventory System"
                                class="img-fluid"
                                style="max-height: 320px; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1))">
                            <h3 class="text-white mt-4 fw-bold">Manajemen Aset Sekolah</h3>
                            <p class="text-white-75 mb-0">Pantau, kelola, dan optimalkan penggunaan inventaris sekolah</p>

                            {{-- <div class="d-flex justify-content-center mt-5">
                                <div class="pe-4 border-end border-white-50">
                                    <h2 class="text-white fw-bold">100%</h2>
                                    <p class="text-white-75 mb-0">Akuntabilitas</p>
                                </div>
                                <div class="px-4 border-end border-white-50">
                                    <h2 class="text-white fw-bold">24/7</h2>
                                    <p class="text-white-75 mb-0">Akses Data</p>
                                </div>
                                <div class="ps-4">
                                    <h2 class="text-white fw-bold">100+</h2>
                                    <p class="text-white-75 mb-0">Aset Terkelola</p>
                                </div>
                            </div> --}}
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
            // Password visibility toggle
            const passwordInput = document.querySelector('input[name="password"]');
            const passwordAddon = document.getElementById('password-addon');

            if (passwordAddon) {
                passwordAddon.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('mdi-eye-outline');
                    this.querySelector('i').classList.toggle('mdi-eye-off-outline');
                });
            }

            // Add animation to login button
            const loginBtn = document.querySelector('button[type="submit"]');
            if (loginBtn) {
                loginBtn.addEventListener('click', function() {
                    this.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Memproses...';
                });
            }
        });
    </script>
@endpush
