@extends('layouts.app')

@section('title', 'Input Nomor Seri Barang')

@section('content')
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let errorMessages = '';
                @foreach ($errors->all() as $error)
                    errorMessages += `• {{ $error }}<br>`;
                @endforeach

                Swal.fire({
                    icon: 'error',
                    title: 'Validasi Gagal',
                    html: errorMessages,
                    showConfirmButton: false,
                    timer: 3000,
                    position: 'top',
                    toast: true
                });
            });
        </script>
    @endif

    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h4 class="mb-0">Input Nomor Seri Barang</h4>
                <p class="text-muted">Step 2 dari 2 — Isi nomor seri setiap unit barang</p>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="twitter-bs-wizard">
                    {{-- Wizard Nav --}}
                    <ul class="twitter-bs-wizard-nav nav nav-pills nav-justified">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('barang.edit', $barang->id) }}">
                                <div class="step-icon" data-bs-toggle="tooltip" title="Data Barang">
                                    <i class="bx bx-list-ul"></i>
                                </div>
                                <span class="step-title"></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="javascript:void(0)">
                                <div class="step-icon" data-bs-toggle="tooltip" title="Nomor Seri">
                                    <i class="bx bx-barcode"></i>
                                </div>
                                <span class="step-title"></span>
                            </a>
                        </li>
                    </ul>

                    {{-- Step Content --}}
                    <div class="tab-content twitter-bs-wizard-tab-content mt-2">
                        <div class="tab-pane fade show active">
                            <div class="text-center mb-4">
                                <h5>Nomor Seri</h5>
                                <p class="card-title-desc">Isi informasi dibawah</p>
                            </div>
                            @include('admin.barang._form_step2')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
