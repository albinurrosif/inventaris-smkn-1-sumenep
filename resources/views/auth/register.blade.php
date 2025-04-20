@extends('layouts.auth')

@section('title', 'Register')

@section('content')
    <div class="row g-0">
        <div class="col-xxl-3 col-lg-4 col-md-5">
            <div class="auth-full-page-content d-flex p-sm-5 p-4">
                <div class="w-100">
                    <div class="d-flex flex-column h-100">
                        <div class="mb-4 mb-md-5 text-center">
                            <a href="{{ route('dashboard') }}" class="d-block auth-logo">
                                <img src="{{ asset('assets/images/logo-sm.svg') }}" alt="" height="28"> <span
                                    class="logo-txt">SMKN 1 Sumenep</span>
                            </a>
                        </div>
                        <div class="auth-content my-auto">
                            <div class="text-center">
                                <h5 class="mb-0">Register Akun</h5>
                                <p class="text-muted mt-2">Buat akun baru untuk Aplikasi Inventaris Barang Sekolah.</p>
                            </div>

                            <!-- Form Register dari Laravel Breeze -->
                            <form method="POST" action="{{ route('register') }}" class="needs-validation mt-4 pt-2">
                                @csrf

                                <!-- Name -->
                                <div class="mb-3">
                                    <label for="name" class="form-label">{{ __('Name') }}</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name') }}"
                                        placeholder="Masukkan nama" required autofocus>
                                    @error('name')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <!-- Email Address -->
                                <div class="mb-3">
                                    <label for="email" class="form-label">{{ __('Email') }}</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        id="email" name="email" value="{{ old('email') }}"
                                        placeholder="Masukkan email" required>
                                    @error('email')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <!-- Password -->
                                <div class="mb-3">
                                    <label for="password" class="form-label">{{ __('Password') }}</label>
                                    <div class="input-group auth-pass-inputgroup">
                                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                                            id="password" name="password" placeholder="Masukkan password" required
                                            aria-label="Password" aria-describedby="password-addon">
                                        <button class="btn btn-light shadow-none ms-0" type="button" id="password-addon"><i
                                                class="mdi mdi-eye-outline"></i></button>
                                        @error('password')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Confirm Password -->
                                <div class="mb-3">
                                    <label for="password_confirmation"
                                        class="form-label">{{ __('Confirm Password') }}</label>
                                    <div class="input-group auth-pass-inputgroup">
                                        <input type="password" class="form-control" id="password_confirmation"
                                            name="password_confirmation" placeholder="Konfirmasi password" required
                                            aria-label="Password" aria-describedby="password-confirm-addon">
                                        <button class="btn btn-light shadow-none ms-0" type="button"
                                            id="password-confirm-addon"><i class="mdi mdi-eye-outline"></i></button>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <p class="mb-0">Dengan mendaftar Anda menyetujui <a href="#"
                                            class="text-primary">Ketentuan Penggunaan</a></p>
                                </div>

                                <div class="mb-3">
                                    <button class="btn btn-primary w-100 waves-effect waves-light" type="submit">
                                        {{ __('Register') }}
                                    </button>
                                </div>
                            </form>

                            <div class="mt-5 text-center">
                                <p class="text-muted mb-0">Sudah memiliki akun? <a href="{{ route('login') }}"
                                        class="text-primary fw-semibold"> Login </a> </p>
                            </div>
                        </div>
                        <div class="mt-4 mt-md-5 text-center">
                            <p class="mb-0">©
                                <script>
                                    document.write(new Date().getFullYear())
                                </script> Aplikasi Inventaris SMKN 1 Sumenep
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end col -->
        <div class="col-xxl-9 col-lg-8 col-md-7">
            <div class="auth-bg pt-md-5 p-4 d-flex">
                <div class="bg-overlay bg-primary"></div>
                <ul class="bg-bubbles">
                    @for ($i = 0; $i < 10; $i++)
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                    @endfor
                </ul>
                <div class="row justify-content-center align-items-center">
                    <div class="col-xl-7">
                        <div class="p-0 p-sm-4 px-xl-0">
                            <div id="reviewcarouselIndicators" class="carousel slide" data-bs-ride="carousel">
                                <div
                                    class="carousel-indicators carousel-indicators-rounded justify-content-start ms-0 mb-0">
                                    <button type="button" data-bs-target="#reviewcarouselIndicators" data-bs-slide-to="0"
                                        class="active" aria-current="true" aria-label="Slide 1"></button>
                                    <button type="button" data-bs-target="#reviewcarouselIndicators"
                                        data-bs-slide-to="1" aria-label="Slide 2"></button>
                                    <button type="button" data-bs-target="#reviewcarouselIndicators"
                                        data-bs-slide-to="2" aria-label="Slide 3"></button>
                                </div>
                                <div class="carousel-inner">
                                    <div class="carousel-item active">
                                        <div class="testi-contain text-white">
                                            <i class="bx bxs-quote-alt-left text-success display-6"></i>

                                            <h4 class="mt-4 fw-medium lh-base text-white">
                                                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod
                                                tempor incididunt ut labore et dolore magna aliqua.
                                            </h4>
                                            <div class="mt-4 pt-3 pb-5">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <img src="{{ asset('assets/images/users/avatar-1.jpg') }}"
                                                            class="avatar-md img-fluid rounded-circle" alt="...">
                                                    </div>
                                                    <div class="flex-grow-1 ms-3 mb-4">
                                                        <h5 class="font-size-18 text-white">Lorem Ipsum</h5>
                                                        <p class="mb-0 text-white-50">Lorem Ipsum Dolor</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="carousel-item">
                                        <div class="testi-contain text-white">
                                            <i class="bx bxs-quote-alt-left text-success display-6"></i>

                                            <h4 class="mt-4 fw-medium lh-base text-white">
                                                Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut
                                                aliquip ex ea commodo consequat.
                                            </h4>
                                            <div class="mt-4 pt-3 pb-5">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <img src="{{ asset('assets/images/users/avatar-2.jpg') }}"
                                                            class="avatar-md img-fluid rounded-circle" alt="...">
                                                    </div>
                                                    <div class="flex-grow-1 ms-3 mb-4">
                                                        <h5 class="font-size-18 text-white">Sit Amet</h5>
                                                        <p class="mb-0 text-white-50">Consectetur Adipiscing</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="carousel-item">
                                        <div class="testi-contain text-white">
                                            <i class="bx bxs-quote-alt-left text-success display-6"></i>

                                            <h4 class="mt-4 fw-medium lh-base text-white">
                                                Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore
                                                eu fugiat nulla pariatur.
                                            </h4>
                                            <div class="mt-4 pt-3 pb-5">
                                                <div class="d-flex align-items-start">
                                                    <img src="{{ asset('assets/images/users/avatar-3.jpg') }}"
                                                        class="avatar-md img-fluid rounded-circle" alt="...">
                                                    <div class="flex-grow-1 ms-3 mb-4">
                                                        <h5 class="font-size-18 text-white">Sed Do</h5>
                                                        <p class="mb-0 text-white-50">Eiusmod Tempor</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->
@endsection
