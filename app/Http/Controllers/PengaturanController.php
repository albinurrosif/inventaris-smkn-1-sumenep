<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pengaturan;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PengaturanController extends Controller
{
    use AuthorizesRequests;
    /**
     * Menampilkan halaman pengaturan.
     */
    public function index(): View
    {
        $this->authorize('view-pengaturan');

        // Ambil semua pengaturan dan kelompokkan berdasarkan grupnya
        $settings = Pengaturan::all()->groupBy('group');

        return view('pages.pengaturan.index', compact('settings'));
    }

    /**
     * Menyimpan perubahan pengaturan.
     */
    public function update(Request $raequest): RedirectResponse
    {
        $this->authorize('update-pengaturan');

        $settings = $request->input('settings', []);

        foreach ($settings as $key => $value) {
            Pengaturan::where('key', $key)->update(['value' => $value]);
        }

        // Handle file upload untuk logo
        if ($request->hasFile('logo_sekolah')) {
            $request->validate(['logo_sekolah' => 'image|mimes:jpeg,png,jpg|max:1024']);

            $setting = Pengaturan::firstWhere('key', 'logo_sekolah');

            // Hapus logo lama jika ada
            if ($setting->value && Storage::disk('public')->exists($setting->value)) {
                Storage::disk('public')->delete($setting->value);
            }

            // Simpan logo baru
            $path = $request->file('logo_sekolah')->store('logos', 'public');
            $setting->update(['value' => $path]);
        }

        // Hapus cache pengaturan jika Anda menggunakan sistem cache
        // Cache::forget('pengaturan');

        return back()->with('success', 'Pengaturan berhasil diperbarui.');
    }
}
