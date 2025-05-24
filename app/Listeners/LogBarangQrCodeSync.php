<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\BarangQrCodeSynchronized;
use Illuminate\Support\Facades\Log;

class LogBarangQrCodeSync
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(BarangQrCodeSynchronized $event)
    {
        Log::info('QR Code Barang Synchronized', [
            'barang_id' => $event->barang->id,
            'old_count' => $event->oldCount,
            'new_count' => $event->newCount
        ]);
    }
}
