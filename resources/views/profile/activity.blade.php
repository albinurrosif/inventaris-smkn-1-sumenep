@extends('layouts.app')

@section('title', 'Aktivitas Saya')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Aktivitas Saya</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a
                                    href="{{ route(Auth::user()->getRolePrefix() . 'dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Aktivitas Saya</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs Navigasi --}}
        <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
            @if (Auth::user()->hasRole(\App\Models\User::ROLE_GURU))
                <li class="nav-item" role="presentation"><a class="nav-link active" data-bs-toggle="tab" href="#peminjaman"
                        role="tab">Riwayat Peminjaman</a></li>
                <li class="nav-item" role="presentation"><a class="nav-link" data-bs-toggle="tab" href="#pemeliharaan"
                        role="tab">Riwayat Laporan Kerusakan</a></li>
            @elseif(Auth::user()->hasRole(\App\Models\User::ROLE_OPERATOR))
                <li class="nav-item" role="presentation"><a class="nav-link active" data-bs-toggle="tab" href="#peminjaman"
                        role="tab">Peminjaman Terkait</a></li>
                <li class="nav-item" role="presentation"><a class="nav-link" data-bs-toggle="tab" href="#pemeliharaan"
                        role="tab">Pemeliharaan Terkait</a></li>
                <li class="nav-item" role="presentation"><a class="nav-link" data-bs-toggle="tab" href="#stokopname"
                        role="tab">Tugas Stok Opname</a></li>
            @endif
        </ul>

        {{-- Konten Tabs --}}
        <div class="tab-content p-3 text-muted">
            @if (Auth::user()->hasRole(\App\Models\User::ROLE_GURU))
                <div class="tab-pane active" id="peminjaman" role="tabpanel">
                    @include('profile.partials.activity_peminjaman_guru')
                </div>
                <div class="tab-pane" id="pemeliharaan" role="tabpanel">
                    @include('profile.partials.activity_pemeliharaan_guru')
                </div>
            @elseif(Auth::user()->hasRole(\App\Models\User::ROLE_OPERATOR))
                <div class="tab-pane active" id="peminjaman" role="tabpanel">
                    <p>Menampilkan daftar peminjaman yang melibatkan barang dari ruangan yang Anda kelola.</p>
                    {{-- Anda bisa membuat partial view khusus untuk operator atau menggunakan yang sama dengan guru --}}
                    @include('profile.partials.activity_peminjaman_guru', [
                        'peminjamanList' => $peminjamanTerkait,
                    ])
                </div>
                <div class="tab-pane" id="pemeliharaan" role="tabpanel">
                    <p>Menampilkan daftar pemeliharaan yang Anda laporkan, Anda kerjakan, atau untuk barang di ruangan Anda.
                    </p>
                    @include('profile.partials.activity_pemeliharaan_guru', [
                        'pemeliharaanList' => $pemeliharaanTerkait,
                    ])
                </div>
                <div class="tab-pane" id="stokopname" role="tabpanel">
                    @include('profile.partials.activity_stokopname_operator')
                </div>
            @endif
        </div>
    </div>
@endsection
