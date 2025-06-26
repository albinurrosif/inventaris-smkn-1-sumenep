<?php

namespace App\Exports;

use App\Models\MutasiBarang;
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

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->data;
    }

    /**
     * Menentukan judul untuk setiap kolom di Excel.
     */
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

    /**
     * Memetakan data dari setiap record mutasi ke kolom yang sesuai.
     */
    public function map($mutasi): array
    {
        $asal = 'N/A';
        if ($mutasi->ruanganAsal) {
            $asal = $mutasi->ruanganAsal->nama_ruangan . ' (Ruangan)';
        } elseif ($mutasi->pemegangAsal) {
            $asal = $mutasi->pemegangAsal->username . ' (Personal)';
        } elseif ($mutasi->jenis_mutasi === 'Penempatan Awal') {
            $asal = 'Pengadaan Baru';
        }

        $tujuan = 'N/A';
        if ($mutasi->ruanganTujuan) {
            $tujuan = $mutasi->ruanganTujuan->nama_ruangan . ' (Ruangan)';
        } elseif ($mutasi->pemegangTujuan) {
            $tujuan = $mutasi->pemegangTujuan->username . ' (Personal)';
        }

        return [
            $mutasi->tanggal_mutasi->format('d-m-Y H:i:s'),
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
