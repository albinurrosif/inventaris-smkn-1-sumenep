<?php

namespace App\Exports;

use App\Models\BarangQrCode;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class InventarisReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $inventaris;

    public function __construct(Collection $inventaris)
    {
        $this->inventaris = $inventaris;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->inventaris;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Kode Inventaris',
            'Nama Barang',
            'Kategori',
            'Merk/Model',
            'No. Seri Pabrik',
            'Lokasi',
            'Pemegang Personal',
            'Tanggal Perolehan',
            'Harga Perolehan (Rp)',
            'Kondisi',
            'Status',
        ];
    }

    /**
     * @param mixed $item
     * @return array
     */
    public function map($item): array
    {
        return [
            $item->kode_inventaris_sekolah,
            optional($item->barang)->nama_barang,
            optional(optional($item->barang)->kategori)->nama_kategori,
            optional($item->barang)->merk_model,
            $item->no_seri_pabrik,
            optional($item->ruangan)->nama_ruangan,
            optional($item->pemegangPersonal)->username,
            \Carbon\Carbon::parse($item->tanggal_perolehan_unit)->format('d-m-Y'),
            $item->harga_perolehan_unit,
            $item->kondisi,
            $item->status,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style untuk baris header (baris 1)
            1    => ['font' => ['bold' => true]],
        ];
    }
}
