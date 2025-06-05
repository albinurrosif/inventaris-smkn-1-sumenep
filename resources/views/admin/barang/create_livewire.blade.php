@extends('layouts.app')

@section('title', 'Tambah Barang Baru (Wizard)')

@section('styles')
    @livewireStyles
    {{-- Tambahkan style khusus untuk wizard di sini jika perlu --}}
    <style>
        /* Contoh style untuk step navigation dari Spatie Wizard */
        .step-item {
            /* Ini adalah contoh, sesuaikan dengan class yang dipakai Spatie */
            padding: 10px 15px;
            margin-right: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }

        .step-item-active {
            font-weight: bold;
            background-color: #e9ecef;
            border-color: #b1bdd0;
        }

        .step-item-completed {
            color: green;
            border-color: green;
        }

        .step-item-disabled {
            color: grey;
            cursor: not-allowed;
        }

        /* Anda mungkin perlu style dari template wizard Anda sebelumnya */
        .form-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            padding-bottom: 0.5rem;
            margin-top: 1.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .form-subsection-title {
            font-size: 0.95rem;
            font-weight: 500;
            color: #495057;
            margin-top: 1.25rem;
            margin-bottom: 1rem;
        }

        .swal2-html-container ul {
            text-align: left !important;
            padding-left: 1.5rem !important;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        @can('create', App\Models\Barang::class)
            {{-- Otorisasi Laravel Policy standar --}}
            <div>
                @livewire(\App\Livewire\TambahBarangWizard::class) </div>
        @else
            <div class="alert alert-danger">
                Anda tidak memiliki izin untuk menambah barang baru.
            </div>
        @endcan
    </div>
@endsection

@push('scripts')
    @livewireScripts
    {{-- Jika ada JavaScript global untuk wizard atau SweetAlert --}}
    <script>
        document.addEventListener('livewire:load', function() {
            Livewire.on('wizardError', message => { // Dengarkan event kustom jika diperlukan
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: message
                });
            });
            Livewire.on('showSuccessToast', message => { // Contoh event untuk toast sukses dari Livewire
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            });
        });
    </script>
@endpush
