<?php
// app/Console/Commands/CleanupIncompleteBarang.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Barang;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CleanupIncompleteBarang extends Command
{
    protected $signature = 'barang:cleanup-incomplete {--hours=24}';
    protected $description = 'Cleanup barang yang pembuatannya tidak diselesaikan';

    public function handle()
    {
        $hours = $this->option('hours');
        $cutoff = Carbon::now()->subHours($hours);

        // Cari barang yang dibuat dalam waktu tertentu dan masih incomplete
        $incompleteBarang = Barang::where('created_at', '<', $cutoff)
            ->whereDoesntHave('qrCodes')  // Tidak memiliki QR codes (belum selesai)
            ->where('menggunakan_nomor_seri', true)  // Yang seharusnya memiliki QR codes
            ->get();

        $count = 0;
        foreach ($incompleteBarang as $barang) {
            // Hapus barang yang incomplete
            $barang->delete();
            $count++;
        }

        $this->info("Berhasil menghapus {$count} barang yang tidak diselesaikan pembuatannya");
    }
}
