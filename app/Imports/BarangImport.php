<?php

namespace App\Imports;

use App\Models\Barang;
use App\Models\Ruangan;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class BarangImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    public function model(array $row)
    {
        // Temukan ruangan berdasarkan nama_ruangan dari Excel
        $ruangan = Ruangan::where('nama_ruangan', $row['nama_ruangan'])->first();

        return new Barang([
            'kode_barang' => $row['kode_barang'],
            'nama_barang' => $row['nama_barang'],
            'merk_model' => $row['merk_model'] ?? null,
            'no_seri_pabrik' => $row['no_seri_pabrik'] ?? null,
            'ukuran' => $row['ukuran'] ?? null,
            'bahan' => $row['bahan'] ?? null,
            'tahun_pembuatan_pembelian' => $row['tahun_pembuatan_pembelian'] ?? null,
            'jumlah_barang' => $row['jumlah_barang'],
            'harga_beli' => $row['harga_beli'] ?? null,
            'sumber' => $row['sumber'] ?? null,
            'keadaan_barang' => $row['keadaan_barang'],
            'keterangan_mutasi' => $row['keterangan_mutasi'] ?? null,
            'id_ruangan' => $ruangan ? $ruangan->id : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'kode_barang' => 'required|string|max:50',
            'nama_barang' => 'required|string|max:255',
            'jumlah_barang' => 'required|integer|min:0',
            'nama_ruangan' => 'required|exists:ruangan,nama_ruangan',
            'keadaan_barang' => ['required', Rule::in(['Baik', 'Kurang Baik', 'Rusak Berat'])],
            'merk_model' => 'nullable|string|max:255',
            'no_seri_pabrik' => 'nullable|string|max:100',
            'ukuran' => 'nullable|string|max:100',
            'bahan' => 'nullable|string|max:100',
            'tahun_pembuatan_pembelian' => 'nullable|integer|min:1900|max:' . date('Y'),
            'harga_beli' => 'nullable|numeric|min:0',
            'sumber' => 'nullable|string|max:100',
            'keterangan_mutasi' => 'nullable|string',
        ];
    }

    public function headingRow(): int
    {
        return 1; // Asumsi baris header ada di baris pertama file Excel
    }
}
