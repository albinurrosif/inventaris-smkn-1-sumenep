<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class PemeliharaanReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'ID Laporan',
            'Kode Unit',
            'Nama Barang',
            'Deskripsi Kerusakan',
            'Tgl. Lapor',
            'Pelapor',
            'Status Pengajuan',
            'Status Pengerjaan',
            'PIC',
            'Tgl. Mulai',
            'Tgl. Selesai',
            'Biaya (Rp)',
            'Hasil Akhir',
        ];
    }

    public function map($item): array
    {
        return [
            $item->id,
            optional($item->barangQrCode)->kode_inventaris_sekolah,
            optional(optional($item->barangQrCode)->barang)->nama_barang,
            $item->catatan_pengajuan,
            \Carbon\Carbon::parse($item->tanggal_pengajuan)->format('d-m-Y'),
            optional($item->pengaju)->username,
            $item->status_pengajuan,
            $item->status_pengerjaan,
            optional($item->operatorPengerjaan)->username,
            $item->tanggal_mulai_pengerjaan ? \Carbon\Carbon::parse($item->tanggal_mulai_pengerjaan)->format('d-m-Y') : '-',
            $item->tanggal_selesai_pengerjaan ? \Carbon\Carbon::parse($item->tanggal_selesai_pengerjaan)->format('d-m-Y') : '-',
            $item->biaya ?? 0,
            $item->hasil_pemeliharaan,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
