<?php

namespace App\Imports;

use App\Models\Barang;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Validation\Rule;

class BarangImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    public function model(array $row)
    {
        return new Barang([
            'kode_barang' => $row['kode_barang'],
            'nama_barang' => $row['nama_barang'],
            'merk_model' => $row['merk_model'],
            'no_seri_pabrik' => $row['no_seri_pabrik'],
            'ukuran' => $row['ukuran'],
            'bahan' => $row['bahan'],
            'tahun_pembuatan_pembelian' => $row['tahun_pembuatan_pembelian'],
            'jumlah_barang' => $row['jumlah_barang'],
            'harga_beli' => $row['harga_beli'],
            'sumber' => $row['sumber'],
            'keadaan_barang' => $row['keadaan_barang'],
            'keterangan_mutasi' => $row['keterangan_mutasi'],
            'id_ruangan' => $row['id_ruangan'],
        ]);
    }

    public function rules(): array
    {
        return [
            '*.kode_barang' => 'required|string|max:50',
            '*.nama_barang' => 'required|string|max:255',
            '*.jumlah_barang' => 'required|integer|min:0',
            '*.id_ruangan' => 'required|exists:ruangan,id',
            '*.keadaan_barang' => ['required', Rule::in(['Baik', 'Kurang Baik', 'Rusak Berat'])],
        ];
    }
}
