@extends('layouts.base-pdf')

@section('content')
    <h3 class="text-center">DAFTAR BARANG PER UNIT</h3>

    <p><strong>Filter:</strong>
        @if ($filters['ruangan'])
            Ruangan: {{ $filters['ruangan']->nama_ruangan }} |
        @endif
        @if ($filters['kategori'])
            Kategori: {{ $filters['kategori']->nama_kategori }} |
        @endif
        @if ($filters['status'])
            Status: {{ $filters['status'] }} |
        @endif
        @if ($filters['kondisi'])
            Kondisi: {{ $filters['kondisi'] }} |
        @endif
        @if ($filters['tahun'])
            Tahun: {{ $filters['tahun'] }} |
        @endif
    </p>

    @php $grouped = $qrCodes->groupBy(fn($qr) => $groupByRuangan ? $qr->barang->ruangan->nama_ruangan : 'Semua') @endphp

    @foreach ($grouped as $groupName => $items)
        @if ($groupByRuangan)
            <h5 class="mt-4">Ruangan: {{ $groupName }}</h5>
        @endif

        <table border="1" cellpadding="4" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Barang</th>
                    <th>Kode Barang</th>
                    <th>Nomor Seri Pabrik</th>
                    <th>Kategori</th>
                    <th>Merk / Model</th>
                    <th>Ukuran</th>
                    <th>Bahan</th>
                    <th>Ruangan</th>
                    <th>Tahun Pembelian</th>
                    <th>Harga Beli / Perolehan</th>
                    <th>Sumber</th>
                    <th>Keadaan Barang</th>
                    <th>Status</th>
                    {{-- <th>Keterangan</th> --}}
                    <th>QR Code</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $index => $qr)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $qr->barang->nama_barang }}</td>
                        <td>{{ $qr->barang->kode_barang }}</td>
                        <td>{{ $qr->no_seri_pabrik }}</td>
                        <td>{{ $qr->barang->kategori->nama_kategori }}</td>
                        <td>{{ $qr->barang->merk_model }}</td>
                        <td>{{ $qr->barang->ukuran }}</td>
                        <td>{{ $qr->barang->bahan }}</td>
                        <td>{{ $qr->barang->ruangan->nama_ruangan }}</td>
                        <td>{{ $qr->barang->tahun_pembuatan_pembelian }}</td>
                        <td>{{ number_format($qr->barang->harga_beli, 0, ',', '.') }}</td>
                        <td>{{ $qr->barang->sumber }}</td>
                        <td>{{ $qr->kondisi }}</td>
                        <td>{{ $qr->status }}</td>
                        {{-- <td>{{ $qr->keterangan }}</td> --}}
                        <td>
                            @if ($qr->qr_path && file_exists(public_path('storage/' . $qr->qr_path)))
                                <img src="{{ public_path('storage/' . $qr->qr_path) }}" width="50">
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <br><br>
    <table width="100%">
        <tr>
            <td style="text-align: center;">Mengetahui,<br>Kepala Sekolah<br><br><br>______________________</td>
            <td style="text-align: center;">Petugas<br><br><br><br>______________________</td>
        </tr>
    </table>
@endsection
