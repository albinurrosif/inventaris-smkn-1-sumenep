@extends('layouts.app')

@section('title', 'Tambah Barang')

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
                <h4 class="mb-0">Tambah Barang</h4>
                <p class="text-muted">Step 1 dari 2 — Input data barang utama</p>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="twitter-bs-wizard">
                    {{-- Wizard Navigation --}}
                    <ul class="twitter-bs-wizard-nav nav nav-pills nav-justified">
                        <li class="nav-item">
                            <a class="nav-link active" href="javascript:void(0)">
                                <div class="step-icon" data-bs-toggle="tooltip" title="Data Barang">
                                    <i class="bx bx-list-ul"></i>
                                </div>
                                <span class="step-title"></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link " href="javascript:void(0)">
                                <div class="step-icon" data-bs-toggle="tooltip" title="Nomor Seri">
                                    <i class="bx bx-barcode"></i>
                                </div>
                                <span class="step-title"></span>
                            </a>
                        </li>
                    </ul>

                    {{-- Tombol kembali ke index --}}
                    {{-- <div class="mt-3">
                        <a href="{{ route('barang.index') }}" class="btn btn-light">
                            <i class="mdi mdi-arrow-left"></i> Kembali ke Daftar Barang
                        </a>
                    </div> --}}

                    {{-- Step Content --}}
                    <div class="tab-content twitter-bs-wizard-tab-content mt-2">
                        <div class="tab-pane fade show active">
                            <div class="text-center mb-4">
                                <h5>Data Barang</h5>
                                <p class="card-title-desc">Isi informasi dibawah</p>
                            </div>
                            @include('admin.barang._form_step1')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
