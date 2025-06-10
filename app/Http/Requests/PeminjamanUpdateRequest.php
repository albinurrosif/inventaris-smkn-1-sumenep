<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // Import User model
use App\Models\Peminjaman; // Import Peminjaman model
use App\Models\BarangQrCode; // Import BarangQrCode model
use Carbon\Carbon;

class PeminjamanUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Otorisasi sebenarnya akan ditangani oleh PeminjamanPolicy di controller.
     */
    public function authorize(): bool
    {
        // Policy akan menangani siapa yang boleh update berdasarkan status peminjaman
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $peminjaman = $this->route('peminjaman');

        $rules = [
            'tujuan_peminjaman' => 'sometimes|required|string|max:1000',
            // PENYEMPURNAAN: Aturan tanggal disederhanakan dan dibuat konsisten
            'tanggal_rencana_pinjam' => 'sometimes|required|date|after_or_equal:today',
            'tanggal_harus_kembali' => [
                'sometimes',
                'required',
                'date',
                'after:tanggal_rencana_pinjam',
                'before_or_equal:' . Carbon::parse($this->input('tanggal_rencana_pinjam'))->addDays(14)->format('Y-m-d')
            ],
            'catatan_peminjam' => 'nullable|string|max:1000',
            'id_ruangan_tujuan_peminjaman' => 'nullable|integer|exists:ruangans,id',
        ];

        if ($user->hasRole(User::ROLE_GURU) && $peminjaman && $peminjaman->status === Peminjaman::STATUS_MENUNGGU_PERSETUJUAN) {
            $rules['id_barang_qr_code'] = 'sometimes|required|array|min:1';
            $rules['id_barang_qr_code.*'] = [
                'sometimes',
                'required',
                'integer',
                'exists:barang_qr_codes,id',
                function ($attribute, $value, $fail) use ($peminjaman) {
                    $barang = BarangQrCode::find($value);
                    $itemIndex = array_search($value, $this->input('id_barang_qr_code') ?? [], true);
                    $keyAttribute = "id_barang_qr_code.{$itemIndex}";


                    if ($barang) {
                        // Cek apakah barang ini adalah item yang sudah ada di peminjaman ini sebelumnya (saat edit)
                        $isExistingItemInThisPeminjaman = $peminjaman->detailPeminjaman()
                            ->where('id_barang_qr_code', $value)
                            ->exists();

                        if (!$isExistingItemInThisPeminjaman) { // Hanya validasi status & kondisi untuk item BARU yang ditambahkan
                            if ($barang->status !== BarangQrCode::STATUS_TERSEDIA) {
                                $fail("Barang baru '{$barang->barang->nama_barang} ({$barang->kode_inventaris_sekolah})' tidak tersedia (status: {$barang->status}).");
                            }
                            if (!in_array($barang->kondisi, [BarangQrCode::KONDISI_BAIK, BarangQrCode::KONDISI_KURANG_BAIK])) {
                                $fail("Barang baru '{$barang->barang->nama_barang} ({$barang->kode_inventaris_sekolah})' dalam kondisi {$barang->kondisi} dan tidak dapat dipinjam.");
                            }
                            if ($barang->deleted_at) {
                                $fail("Barang baru '{$barang->barang->nama_barang} ({$barang->kode_inventaris_sekolah})' sudah tidak aktif/diarsipkan.");
                            }
                            // Cek apakah barang sudah ada di detail peminjaman lain yang aktif
                            $existingDetailInOtherPeminjaman = \App\Models\DetailPeminjaman::where('id_barang_qr_code', $value)
                                ->where('id_peminjaman', '!=', $peminjaman->id) // Peminjaman lain
                                ->whereHas('peminjaman', function ($qPeminjaman) {
                                    $qPeminjaman->whereNotIn('status', [
                                        Peminjaman::STATUS_SELESAI,
                                        Peminjaman::STATUS_DITOLAK,
                                        Peminjaman::STATUS_DIBATALKAN
                                    ]);
                                })->exists();
                            if ($existingDetailInOtherPeminjaman) {
                                $fail("Barang baru '{$barang->barang->nama_barang} ({$barang->kode_inventaris_sekolah})' sudah dalam pengajuan lain atau sedang dipinjam.");
                            }
                        }
                    }
                }
            ];
        }

        // Admin atau Operator dapat menambahkan catatan operator
        if ($user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR])) {
            $rules['catatan_operator'] = 'nullable|string|max:255';
        }

        // Validasi tanggal_rencana_pinjam tidak boleh mundur jika sudah ada isinya
        if ($peminjaman && $peminjaman->tanggal_rencana_pinjam) {
            $rules['tanggal_rencana_pinjam'] .= '|after_or_equal:' . Carbon::parse($peminjaman->tanggal_rencana_pinjam)->format('Y-m-d');
        } else {
            $rules['tanggal_rencana_pinjam'] .= '|after_or_equal:today';
        }

        return $rules;
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
            'tanggal_rencana_pinjam.after_or_equal' => 'Tanggal rencana pinjam tidak boleh kurang dari tanggal rencana pinjam sebelumnya atau hari ini.',
            'tanggal_harus_kembali.required' => 'Tanggal harus kembali wajib diisi.',
            'tanggal_harus_kembali.date' => 'Format tanggal harus kembali tidak valid.',
            'tanggal_harus_kembali.after' => 'Tanggal kembali harus setelah tanggal pinjam.',
            'id_ruangan_tujuan_peminjaman.exists' => 'Ruangan tujuan peminjaman tidak valid.',
            'catatan_operator.max' => 'Catatan operator maksimal 255 karakter.',
            'tanggal_harus_kembali.before_or_equal' => 'Durasi peminjaman tidak boleh lebih dari 14 hari.',
        ];
    }
}
