<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetailStokOpname extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'detail_stok_opname'; // [cite: 399]
    protected $primaryKey = 'id'; // [cite: 400]
    public $incrementing = true; // [cite: 400]
    protected $keyType = 'int'; // [cite: 400]
    public $timestamps = true; // [cite: 400]
    protected $dates = ['deleted_at']; // [cite: 400]

    protected $fillable = [
        'id_stok_opname', // [cite: 401]
        'id_barang_qr_code', // [cite: 401]
        'kondisi_tercatat', // Kondisi barang menurut sistem sebelum opname // [cite: 401]
        'kondisi_fisik',    // Kondisi barang menurut hasil pemeriksaan fisik // [cite: 401]
        'catatan_fisik',    // Catatan dari petugas opname // [cite: 401]
        'waktu_pertama_diperiksa',  // Tambahkan ini
        'waktu_terakhir_diperiksa', // Tambahkan ini
    ];

    protected $casts = [
        'kondisi_tercatat' => 'string', // [cite: 402]
        'kondisi_fisik' => 'string', // [cite: 402]
        'waktu_pertama_diperiksa' => 'datetime',  // Tambahkan ini
        'waktu_terakhir_diperiksa' => 'datetime', // Tambahkan ini
        'created_at' => 'datetime', // [cite: 402]
        'updated_at' => 'datetime', // [cite: 402]
        'deleted_at' => 'datetime', // [cite: 402]
    ];

    // Konstanta untuk kondisi_fisik (sesuai migration Anda)
    // Nilai enum di DB: 'Baik','Kurang Baik','Rusak Berat','Hilang','Ditemukan'
    public const KONDISI_BAIK = 'Baik'; // Sebelumnya KONDISI_FISIK_BAIK // [cite: 403]
    public const KONDISI_KURANG_BAIK = 'Kurang Baik'; // Sebelumnya KONDISI_FISIK_KURANG_BAIK // [cite: 404]
    public const KONDISI_RUSAK_BERAT = 'Rusak Berat'; // Sebelumnya KONDISI_FISIK_RUSAK_BERAT // [cite: 404]
    public const KONDISI_HILANG = 'Hilang'; // Sebelumnya KONDISI_FISIK_HILANG // [cite: 404]
    public const KONDISI_DITEMUKAN = 'Ditemukan'; // Sebelumnya KONDISI_FISIK_DITEMUKAN // [cite: 405]

    /**
     * Mendapatkan daftar nilai enum 'kondisi_fisik' yang valid.
     */
    public static function getValidKondisiFisik(): array
    {
        return [
            self::KONDISI_BAIK => 'Baik', // [cite: 406]
            self::KONDISI_KURANG_BAIK => 'Kurang Baik', // [cite: 406]
            self::KONDISI_RUSAK_BERAT => 'Rusak Berat', // [cite: 406]
            self::KONDISI_HILANG => 'Hilang', // [cite: 406]
            self::KONDISI_DITEMUKAN => 'Ditemukan (Tidak Tercatat)', // [cite: 406]
        ];
    }

    // Konstanta untuk kondisi_tercatat (sesuai migration Anda)
    // Nilai enum di DB: 'Baik','Kurang Baik','Rusak Berat','Hilang','Diarsipkan'
    // KONDISI_TERCATAT_BAIK, KONDISI_TERCATAT_KURANG_BAIK, KONDISI_TERCATAT_RUSAK_BERAT, KONDISI_TERCATAT_HILANG
    // sudah ada di BarangQrCode, kita tambahkan KONDISI_DIARSIPKAN di sini untuk kelengkapan.
    // Namun, saat mengisi, lebih baik merujuk ke konstanta BarangQrCode jika kondisi barang aktif.
    public const KONDISI_TERCATAT_DIARSIPKAN = 'Diarsipkan'; // [cite: 409]

    /**
     * Mendapatkan daftar nilai enum 'kondisi_tercatat' yang valid.
     * Ini harus mencerminkan ENUM di database.
     */
    public static function getValidKondisiTercatat(): array
    {
        return [
            BarangQrCode::KONDISI_BAIK => 'Baik', // Merujuk ke BarangQrCode untuk konsistensi nilai
            BarangQrCode::KONDISI_KURANG_BAIK => 'Kurang Baik',
            BarangQrCode::KONDISI_RUSAK_BERAT => 'Rusak Berat',
            BarangQrCode::KONDISI_HILANG => 'Hilang',
            self::KONDISI_TERCATAT_DIARSIPKAN => 'Diarsipkan',
        ];
    }


    public function stokOpname(): BelongsTo
    {
        return $this->belongsTo(StokOpname::class, 'id_stok_opname'); // [cite: 411]
    }

    public function barangQrCode(): BelongsTo
    {
        return $this->belongsTo(BarangQrCode::class, 'id_barang_qr_code')->withTrashed(); // [cite: 412]
    }
}
