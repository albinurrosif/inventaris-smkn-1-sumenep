<?php

namespace App\Exports\Barang;

use App\Models\BarangQrCode;
use Illuminate\Http\Request;

class BarangQrCodeExportPdf
{
    protected $filters;

    public function __construct(Request $request)
    {
        $this->filters = $request->only([
            'ruangan_id',
            'kategori_id',
            'status',
            'kondisi',
            'tahun'
        ]);
    }

    public function getFilteredData()
    {
        return BarangQrCode::with(['barang.kategori', 'barang.ruangan'])
            ->when($this->filters['ruangan_id'] ?? null, fn($q, $v) => $q->whereHas('barang', fn($q) => $q->where('id_ruangan', $v)))
            ->when($this->filters['kategori_id'] ?? null, fn($q, $v) => $q->whereHas('barang', fn($q) => $q->where('id_kategori', $v)))
            ->when($this->filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($this->filters['kondisi'] ?? null, fn($q, $v) => $q->where('kondisi', $v))
            ->when($this->filters['tahun'] ?? null, fn($q, $v) => $q->whereHas('barang', fn($q) => $q->where('tahun_pembuatan_pembelian', $v)))
            ->get();
    }
}
