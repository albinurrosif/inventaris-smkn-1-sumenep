@extends('layouts.app')
@section('title', 'Pengaturan Sistem')

@php $rolePrefix = Auth::user()->getRolePrefix(); @endphp

@section('content')
    <div class="container-fluid">
        {{-- Header & Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Pengaturan Sistem</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route($rolePrefix . 'dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Pengaturan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.pengaturan.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Kelola Pengaturan Aplikasi</h5>
                </div>
                <div class="card-body">
                    @if ($settings->isNotEmpty())
                        <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                            @foreach ($settings as $group => $items)
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab"
                                        href="#{{ Str::slug($group) }}" role="tab">{{ $group }}</a>
                                </li>
                            @endforeach
                        </ul>
                        <div class="tab-content border border-top-0 p-4">
                            @foreach ($settings as $group => $items)
                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                    id="{{ Str::slug($group) }}" role="tabpanel">
                                    @foreach ($items as $setting)
                                        <div class="mb-4">
                                            <label for="{{ $setting->key }}"
                                                class="form-label fw-medium">{{ Str::headline($setting->key) }}</label>

                                            {{-- PERBAIKAN UTAMA ADA DI SINI --}}
                                            @if ($setting->type == 'text')
                                                <input type="text" class="form-control" id="{{ $setting->key }}"
                                                    name="settings[{{ $setting->key }}]"
                                                    value="{{ old('settings.' . $setting->key, $setting->value) }}">
                                            @elseif($setting->type == 'textarea')
                                                <textarea class="form-control" id="{{ $setting->key }}" name="settings[{{ $setting->key }}]" rows="3">{{ old('settings.' . $setting->key, $setting->value) }}</textarea>
                                            @elseif($setting->type == 'image')
                                                <input class="form-control" type="file" id="{{ $setting->key }}"
                                                    name="{{ $setting->key }}" accept=".png, .jpg, .jpeg">
                                                @if ($setting->value && Storage::disk('public')->exists($setting->value))
                                                    <div class="mt-2">
                                                        <img src="{{ Storage::url($setting->value) }}" alt="Logo Saat Ini"
                                                            height="60" class="img-thumbnail">
                                                    </div>
                                                @endif
                                            @endif

                                            @if ($setting->description)
                                                <small class="form-text text-muted">{{ $setting->description }}</small>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-muted">Tidak ada pengaturan ditemukan. Mohon jalankan `php artisan
                            db:seed --class=PengaturanSeeder`.</p>
                    @endif
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan
                        Pengaturan</button>
                </div>
            </div>
        </form>
    </div>
@endsection
