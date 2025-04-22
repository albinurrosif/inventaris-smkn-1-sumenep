@forelse($barang as $key => $item)
    <tr>
        <td>{{ $key + 1 }}</td>
        <td>{{ $item->kode_barang }}</td>
        <td>{{ $item->nama_barang }}</td>
        <td>{{ $item->merk_model ?? '-' }}</td>
        <td>{{ $item->no_seri_pabrik ?? '-' }}</td>
        <td>{{ $item->ukuran ?? '-' }}</td>
        <td>{{ $item->bahan ?? '-' }}</td>
        <td>{{ $item->tahun_pembuatan_pembelian ?? '-' }}</td>
        <td>{{ $item->jumlah_barang }}</td>
        <td>{{ $item->harga_beli ? 'Rp' . number_format($item->harga_beli, 0, ',', '.') : '-' }}</td>
        <td>{{ $item->sumber ?? '-' }}</td>
        <td>{{ $item->ruangan->nama_ruangan ?? '-' }}</td>
        <td>
            @if ($item->keadaan_barang == 'Baik')
                <span class="badge bg-success">Baik</span>
            @elseif ($item->keadaan_barang == 'Kurang Baik')
                <span class="badge bg-warning">Kurang Baik</span>
            @else
                <span class="badge bg-danger">Rusak Berat</span>
            @endif
        </td>
        <td>{{ $item->keterangan_mutasi ?? '-' }}</td>
        <td>
            <div class="d-flex gap-2">
                <a href="{{ route('barang.show', $item->id) }}" class="btn btn-info btn-sm">
                    <i class="mdi mdi-eye"></i>
                </a>
                <button type="button" class="btn btn-warning btn-sm btn-edit-barang"
                    data-barang='@json($item)'>

                    <i class="mdi mdi-pencil"></i>
                </button>

                <script>
                    console.log('Data Barang:', @json($item));
                </script>
                <button type="button" class="btn btn-danger btn-sm btn-delete-barang" data-id="{{ $item->id }}"
                    data-nama="{{ $item->nama_barang }}">
                    <i class="mdi mdi-trash-can"></i>
                </button>

            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="15" class="text-center">Tidak ada data barang</td>
    </tr>
@endforelse
