<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RekapStokCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rekap:stok';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merekam stok barang per semester';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DB::table('rekap_stok')->insertUsing(
            ['id_barang', 'id_ruangan', 'semester', 'tahun', 'stok', 'created_at', 'updated_at'],
            DB::table('barang')->selectRaw(
                'id_barang, id_ruangan, 
                (CASE WHEN MONTH(CURDATE()) <= 6 THEN "I" ELSE "II" END) as semester, 
                YEAR(CURDATE()) as tahun, 
                stok, NOW(), NOW()'
            )
        );

        $this->info('Rekap stok berhasil disimpan!');
    }
}
