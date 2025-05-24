<?php

namespace App\Observers;

use App\Models\Barang;
use App\Models\BarangQrCode;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BarangObserver
{

    public function updating(Barang $barang)
    {
        // Kosongkan karena logika pindah ke model
    }


    /**
     * Handle the Barang "created" event.
     */
    public function created(Barang $barang): void
    {
        //
    }

    /**
     * Handle the Barang "updated" event.
     */
    public function updated(Barang $barang): void
    {
        //
    }

    /**
     * Handle the Barang "deleted" event.
     */
    public function deleted(Barang $barang): void
    {
        //
    }

    /**
     * Handle the Barang "restored" event.
     */
    public function restored(Barang $barang): void
    {
        //
    }

    /**
     * Handle the Barang "force deleted" event.
     */
    public function forceDeleted(Barang $barang): void
    {
        //
    }
}
