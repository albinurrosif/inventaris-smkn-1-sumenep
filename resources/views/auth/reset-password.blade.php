@extends('layouts.auth') {{-- atau layout khusus auth jika kamu punya --}}

@section('content')
    <div class="auth-page">
        <div class="container-fluid p-0">
            <div class="row g-0">
                <div class="col-xxl-3 col-lg-4 col-md-5">
                    <div class="auth-full-page-content d-flex p-sm-5 p-4">
                        <div class="w-100">
                            <div class="d-flex flex-column h-100">
                                <div class="mb-4 text-center">
                                    <a href="/" class="d-block auth-logo">
                                        <img src="{{ asset('assets/images/logo-sm.svg') }}" alt="" height="28">
                                        <span class="logo-txt">Minia</span>
                                    </a>
                                </div>
                                <div class="auth-content my-auto">
                                    <div class="text-center">
                                        <h5 class="mb-0">Reset Your Password</h5>
                                        <p class="text-muted mt-2">Enter your email and new password.</p>
                                    </div>

                                    <form method="POST" action="{{ route('password.store') }}" class="mt-4 pt-2">
                                        @csrf
                                        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                                id="email" name="email" value="{{ old('email', $request->email) }}"
                                                required autofocus autocomplete="username">
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="password" class="form-label">New Password</label>
                                            <input type="password"
                                                class="form-control @error('password') is-invalid @enderror" id="password"
                                                name="password" required autocomplete="new-password">
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control" id="password_confirmation"
                                                name="password_confirmation" required autocomplete="new-password">
                                            @error('password_confirmation')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <button class="btn btn-primary w-100" type="submit">Reset Password</button>
                                        </div>
                                    </form>
                                </div>

                                <div class="mt-4 text-center">
                                    <p class="mb-0">©
                                        <script>
                                            document.write(new Date().getFullYear())
                                        </script> Minia. Crafted with <i
                                            class="mdi mdi-heart text-danger"></i> by Themesbrand
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side (optional) -->
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
                            <button type="button" data-bs-target="#reviewcarouselIndicators"
                                data-bs-slide-to="0" class="active" aria-current="true"
                                aria-label="Slide 1"></button>
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
                                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                                    </h4>
                                    <div class="mt-4 pt-3 pb-5">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <img src="{{ asset('assets/images/users/avatar-1.jpg') }}"
                                                    class="avatar-md img-fluid rounded-circle"
                                                    alt="...">
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
                                        Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
                                    </h4>
                                    <div class="mt-4 pt-3 pb-5">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <img src="{{ asset('assets/images/users/avatar-2.jpg') }}"
                                                    class="avatar-md img-fluid rounded-circle"
                                                    alt="...">
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
                                        Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.
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
            </div>
        </div>
    </div>
@endsection
