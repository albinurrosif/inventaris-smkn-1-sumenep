<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class MutasiReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $data;

    public function __construct($data)
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
            'Tanggal Mutasi',
            'Kode Unit',
            'Nama Barang',
            'Jenis Mutasi',
            'Lokasi/Pemegang Asal',
            'Lokasi/Pemegang Tujuan',
            'Admin Pelaksana',
            'Alasan Pemindahan',
        ];
    }

    public function map($mutasi): array
    {
        $asal = 'N/A';
        if ($mutasi->ruanganAsal) $asal = $mutasi->ruanganAsal->nama_ruangan . ' (Ruangan)';
        elseif ($mutasi->pemegangAsal) $asal = $mutasi->pemegangAsal->username . ' (Personal)';

        $tujuan = 'N/A';
        if ($mutasi->ruanganTujuan) $tujuan = $mutasi->ruanganTujuan->nama_ruangan . ' (Ruangan)';
        elseif ($mutasi->pemegangTujuan) $tujuan = $mutasi->pemegangTujuan->username . ' (Personal)';

        return [
            $mutasi->tanggal_mutasi->format('Y-m-d H:i:s'),
            optional($mutasi->barangQrCode)->kode_inventaris_sekolah,
            optional(optional($mutasi->barangQrCode)->barang)->nama_barang,
            $mutasi->jenis_mutasi,
            $asal,
            $tujuan,
            optional($mutasi->admin)->username,
            $mutasi->alasan_pemindahan,
        ];
    }
}