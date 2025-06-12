@extends('layouts.app') {{-- Sesuaikan dengan layout utama Anda --}}

@section('title', 'Manajemen Pemeliharaan Barang')
@php
    $rolePrefix = Auth::user()->getRolePrefix();
@endphp
@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <style>
        #tabelPemeliharaan th,
        #tabelPemeliharaan td {
            vertical-align: middle;
            font-size: 0.85rem;
            /* Ukuran font yang sedikit lebih kecil */
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }

        .table-danger-light td {
            background-color: #fdeeee !important;
            /* Warna untuk item yang diarsipkan */
        }

        .select2-container--bootstrap-5 .select2-selection--single {
            height: calc(1.5em + .5rem + 2px) !important;
            /* Menyamakan tinggi dengan form-control-sm */
            padding: .25rem .5rem !important;
            font-size: .875rem !important;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            line-height: 1.5 !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + .5rem) !important;
        }

        .form-label {
            font-size: 0.8rem;
            /* Ukuran font label */
            margin-bottom: 0.25rem;
        }

        .btn-sm {
            padding: 0.2rem 0.4rem;
            /* Padding lebih kecil untuk tombol aksi */
            font-size: 0.7rem;
            /* Font lebih kecil untuk tombol aksi */
        }

        .badge {
            font-size: 0.7rem;
            /* Ukuran font badge */
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        {{-- Breadcrumb --}}
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">@yield('title')</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            {{-- Menggunakan helper untuk prefix route dashboard --}}
                            <li class="breadcrumb-item"><a
                                    href="{{ route(Auth::user()->getRolePrefix() . 'dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Pemeliharaan</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="card-title mb-0 flex-grow-1"><i class="fas fa-tools me-2"></i>Data Laporan Pemeliharaan</h5>
                @can('create', App\Models\Pemeliharaan::class)
                    {{-- Menggunakan helper untuk prefix route create --}}
                    <a href="{{ route(Auth::user()->getRolePrefix() . 'pemeliharaan.create') }}" class="btn btn-primary btn-sm"
                        title="Buat Laporan Pemeliharaan Baru">
                        <i class="mdi mdi-plus me-1"></i> Lapor Pemeliharaan
                    </a>
                @endcan
            </div>

            <div class="card-body">

                {{-- Form Filter --}}
                <form method="GET" action="{{ route(Auth::user()->getRolePrefix() . 'pemeliharaan.index') }}"
                    class="mb-3">
                    <div class="row g-2">
                        <div class="col-md-6 col-lg-3 mb-2">
                            <label for="search_pemeliharaan" class="form-label">Pencarian Global</label>
                            <input type="text" name="search" id="search_pemeliharaan"
                                class="form-control form-control-sm" placeholder="Kode unit, nama barang, kerusakan..."
                                value="{{ $searchTerm ?? '' }}">
                        </div>
                        <div class="col-md-6 col-lg-2 mb-2">
                            <label for="status_pemeliharaan_filter" class="form-label">Status</label>
                            <select name="status_pemeliharaan" id="status_pemeliharaan_filter"
                                class="form-select form-select-sm select2-filter">
                                <option value="">-- Semua Status --</option>
                                @foreach ($statusPemeliharaanList as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ ($statusFilter ?? '') == $key ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-1 mb-2">
                            <label for="prioritas_filter" class="form-label">Prioritas</label>
                            <select name="prioritas" id="prioritas_filter"
                                class="form-select form-select-sm select2-filter">
                                <option value="">-- Semua --</option>
                                @foreach ($prioritasList as $key => $value)
                                    <option value="{{ $key }}"
                                        {{ ($prioritasFilter ?? '') == $key ? 'selected' : '' }}>{{ $value }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if (Auth::user()->hasRole(\App\Models\User::ROLE_ADMIN))
                            <div class="col-md-6 col-lg-2 mb-2">
                                <label for="id_user_pelapor_filter" class="form-label">Pelapor</label>
                                <select name="id_user_pelapor" id="id_user_pelapor_filter"
                                    class="form-select form-select-sm select2-filter">
                                    <option value="">-- Semua Pelapor --</option>
                                    @foreach ($usersList as $u)
                                        <option value="{{ $u->id }}"
                                            {{ ($pelaporFilter ?? '') == $u->id ? 'selected' : '' }}>{{ $u->username }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 col-lg-2 mb-2">
                                <label for="id_user_bertanggung_jawab_filter" class="form-label">P. Jawab (PIC)</label>
                                <select name="id_user_bertanggung_jawab" id="id_user_bertanggung_jawab_filter"
                                    class="form-select form-select-sm select2-filter">
                                    <option value="">-- Semua PIC --</option>
                                    @foreach ($usersList as $u)
                                        <option value="{{ $u->id }}"
                                            {{ ($picFilter ?? '') == $u->id ? 'selected' : '' }}>{{ $u->username }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-6 col-lg-2 mb-2">
                            <label for="tanggal_mulai_lapor_filter" class="form-label">Tgl. Lapor Dari</label>
                            <input type="date" name="tanggal_mulai_lapor" id="tanggal_mulai_lapor_filter"
                                class="form-control form-control-sm" value="{{ $tanggalMulai ?? '' }}">
                        </div>
                        <div class="col-md-6 col-lg-2 mb-2">
                            <label for="tanggal_selesai_lapor_filter" class="form-label">Tgl. Lapor Sampai</label>
                            <input type="date" name="tanggal_selesai_lapor" id="tanggal_selesai_lapor_filter"
                                class="form-control form-control-sm" value="{{ $tanggalSelesai ?? '' }}">
                        </div>
                        <div class="col-md-6 col-lg-2 mb-2">
                            <label for="status_arsip_filter" class="form-label">Tampilkan</label>
                            <select name="status_arsip" id="status_arsip_filter" class="form-select form-select-sm"
                                onchange="this.form.submit()">
                                <option value="aktif" {{ ($statusArsipFilter ?? 'aktif') === 'aktif' ? 'selected' : '' }}>
                                    Aktif</option>
                                <option value="arsip" {{ ($statusArsipFilter ?? '') === 'arsip' ? 'selected' : '' }}>Arsip
                                </option>
                                <option value="semua" {{ ($statusArsipFilter ?? '') === 'semua' ? 'selected' : '' }}>Semua
                                </option>
                            </select>
                        </div>
                        <div class="col-md-auto mb-2 d-flex align-items-end">
                            <button class="btn btn-primary btn-sm w-100" type="submit"><i
                                    class="fas fa-filter me-1"></i>Filter</button>
                        </div>
                        @if (
                            $searchTerm ||
                                $statusFilter ||
                                $prioritasFilter ||
                                $picFilter ||
                                $pelaporFilter ||
                                $tanggalMulai ||
                                $tanggalSelesai ||
                                ($statusArsipFilter !== 'aktif' && !is_null($statusArsipFilter)))
                            <div class="col-md-auto mb-2 d-flex align-items-end">
                                {{-- Menggunakan helper untuk prefix route index --}}
                                <a href="{{ route(Auth::user()->getRolePrefix() . 'pemeliharaan.index') }}"
                                    class="btn btn-outline-secondary btn-sm w-100" title="Reset Semua Filter">
                                    <i class="fas fa-times me-1"></i>Reset
                                </a>
                            </div>
                        @endif
                    </div>
                </form>

                <div class="table-responsive mt-3">
                    <table id="tabelPemeliharaan"
                        class="table table-sm table-bordered table-hover dt-responsive nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">No</th>
                                <th>Nama Barang & Kode Unit</th> {{-- Perubahan Judul Kolom --}}
                                <th>Kerusakan Dilaporkan</th>
                                <th class="text-center">Prioritas</th>
                                <th class="text-center">Status</th>
                                <th>Keterkaitan</th> {{-- Kolom Baru --}}
                                <th>Tgl. Lapor</th>
                                <th>Pelapor</th>
                                <th>PIC</th>
                                <th class="text-center" style="width: 10%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pemeliharaanList as $index => $item)
                                <tr class="{{ $item->trashed() ? 'table-danger-light' : '' }}">
                                    <td class="text-center">{{ $pemeliharaanList->firstItem() + $index }}</td>
                                    <td>
                                        {{-- ===== AWAL PERUBAHAN KOLOM NAMA BARANG & KODE UNIT ===== --}}
                                        @if ($item->barangQrCode)
                                            <a href="{{ route($rolePrefix . 'barang-qr-code.show', $item->id) }}"
                                                target="_blank" class="fw-bold" data-bs-toggle="tooltip"
                                                title="Lihat Detail Unit: {{ $item->barangQrCode->kode_inventaris_sekolah }}">
                                                {{ optional($item->barangQrCode->barang)->nama_barang ?? 'N/A' }}
                                            </a>
                                            <small class="text-muted d-block">
                                                Kode:
                                                <code>{{ $item->barangQrCode->kode_inventaris_sekolah ?? 'N/A' }}</code>
                                            </small>
                                            @php
                                                $lokasi = 'Lokasi Tidak Diketahui';
                                                if ($item->barangQrCode->ruangan) {
                                                    $lokasi = 'Di: ' . $item->barangQrCode->ruangan->nama_ruangan;
                                                } elseif ($item->barangQrCode->pemegangPersonal) {
                                                    $lokasi =
                                                        'Dipegang: ' . $item->barangQrCode->pemegangPersonal->username;
                                                }
                                            @endphp
                                            <small class="fst-italic d-block" style="font-size: 0.75rem;">
                                                <i class="fas fa-map-marker-alt me-1"></i> {{ $lokasi }}
                                            </small>
                                        @else
                                            <span class="text-muted">Data Unit Barang Hilang</span>
                                        @endif
                                        {{-- ===== AKHIR PERUBAHAN KOLOM NAMA BARANG & KODE UNIT ===== --}}
                                    </td>
                                    <td data-bs-toggle="tooltip" title="{{ $item->catatan_pengajuan }}">
                                        {{ Str::limit($item->catatan_pengajuan, 35) }}
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $prioritasClass = match (strtolower($item->prioritas ?? '')) {
                                                'tinggi' => 'danger',
                                                'sedang' => 'warning text-dark',
                                                'rendah' => 'info',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <span
                                            class="badge bg-{{ $prioritasClass }}">{{ Str::ucfirst($item->prioritas) }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge {{ App\Models\Pemeliharaan::statusColor($item->status_pemeliharaan) }}">{{ $item->status_pemeliharaan }}</span>
                                    </td>
                                    {{-- ===== AWAL PENAMBAHAN KOLOM KETERKAITAN ===== --}}
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $item->keterkaitan }}</span>
                                    </td>
                                    {{-- ===== AKHIR PENAMBAHAN KOLOM KETERKAITAN ===== --}}
                                    <td data-sort="{{ optional($item->tanggal_pengajuan)->timestamp }}">
                                        {{ optional($item->tanggal_pengajuan)->isoFormat('DD MMM YY') }}
                                    </td>
                                    <td>{{ optional($item->pengaju)->username ?? '-' }}</td>
                                    <td>{{ optional($item->operatorPengerjaan)->username ?? '-' }}</td>
                                    <td class="text-center">
                                        {{-- (Tidak ada perubahan pada kolom Aksi) --}}
                                        <div class="d-flex gap-1 justify-content-center">
                                            @if ($item->trashed())
                                                @can('restore', $item)
                                                    <form
                                                        action="{{ route(Auth::user()->getRolePrefix() . 'pemeliharaan.restore', $item->id) }}"
                                                        method="POST" class="d-inline form-restore-pemeliharaan">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm btn-restore"
                                                            data-bs-toggle="tooltip" title="Pulihkan Laporan">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                                @can('view', $item)
                                                    <a href="{{ route(Auth::user()->getRolePrefix() . 'pemeliharaan.show', $item->id) }}"
                                                        class="btn btn-secondary btn-sm" data-bs-toggle="tooltip"
                                                        title="Detail Arsip">
                                                        <i class="fas fa-archive"></i>
                                                    </a>
                                                @endcan
                                            @else
                                                @can('view', $item)
                                                    <a href="{{ route(Auth::user()->getRolePrefix() . 'pemeliharaan.show', $item->id) }}"
                                                        class="btn btn-info btn-sm" data-bs-toggle="tooltip" title="Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endcan
                                                @can('update', $item)
                                                    <a href="{{ route(Auth::user()->getRolePrefix() . 'pemeliharaan.edit', $item->id) }}"
                                                        class="btn btn-warning btn-sm" data-bs-toggle="tooltip"
                                                        title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endcan
                                                @can('delete', $item)
                                                    <button type="button"
                                                        class="btn btn-danger btn-sm btn-delete-pemeliharaan"
                                                        data-id="{{ $item->id }}"
                                                        data-deskripsi="{{ Str::limit($item->catatan_pengajuan, 30) }}"
                                                        data-bs-toggle="tooltip" title="Arsipkan Laporan">
                                                        <i class="fas fa-archive"></i>
                                                    </button>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    {{-- Sesuaikan colspan karena ada penambahan kolom --}}
                                    <td colspan="11" class="text-center">
                                        Tidak ada data laporan pemeliharaan yang cocok dengan filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Paginasi --}}
                @if ($pemeliharaanList->hasPages())
                    <div class="mt-3 d-flex justify-content-end">
                        {{ $pemeliharaanList->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <form id="formDeletePemeliharaan" method="POST" style="display: none;">@csrf @method('DELETE')</form>
@endsection

@push('scripts')
    {{-- (Tidak ada perubahan signifikan di sini, hanya penyesuaian kecil jika ada) --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function getUserRoleBasedRoutePrefix() {
            @if (Auth::check() && method_exists(Auth::user(), 'getRolePrefix'))
                return "{{ Auth::user()->getRolePrefix() }}";
            @else
                console.warn(
                    'getRolePrefix method not found on User model or user not authenticated. Defaulting to admin prefix logic if applicable.'
                );
                return 'admin.';
            @endif
        }

        $(document).ready(function() {
            if ($('#tabelPemeliharaan tbody tr').length > 0 && !$('#tabelPemeliharaan tbody tr td[colspan="11"]')
                .length) {
                if ($.fn.DataTable.isDataTable('#tabelPemeliharaan')) {
                    $('#tabelPemeliharaan').DataTable().destroy();
                }
                $('#tabelPemeliharaan').DataTable({
                    responsive: true,
                    paging: false, // Paginasi dikelola Laravel
                    searching: false,
                    info: false,
                    ordering: true,
                    order: [
                        [6, 'desc']
                    ], // Default sort by Tgl. Lapor (indeks kolom 7) descending
                    columnDefs: [{
                        targets: [0, 9], // Sesuaikan indeks kolom Aksi
                        orderable: false,
                        searchable: false
                    }],
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                    }
                });
            }

            $('.select2-filter').select2({
                theme: "bootstrap-5",
                width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' :
                    'style',
                placeholder: $(this).data('placeholder') || "-- Pilih Opsi --",
                allowClear: true
            });

            // Handle Delete Confirmation
            $(document).on('click', '.btn-delete-pemeliharaan', function() {
                const pemeliharaanId = $(this).data('id');
                const deskripsi = $(this).data('deskripsi');
                const formDelete = $('#formDeletePemeliharaan');
                const rolePrefixOnly = getUserRoleBasedRoutePrefix().slice(0, -
                    1);

                let actionUrl =
                    `{{ route('admin.pemeliharaan.destroy', ['pemeliharaan' => ':id']) }}`;
                if (rolePrefixOnly && rolePrefixOnly !== 'admin') {
                    actionUrl = actionUrl.replace('/admin/', `/${rolePrefixOnly}/`);
                }
                actionUrl = actionUrl.replace(':id', pemeliharaanId);

                Swal.fire({
                    title: 'Konfirmasi Arsipkan Laporan',
                    html: `Anda yakin ingin mengarsipkan laporan pemeliharaan untuk: <strong>"${deskripsi}"</strong>?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Arsipkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (formDelete.length) {
                            formDelete.attr('action', actionUrl).submit();
                        }
                    }
                });
            });

            // Handle Restore Confirmation
            $(document).on('submit', '.form-restore-pemeliharaan', function(e) {
                e.preventDefault();
                const form = this;
                const deskripsi = $(this).closest('tr').find('td:nth-child(3)').text()
                    .trim();
                Swal.fire({
                    title: 'Konfirmasi Pulihkan Laporan',
                    html: `Anda yakin ingin memulihkan laporan pemeliharaan: <strong>"${deskripsi}"</strong>?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Pulihkan!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        });
    </script>
@endpush
