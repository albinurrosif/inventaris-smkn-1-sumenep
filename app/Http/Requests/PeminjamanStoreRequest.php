<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // Import User model
use App\Models\BarangQrCode; // Import BarangQrCode model

class PeminjamanStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Otorisasi sebenarnya akan ditangani oleh PeminjamanPolicy di controller.
     * Di sini kita bisa pastikan user adalah Guru.
     */
    public function authorize(): bool
    {
        // Hanya Guru yang boleh membuat pengajuan peminjaman baru melalui form ini
        return Auth::check() && Auth::user()->hasRole(User::ROLE_GURU);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'tujuan_peminjaman' => 'required|string|max:1000',
            'id_barang_qr_code' => 'required|array|min:1',
            'id_barang_qr_code.*' => [
                'required',
                'integer',
                'exists:barang_qr_codes,id',
                function ($attribute, $value, $fail) {
                    $barang = BarangQrCode::find($value);
                    if ($barang) {
                        if ($barang->status !== BarangQrCode::STATUS_TERSEDIA) {
                            $fail("Barang '{$barang->barang->nama_barang} ({$barang->kode_inventaris_sekolah})' tidak tersedia untuk dipinjam (status: {$barang->status}).");
                        }
                        if (!in_array($barang->kondisi, [BarangQrCode::KONDISI_BAIK, BarangQrCode::KONDISI_KURANG_BAIK])) {
                             $fail("Barang '{$barang->barang->nama_barang} ({$barang->kode_inventaris_sekolah})' dalam kondisi {$barang->kondisi} dan tidak dapat dipinjam.");
                        }
                        if ($barang->deleted_at) {
                            $fail("Barang '{$barang->barang->nama_barang} ({$barang->kode_inventaris_sekolah})' sudah tidak aktif/diarsipkan.");
                        }
                         // Cek apakah barang sudah ada di detail peminjaman lain yang aktif
                        $existingDetail = \App\Models\DetailPeminjaman::where('id_barang_qr_code', $value)
                            ->whereHas('peminjaman', function ($qPeminjaman) {
                                $qPeminjaman->whereNotIn('status', [
                                    \App\Models\Peminjaman::STATUS_SELESAI,
                                    \App\Models\Peminjaman::STATUS_DITOLAK,
                                    \App\Models\Peminjaman::STATUS_DIBATALKAN
                                ]);
                            })->exists();
                        if ($existingDetail) {
                            $fail("Barang '{$barang->barang->nama_barang} ({$barang->kode_inventaris_sekolah})' sudah dalam pengajuan lain atau sedang dipinjam.");
                        }
                    }
                }
            ],
            'tanggal_rencana_pinjam' => 'required|date|after_or_equal:today',
            'tanggal_harus_kembali' => 'required|date|after:tanggal_rencana_pinjam',
            'catatan_peminjam' => 'nullable|string|max:1000',
            'id_ruangan_tujuan_peminjaman' => 'nullable|integer|exists:ruangans,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'tujuan_peminjaman.required' => 'Tujuan peminjaman wajib diisi.',
            'id_barang_qr_code.required' => 'Minimal satu barang harus dipilih untuk dipinjam.',
            'id_barang_qr_code.min' => 'Minimal satu barang harus dipilih untuk dipinjam.',
            'id_barang_qr_code.*.exists' => 'Barang yang dipilih tidak valid.',
            'tanggal_rencana_pinjam.required' => 'Tanggal rencana pinjam wajib diisi.',
            'tanggal_rencana_pinjam.date' => 'Format tanggal rencana pinjam tidak valid.',
            'tanggal_rencana_pinjam.after_or_equal' => 'Tanggal rencana pinjam tidak boleh kurang dari hari ini.',
            'tanggal_harus_kembali.required' => 'Tanggal harus kembali wajib diisi.',
            'tanggal_harus_kembali.date' => 'Format tanggal harus kembali tidak valid.',
            'tanggal_harus_kembali.after' => 'Tanggal harus kembali harus setelah tanggal rencana pinjam.',
            'id_ruangan_tujuan_peminjaman.exists' => 'Ruangan tujuan peminjaman tidak valid.',
        ];
    }
}