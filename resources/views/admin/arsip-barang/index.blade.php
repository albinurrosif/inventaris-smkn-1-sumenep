@extends('layouts.app')

@section('title', 'Arsip Barang Dihapus')

@section('content')
    <div class="container-fluid">

        <!-- Page Title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('redirect-dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Arsip Barang</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Riwayat Penghapusan Barang</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="arsipTable" class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nomor Seri</th>
                                <th>Nama Barang</th>
                                <th>Ruangan</th>
                                <th>Dihapus Oleh</th>
                                <th>Alasan</th>
                                <th>Tanggal</th>
                                <th>Berita Acara</th>
                                <th>Foto Bukti</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($arsipList as $index => $arsip)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $arsip->qrCode->no_seri_pabrik ?? '-' }}</td>
                                    <td>{{ $arsip->barang->nama_barang ?? '-' }}</td>
                                    <td>{{ $arsip->barang->ruangan->nama_ruangan ?? '-' }}</td>
                                    <td>{{ $arsip->user->name ?? '-' }}</td>
                                    <td>{{ $arsip->alasan ?? '-' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($arsip->tanggal_dihapus)->translatedFormat('d M Y H:i') }}
                                    </td>
                                    <td>
                                        @if ($arsip->berita_acara_path)
                                            <a href="{{ asset('storage/' . $arsip->berita_acara_path) }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="mdi mdi-file-document-outline"></i> Unduh
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($arsip->foto_bukti)
                                            <a href="{{ asset('storage/' . $arsip->foto_bukti) }}" target="_blank">
                                                <img src="{{ asset('storage/' . $arsip->foto_bukti) }}" width="40"
                                                    height="40" class="img-thumbnail">
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#arsipTable').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                },
                order: [
                    [0, 'asc']
                ]
            });
        });
    </script>
@endpush
