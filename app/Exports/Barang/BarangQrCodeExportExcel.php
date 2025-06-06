<?php

namespace App\Exports\Barang;

use App\Models\BarangQrCode;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BarangQrCodeExportExcel implements FromView, ShouldAutoSize, WithTitle, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function view(): View
    {
        $query = BarangQrCode::with(['barang.kategori', 'barang.ruangan'])
            ->when($this->filters['ruangan_id'] ?? null, fn($q, $v) => $q->whereHas('barang', fn($q) => $q->where('id_ruangan', $v)))
            ->when($this->filters['kategori_id'] ?? null, fn($q, $v) => $q->whereHas('barang', fn($q) => $q->where('id_kategori', $v)))
            ->when($this->filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($this->filters['kondisi'] ?? null, fn($q, $v) => $q->where('kondisi', $v))
            ->when($this->filters['tahun'] ?? null, fn($q, $v) => $q->whereHas('barang', fn($q) => $q->where('tahun_pembuatan_pembelian', $v)));

        $data = $query->get();

        return view('exports.barang_qrcode_excel', [
            'data' => $data
        ]);
    }

    public function title(): string
    {
        return 'Data Barang QR';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
