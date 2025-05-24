<?php
// app/Http/Middleware/CheckIncompleteBarang.php
namespace App\Http\Middleware;

use Closure;
use App\Models\Barang;

class CheckIncompleteBarang
{
    public function handle($request, Closure $next)
    {
        // Daftar route yang dianggap sebagai kelanjutan proses
        $allowedRoutes = [
            'barang.input-serial',
            'barang.store-serial',
            'barang.cancel-create',
            'barang.create',
            'barang.edit',
            'barang.edit-step1',
            'barang.update-step1',
            'barang.cancel',
        ];

        if (session()->has('incomplete_barang_id') && !in_array($request->route()->getName(), $allowedRoutes)) {
            $incompleteId = session('incomplete_barang_id');
            $barang = Barang::find($incompleteId);

            if ($barang && $barang->isIncomplete()) {
                return redirect()->route('barang.input-serial', $incompleteId)
                    ->with('warning', 'Anda memiliki proses pembuatan barang yang belum selesai');
            } else {
                session()->forget(['incomplete_barang_id', 'incomplete_started_at']);
            }
        }

        return $next($request);
    }
}
