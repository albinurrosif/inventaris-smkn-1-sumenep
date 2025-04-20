{{-- resources/views/auth/verify-email.blade.php --}}
@extends('layouts.auth') {{-- Atau sesuaikan layout kamu jika pakai layout khusus --}}

@section('title', 'Email Verification')

@section('content')
    <div class="auth-full-page-content d-flex p-sm-5 p-4">
        <div class="w-100">
            <div class="d-flex flex-column h-100">
                <div class="mb-4 mb-md-5 text-center">
                    <a href="{{ route('dashboard') }}" class="d-block auth-logo">
                        <img src="{{ asset('assets/images/logo-sm.svg') }}" alt="Logo" height="28">
                        <span class="logo-txt">Minia</span>
                    </a>
                </div>
                <div class="auth-content my-auto">
                    <div class="text-center">
                        <div class="avatar-lg mx-auto">
                            <div class="avatar-title rounded-circle bg-light">
                                <i class="bx bxs-envelope h2 mb-0 text-primary"></i>
                            </div>
                        </div>
                        <div class="p-2 mt-4">
                            <h4>Verify your email</h4>
                            <p>
                                We have sent you a verification email to
                                <span class="fw-bold">{{ Auth::user()->email }}</span>, please check it.
                            </p>

                            @if (session('status') == 'verification-link-sent')
                                <div class="alert alert-success mt-3" role="alert">
                                    A new verification link has been sent to your email address.
                                </div>
                            @endif

                            <div class="mt-4">
                                <form method="POST" action="{{ route('verification.send') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary w-100">
                                        Resend Verification Email
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 text-center">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <p class="text-muted mb-0">
                                <button type="submit" class="btn btn-link p-0 m-0 align-baseline text-primary fw-semibold">
                                    Logout
                                </button>
                            </p>
                        </form>
                    </div>
                </div>
                <div class="mt-4 mt-md-5 text-center">
                    <p class="mb-0">© {{ date('Y') }} Minia. Crafted with <i class="mdi mdi-heart text-danger"></i> by
                        Themesbrand</p>
                </div>
            </div>
        </div>
    </div>
@endsection
