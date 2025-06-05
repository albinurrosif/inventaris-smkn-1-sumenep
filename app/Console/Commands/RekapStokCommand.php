<?php

namespace App\Console\Commands;

use App\Models\Barang;
use App\Models\Ruangan;
use App\Models\RekapStok;
use App\Models\BarangQrCode;
use App\Models\StokOpname; // Untuk mencari stok opname terakhir
use App\Models\DetailStokOpname; // Untuk mencari stok opname terakhir
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RekapStokCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sima:rekap-stok {--periode= : Periode rekap dalam format YYYY-MM (opsional, default bulan lalu)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membuat atau memperbarui rekapitulasi stok barang per ruangan untuk periode tertentu.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $periodeInput = $this->option('periode');

        if ($periodeInput) {
            try {
                $targetDate = Carbon::createFromFormat('Y-m', $periodeInput)->endOfMonth();
            } catch (\Exception $e) {
                $this->error('Format periode tidak valid. Gunakan format YYYY-MM.');
                return 1;
            }
        } else {
            // Default ke akhir bulan lalu
            $targetDate = Carbon::now()->subMonth()->endOfMonth();
        }

        $periodeRekap = $targetDate->toDateString();
        $this->info("Memulai proses rekap stok untuk periode: " . $targetDate->isoFormat('MMMM YYYY'));

        try {
            DB::beginTransaction();

            $barangs = Barang::whereNull('deleted_at')->get(); // Hanya barang induk yang aktif
            $ruangans = Ruangan::whereNull('deleted_at')->get(); // Hanya ruangan yang aktif

            if ($barangs->isEmpty() || $ruangans->isEmpty()) {
                $this->info('Tidak ada barang atau ruangan aktif untuk direkap.');
                DB::rollBack();
                return 0;
            }

            foreach ($ruangans as $ruangan) {
                foreach ($barangs as $barang) {
                    $jumlahTercatatSistem = BarangQrCode::where('id_barang', $barang->id)
                        ->where('id_ruangan', $ruangan->id)
                        ->whereNull('deleted_at')
                        ->count();

                    // Mencari jumlah fisik terakhir dari Stok Opname yang sudah selesai
                    // untuk kombinasi barang dan ruangan ini sebelum atau pada periode rekap
                    $jumlahFisikTerakhir = null;
                    $catatanTambahan = 'Rekap sistem periodik.';

                    $latestCompletedSO = StokOpname::where('id_ruangan', $ruangan->id)
                        ->where('status', StokOpname::STATUS_SELESAI) // Asumsi ada konstanta ini
                        ->whereDate('tanggal_opname', '<=', $periodeRekap)
                        ->orderByDesc('tanggal_opname')
                        ->orderByDesc('id') // Untuk kasus jika ada SO di tanggal yang sama
                        ->first();

                    if ($latestCompletedSO) {
                        $jumlahFisikTerakhir = DetailStokOpname::where('id_stok_opname', $latestCompletedSO->id)
                            ->whereHas('barangQrCode', function ($query) use ($barang) {
                                $query->where('id_barang', $barang->id);
                            })
                            ->where('kondisi_fisik', '!=', DetailStokOpname::KONDISI_FISIK_HILANG) // Asumsi ada konstanta ini
                            ->count();
                        $catatanTambahan .= " Ref. Stok Opname ID: {$latestCompletedSO->id} tgl {$latestCompletedSO->tanggal_opname}.";
                    }

                    // Hanya buat/update rekap jika ada unit tercatat atau ada histori fisik
                    if ($jumlahTercatatSistem > 0 || $jumlahFisikTerakhir !== null) {
                        RekapStok::updateOrCreate(
                            [
                                'id_barang'     => $barang->id,
                                'id_ruangan'    => $ruangan->id,
                                'periode_rekap' => $periodeRekap,
                            ],
                            [
                                'jumlah_tercatat_sistem' => $jumlahTercatatSistem,
                                'jumlah_fisik_terakhir'  => $jumlahFisikTerakhir, // Bisa null jika tidak ada SO relevan
                                'catatan'                => $catatanTambahan,
                            ]
                        );
                        $this->line("Rekap untuk Barang: {$barang->nama_barang} di Ruangan: {$ruangan->nama_ruangan} periode {$periodeRekap} diproses.");
                    }
                }
            }

            DB::commit();
            $this->info('Rekap stok berhasil disimpan/diperbarui untuk periode: ' . $targetDate->isoFormat('MMMM YYYY'));
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error saat menjalankan rekap stok command: " . $e->getMessage(), ['exception' => $e]);
            $this->error('Terjadi kesalahan saat memproses rekap stok. Cek log untuk detail.');
            return 1;
        }
    }
}
