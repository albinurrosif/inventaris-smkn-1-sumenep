{{--
|--------------------------------------------------------------------------
| Partial View: _item_detail_row.blade.php
|--------------------------------------------------------------------------
|
| File ini merepresentasikan SATU BARIS (<tr>) di dalam tabel detail
| stok opname. File ini akan di-include berulang kali oleh view utama
| dan juga dirender melalui AJAX saat menambahkan barang temuan.
|
| Variabel yang dibutuhkan:
| - $detail: Instance dari model DetailStokOpname.
| - $index: Nomor urut baris.
| - $stokOpname: Instance dari model StokOpname (induk).
| - $kondisiFisikList: Array dari daftar kondisi fisik.
| - $rolePrefix: Prefix route ('admin.' atau 'operator.').
|
--}}

@php
    $disabled = $stokOpname->trashed() || $stokOpname->status !== \App\Models\StokOpname::STATUS_DRAFT;
@endphp
<tr id="row-detail-{{ $detail->id }}" data-detail-id="{{ $detail->id }}">
    <td class="text-center">{{ $index + 1 }}</td>
    <td>
        @if ($detail->barangQrCode)
            <a href="{{ route($rolePrefix . 'barang-qr-code.show', $detail->barangQrCode->kode_inventaris_sekolah) }}" target="_blank"
                data-bs-toggle="tooltip" title="Lihat detail unit barang">
                <code>{{ $detail->barangQrCode->kode_inventaris_sekolah }}</code>
            </a>
            @if ($detail->barangQrCode->trashed())
                <span class="badge bg-dark ms-1">Diarsipkan</span>
            @endif
        @else
            <span class="text-danger">Unit Dihapus Permanen</span>
        @endif
    </td>
    <td>{{ optional(optional($detail->barangQrCode)->barang)->nama_barang ?? 'N/A' }}</td>
    <td>{{ optional($detail->barangQrCode)->no_seri_pabrik ?: '-' }}</td>
    <td class="text-center">
        @php
            // Menangani kondisi 'Baru' yang tidak ada di helper warna
            $kondisiTercatat = $detail->kondisi_tercatat;
            $badgeColor =
                $kondisiTercatat === 'Baru'
                    ? 'text-bg-info'
                    : \App\Models\BarangQrCode::getKondisiColor($kondisiTercatat);
        @endphp
        <span class="badge {{ $badgeColor }}">{{ $kondisiTercatat ?? 'N/A' }}</span>
    </td>
    <td>
        @if ($stokOpname->status === 'Draft' && !$stokOpname->trashed())
            <select name="kondisi_fisik" class="form-select form-control-sm-custom kondisi-fisik-input select2-kondisi">
                <option value="">-- Pilih --</option>
                @foreach ($kondisiFisikList as $key => $value)
                    <option value="{{ $key }}" {{ $detail->kondisi_fisik == $key ? 'selected' : '' }}>
                        {{ $value }}
                    </option>
                @endforeach
            </select>
        @else
            @php
                 $kondisiFisik = $detail->kondisi_fisik;
                 $badgeColorFisik = $kondisiFisik === 'Baru' ? 'text-bg-info' : \App\Models\BarangQrCode::getKondisiColor($kondisiFisik);
            @endphp
            <span class="badge {{ $badgeColorFisik }}">{{ $kondisiFisikList[$detail->kondisi_fisik] ?? ($detail->kondisi_fisik ?? '-') }}</span>
        @endif
    </td>
    <td>
        @if ($stokOpname->status === 'Draft' && !$stokOpname->trashed())
            <textarea name="catatan_fisik" class="form-control form-control-sm-custom catatan-fisik-input" rows="1">{{ $detail->catatan_fisik }}</textarea>
        @else
            {{ $detail->catatan_fisik ?? '-' }}
        @endif
    </td>
    {{-- KOLOM BARU UNTUK WAKTU --}}
    <td class="text-center" id="waktu-periksa-{{ $detail->id }}">
        @if ($detail->waktu_terakhir_diperiksa)
            {{ \Carbon\Carbon::parse($detail->waktu_terakhir_diperiksa)->isoFormat('HH:mm:ss') }}
        @else
            -
        @endif
    </td>
    <td class="text-center">
        @if ($stokOpname->status === 'Draft' && !$stokOpname->trashed())
            @can('processDetails', $stokOpname)
                <button type="button" class="btn btn-success btn-sm btn-save-detail" data-detail-id="{{ $detail->id }}"
                    data-bs-toggle="tooltip" title="Simpan Baris Ini">
                    <i class="fas fa-save"></i>
                </button>
            @endcan
        @else
            <i class="fas fa-check-circle text-success" data-bs-toggle="tooltip" title="Selesai"></i>
        @endif
    </td>
</tr>
