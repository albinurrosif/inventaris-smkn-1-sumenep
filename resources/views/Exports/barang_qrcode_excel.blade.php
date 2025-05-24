<table>
    <thead>
        <tr>
            {{-- <th>No</th> --}}
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
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $index => $qr)
            <tr>
                {{-- <td>{{ $index + 1 }}</td> --}}
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
                <td>{{ $qr->keterangan }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
