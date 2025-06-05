@extends('layouts.app')

@section('title', 'Input Nomor Seri - ' . $barang->nama_barang)

@section('styles')
    {{-- Style yang sama dengan create.blade.php untuk konsistensi wizard --}}
    <style>
        .form-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            padding-bottom: 0.5rem;
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

        .nav-pills .nav-link.active,
        .nav-pills .show>.nav-link {
            color: #fff;
            background-color: {{ '#556ee6' }};
        }

        .nav-pills .nav-link:not(.active) {
            color: #556ee6;
            background-color: #f0f0f0;
        }

        .twitter-bs-wizard-nav .nav-link .step-icon {
            font-size: 1.5rem;
        }

        .twitter-bs-wizard-nav .nav-link .step-title {
            display: block;
            margin-top: .25rem;
            font-size: .875rem;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }
    </style>
@endsection

@section('content')
    {{-- Notifikasi SweetAlert untuk error validasi --}}
    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let errorMessages =
                    '<ul class="list-unstyled text-start ps-3" style="margin-bottom:0; padding-left: 1rem !important;">';
                @foreach ($errors->all() as $error)
                    errorMessages +=
                        `<li><i class="mdi mdi-alert-circle-outline me-1 text-danger"></i>{{ $error }}</li>`;
                @endforeach
                errorMessages += '</ul>';

                Swal.fire({
                    icon: 'error',
                    title: 'Oops! Ada Kesalahan Validasi',
                    html: errorMessages,
                    confirmButtonText: 'Baik, Saya Mengerti',
                    customClass: {
                        htmlContainer: 'text-left'
                    }
                });
            });
        </script>
    @endif

    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Input Nomor Seri untuk "{{ $barang->nama_barang }}"</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('barang.index') }}">Daftar Jenis Barang</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('barang.create') }}">Tambah (Step 1)</a></li>
                            <li class="breadcrumb-item active">Input Seri (Step 2)</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        {{-- Wizard Navigation --}}
                        <div class="twitter-bs-wizard card-header-tab">
                            <ul class="twitter-bs-wizard-nav nav nav-pills nav-justified">
                                <li class="nav-item" style="width: 50%;">
                                    {{-- Arahkan ke route yang memungkinkan edit step 1 dengan data sesi jika ada --}}
                                    <a class="nav-link" href="{{ route('barang.create') }}?edit_step1={{ $barang->id }}"
                                        role="tab" title="Kembali untuk Edit Data Barang Induk">
                                        <div class="step-icon" data-bs-toggle="tooltip"
                                            title="Data Barang Induk & Unit Awal">
                                            <i class="bx bx-list-ul"></i>
                                        </div>
                                        <span class="step-title">Step 1: Info Barang & Unit</span>
                                    </a>
                                </li>
                                <li class="nav-item" style="width: 50%;">
                                    <a class="nav-link active" href="javascript:void(0);" role="tab">
                                        <div class="step-icon" data-bs-toggle="tooltip" title="Input Nomor Seri">
                                            <i class="bx bx-barcode"></i>
                                        </div>
                                        <span class="step-title">Step 2: Nomor Seri</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="tab-content twitter-bs-wizard-tab-content mt-3">
                            <div class="tab-pane fade show active" id="step2-nomor-seri" role="tabpanel">
                                <div class="text-center mb-4">
                                    <h5>Input Nomor Seri Pabrik</h5>
                                    <p class="card-title-desc">Masukkan nomor seri pabrik untuk sejumlah
                                        <strong>{{ $incompleteTargetQty }}</strong> unit barang
                                        <strong>{{ $barang->nama_barang }}</strong>.
                                    </p>
                                </div>

                                <form action="{{ route('barang.store-serial', $barang->id) }}" method="POST"
                                    id="formInputSerial">
                                    @csrf

                                    <div class="alert alert-info mb-3">
                                        <h6 class="alert-heading">Detail Unit Awal (dari Step 1):</h6>
                                        <ul class="list-unstyled mb-0 small">
                                            <li><strong>Kondisi Awal:</strong> {{ $unitDetailsAwal['kondisi'] ?? '-' }}
                                            </li>
                                            <li><strong>Lokasi Awal:</strong>
                                                @if (!empty($unitDetailsAwal['id_ruangan_awal']))
                                                    {{ \App\Models\Ruangan::find($unitDetailsAwal['id_ruangan_awal'])->nama_ruangan ?? 'Ruangan tidak ditemukan' }}
                                                @elseif(!empty($unitDetailsAwal['id_pemegang_personal_awal']))
                                                    Dipegang oleh:
                                                    {{ \App\Models\User::find($unitDetailsAwal['id_pemegang_personal_awal'])->username ?? 'Pemegang tidak ditemukan' }}
                                                @else
                                                    Belum ditentukan
                                                @endif
                                            </li>
                                            <li><strong>Harga Perolehan Unit Awal:</strong> Rp
                                                {{ number_format($unitDetailsAwal['harga_perolehan_unit'] ?? 0, 0, ',', '.') }}
                                            </li>
                                            <li><strong>Tanggal Perolehan Unit Awal:</strong>
                                                {{ $unitDetailsAwal['tanggal_perolehan_unit'] ? \Carbon\Carbon::parse($unitDetailsAwal['tanggal_perolehan_unit'])->isoFormat('DD MMM YYYY') : '-' }}
                                            </li>
                                            <li><strong>Sumber Dana Unit Awal:</strong>
                                                {{ $unitDetailsAwal['sumber_dana_unit'] ?? '-' }}</li>
                                            <li><strong>No. Dokumen Unit Awal:</strong>
                                                {{ $unitDetailsAwal['no_dokumen_perolehan_unit'] ?? '-' }}</li>
                                            <li><strong>Deskripsi Unit Awal:</strong>
                                                {{ $unitDetailsAwal['deskripsi_unit'] ?? '-' }}</li>
                                        </ul>
                                        <hr>
                                        <small>Informasi di atas akan diterapkan ke semua unit yang nomor serinya Anda input
                                            di bawah ini. Jika ada detail yang berbeda per unit, Anda dapat mengeditnya
                                            nanti setelah semua unit dibuat.</small>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 80px;" class="text-center">Unit Ke-</th>
                                                    <th>Nomor Seri Pabrik <span class="text-danger">*</span></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @for ($i = 0; $i < $incompleteTargetQty; $i++)
                                                    <tr>
                                                        <td class="text-center">{{ $i + 1 }}</td>
                                                        <td>
                                                            <input type="text"
                                                                name="serial_numbers[{{ $i }}]"
                                                                class="form-control form-control-sm @error('serial_numbers.' . $i) is-invalid @enderror"
                                                                value="{{ old('serial_numbers.' . $i) }}" required>
                                                            @error('serial_numbers.' . $i)
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                        </td>
                                                    </tr>
                                                @endfor
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <a href="{{ route('barang.create') }}?edit_step1={{ $barang->id }}"
                                            class="btn btn-secondary waves-effect"> {{-- Tombol kembali ke step 1 untuk edit --}}
                                            <i class="mdi mdi-arrow-left me-1"></i> Kembali ke Step 1
                                        </a>
                                        <button type="button" id="btnAutoGenerateServer"
                                            class="btn btn-outline-info waves-effect">
                                            <i class="mdi mdi-autorenew me-1"></i> Auto-generate Nomor Seri
                                        </button>
                                    </div>

                                    <hr class="my-4">

                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-outline-danger waves-effect btn-cancel-wizard"
                                            data-url="{{ route('barang.cancel-create', $barang->id) }}">
                                            <i class="mdi mdi-close me-1"></i> Batal Proses Pembuatan
                                        </button>
                                        <button type="submit" class="btn btn-success waves-effect waves-light">
                                            <i class="mdi mdi-check me-1"></i> Simpan Nomor Seri & Selesaikan
                                        </button>
                                    </div>
                                </form>
                            </div>
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
            // Wizard Nav Styling
            const navLinks = document.querySelectorAll('.twitter-bs-wizard-nav .nav-link');
            if (navLinks.length > 1) {
                navLinks[0].classList.remove('active');
                navLinks[0].setAttribute('aria-selected', 'false');
                navLinks[1].classList.add('active');
                navLinks[1].setAttribute('aria-selected', 'true');
            }

            // Auto-generate Nomor Seri
            const btnAutoGenerate = document.getElementById('btnAutoGenerateServer');
            if (btnAutoGenerate) {
                btnAutoGenerate.addEventListener('click', function() {
                    Swal.fire({
                        title: 'Mohon tunggu...',
                        text: 'Mengambil saran nomor seri dari server.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch("{{ route('barang.suggest-serials', $barang->id) }}")
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok.');
                            return response.json();
                        })
                        .then(data => {
                            Swal.close();
                            if (data.error) {
                                Swal.fire('Error', data.error, 'error');
                                return;
                            }
                            const inputs = document.querySelectorAll('input[name^="serial_numbers"]');
                            inputs.forEach((input, index) => {
                                input.value = data[index] || '';
                                input.classList.remove(
                                    'is-invalid'); // Hapus kelas error jika ada
                            });
                            Swal.fire('Berhasil', 'Nomor seri berhasil di-generate.', 'success');
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            Swal.fire('Error',
                                'Gagal mengambil nomor seri dari server. Coba lagi nanti.', 'error');
                        });
                });
            }

            // Konfirmasi Submit Form
            const formInputSerial = document.getElementById('formInputSerial');
            if (formInputSerial) {
                formInputSerial.addEventListener('submit', function(e) {
                    e.preventDefault(); // Selalu cegah submit default dulu

                    const inputs = formInputSerial.querySelectorAll('input[name^="serial_numbers"]');
                    let allFilled = true;
                    let uniqueValues = new Set();
                    let hasDuplicates = false;

                    inputs.forEach(input => {
                        input.classList.remove('is-invalid'); // Bersihkan error sebelumnya
                        if (!input.value.trim()) {
                            allFilled = false;
                            input.classList.add('is-invalid');
                        }
                        if (input.value.trim() && uniqueValues.has(input.value.trim())) {
                            hasDuplicates = true;
                            input.classList.add('is-invalid'); // Tandai duplikat
                        }
                        if (input.value.trim()) uniqueValues.add(input.value.trim());
                    });

                    if (!allFilled) {
                        Swal.fire('Data Belum Lengkap',
                            'Harap isi semua field nomor seri yang wajib diisi.', 'error');
                        return;
                    }
                    if (hasDuplicates) {
                        Swal.fire('Nomor Seri Duplikat',
                            'Terdapat nomor seri yang sama. Pastikan semua nomor seri unik.', 'error');
                        return;
                    }

                    Swal.fire({
                        title: 'Konfirmasi Penyimpanan',
                        text: 'Anda yakin ingin menyimpan nomor seri ini? Setelah disimpan, unit-unit akan dibuat.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Simpan',
                        cancelButtonText: 'Periksa Kembali',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            formInputSerial.submit(); // Lanjutkan submit form
                        }
                    });
                });
            }

            // Konfirmasi Pembatalan Wizard
            const btnCancelWizard = document.querySelector('.btn-cancel-wizard');
            if (btnCancelWizard) {
                btnCancelWizard.addEventListener('click', function(e) {
                    e.preventDefault();
                    const cancelUrl = this.getAttribute('data-url');
                    Swal.fire({
                        title: 'Batalkan Proses Pembuatan Barang?',
                        text: "Semua data yang telah diinput untuk jenis barang ini akan dihapus permanen.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Batalkan Proses',
                        cancelButtonText: 'Tidak, Lanjutkan Input',
                        confirmButtonColor: '#d33',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Buat form dinamis untuk mengirim request DELETE
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = cancelUrl;
                            const csrfToken = document.querySelector('meta[name="csrf-token"]')
                                ?.getAttribute('content') || '{{ csrf_token() }}';
                            const methodInput = document.createElement('input');
                            methodInput.type = 'hidden';
                            methodInput.name = '_method';
                            methodInput.value = 'DELETE';
                            form.appendChild(methodInput);
                            const csrfInput = document.createElement('input');
                            csrfInput.type = 'hidden';
                            csrfInput.name = '_token';
                            csrfInput.value = csrfToken;
                            form.appendChild(csrfInput);
                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                });
            }
        });
    </script>
@endpush
