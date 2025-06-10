<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class PeminjamanReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $data;

    public function __construct(Collection $data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        // Flatten the data: satu baris per detail peminjaman
        $flattened = new Collection();
        foreach ($this->data as $peminjaman) {
            if ($peminjaman->detailPeminjaman->isEmpty()) {
                // Tambahkan baris untuk peminjaman tanpa detail jika perlu
                $flattened->push([
                    'peminjaman' => $peminjaman,
                    'detail' => null,
                ]);
            } else {
                foreach ($peminjaman->detailPeminjaman as $detail) {
                    $flattened->push([
                        'peminjaman' => $peminjaman,
                        'detail' => $detail,
                    ]);
                }
            }
        }
        return $flattened;
    }

    public function headings(): array
    {
        return [
            'ID Peminjaman',
            'Tujuan',
            'Peminjam',
            'Tgl. Pengajuan',
            'Tgl. Disetujui',
            'Tgl. Harus Kembali',
            'Status Peminjaman',
            'Kode Unit',
            'Nama Barang',
            'Status Unit',
            'Kondisi Awal',
            'Kondisi Kembali',
        ];
    }

    public function map($row): array
    {
        $peminjaman = $row['peminjaman'];
        $detail = $row['detail'];

        return [
            $peminjaman->id,
            $peminjaman->tujuan_peminjaman,
            optional($peminjaman->guru)->username,
            $peminjaman->tanggal_pengajuan ? \Carbon\Carbon::parse($peminjaman->tanggal_pengajuan)->format('d-m-Y') : '-',
            $peminjaman->tanggal_disetujui ? \Carbon\Carbon::parse($peminjaman->tanggal_disetujui)->format('d-m-Y') : '-',
            $peminjaman->tanggal_harus_kembali ? \Carbon\Carbon::parse($peminjaman->tanggal_harus_kembali)->format('d-m-Y') : '-',
            $peminjaman->status,
            optional(optional($detail)->barangQrCode)->kode_inventaris_sekolah,
            optional(optional(optional($detail)->barangQrCode)->barang)->nama_barang,
            optional($detail)->status_unit,
            optional($detail)->kondisi_sebelum,
            optional($detail)->kondisi_setelah,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
