<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BarangQrCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'barang_qr_code';

    protected $fillable = [
        'id_barang',
        'no_seri_pabrik',
        'keadaan_barang',
        'status',
        'keterangan',

        'qr_path',

        'id_ruangan',
        'alasan_penghapusan',
        'berita_acara',
        'foto_pendukung',
        'deleted_by',

    ];

    /**
     * Enum values untuk keadaan_barang barang
     */
    const KEADAAN_BARANG_BAIK = 'Baik';
    const KEADAAN_BARANG_KURANG_BAIK = 'Kurang Baik';
    const KEADAAN_BARANG_RUSAK_BERAT = 'Rusak Berat';

    /**
     * Enum values untuk status barang
     */
    const STATUS_TERSEDIA = 'Tersedia';
    const STATUS_DIPINJAM = 'Dipinjam';
    const STATUS_HILANG = 'Hilang';
    const STATUS_MAINTENANCE = 'Maintenance';

    /**
     * Relasi ke tabel Barang
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    /**
     * Relasi ke tabel Ruangan
     */
    public function ruangan()
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan');
    }

    /**
     * Relasi ke User yang menghapus barang (opsional)
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }


    /**
     * Relasi ke tabel MutasiBarang
     * Satu QR code bisa memiliki banyak mutasi
     */


    /**
     * Method untuk mendapatkan format QR code yang akan digunakan
     * 
     * @return string
     */
    public function getQrCodeContent()
    {
        return $this->no_seri_pabrik;
    }

    /**
     * Method untuk membuat batch QR Code untuk sebuah barang
     * 
     * @param Barang $barang Barang yang akan dibuat QR Codenya
     * @param array|null $customSerialNumbers Array nomor seri kustom jika menggunakan input manual
     * @return array Array dari QR Code yang dibuat
     */
    public static function createBatchForBarang(Barang $barang, ?array $customSerialNumbers = null)
    {
        $createdQrCodes = [];

        // Cek apakah menggunakan nomor seri manual atau otomatis
        if ($barang->menggunakan_nomor_seri && $customSerialNumbers) {
            // Menggunakan nomor seri kustom yang diinput user
            foreach ($customSerialNumbers as $serialNumber) {
                $qrCode = self::create([
                    'id_barang' => $barang->id,
                    'no_seri_pabrik' => $serialNumber,
                    'keadaan_barang' => self::KEADAAN_BARANG_BAIK,
                    'status' => self::STATUS_TERSEDIA,
                    'keterangan' => null,
                ]);

                $createdQrCodes[] = $qrCode;
            }
        } else {
            // Menggunakan nomor seri yang digenerate otomatis oleh sistem
            $serialNumbers = $barang->generateSerialNumbers($barang->jumlah_barang);

            foreach ($serialNumbers as $serialNumber) {
                $qrCode = self::create([
                    'id_barang' => $barang->id,
                    'no_seri_pabrik' => $serialNumber,
                    'keadaan_barang' => self::KEADAAN_BARANG_BAIK,
                    'status' => self::STATUS_TERSEDIA,
                    'keterangan' => null,
                ]);

                $createdQrCodes[] = $qrCode;
            }
        }

        // Update jumlah barang di parent untuk memastikan konsistensi data
        $barang->syncQrCodeCount();

        return $createdQrCodes;
    }

    /**
     * Method untuk membuat satu QR Code
     * 
     * @param Barang $barang Barang yang akan dibuat QR Codenya
     * @param string|null $serialNumber Nomor seri kustom (jika null, akan digenerate)
     * @return BarangQrCode
     */
    public static function createSingleForBarang(Barang $barang, ?string $serialNumber = null)
    {
        if ($serialNumber === null) {
            $serialNumbers = $barang->generateSerialNumbers(1);
            $serialNumber = $serialNumbers[0];
        }

        $qrCode = self::create([
            'id_barang' => $barang->id,
            'no_seri_pabrik' => $serialNumber,
            'keadaan_barang' => self::KEADAAN_BARANG_BAIK,
            'status' => self::STATUS_TERSEDIA,
            'keterangan' => null,
        ]);

        // Update jumlah barang di parent untuk memastikan konsistensi data
        $barang->syncQrCodeCount();

        return $qrCode;
    }

    /**
     * Method untuk mendapatkan semua keadaan barang yang valid
     * 
     * @return array
     */
    public static function getValidKeadaanBarang()
    {
        return [
            self::KEADAAN_BARANG_BAIK,
            self::KEADAAN_BARANG_KURANG_BAIK,
            self::KEADAAN_BARANG_RUSAK_BERAT
        ];
    }

    /**
     * Method untuk mendapatkan semua status yang valid
     * 
     * @return array
     */
    public static function getValidStatus()
    {
        return [
            self::STATUS_TERSEDIA,
            self::STATUS_DIPINJAM,
            self::STATUS_HILANG,
            self::STATUS_MAINTENANCE
        ];
    }

    /**
     * Method untuk mengecek apakah barang tersedia (dapat dipinjam)
     * 
     * @return bool
     */
    public function isAvailable()
    {
        return $this->status === self::STATUS_TERSEDIA;
    }

    /**
     * Method untuk mengubah status menjadi dipinjam
     * 
     * @return bool
     */
    public function setBorrowed()
    {
        if ($this->isAvailable()) {
            $this->status = self::STATUS_DIPINJAM;
            return $this->save();
        }

        return false;
    }

    /**
     * Method untuk mengubah status menjadi tersedia
     * 
     * @return bool
     */
    public function setAvailable()
    {
        $this->status = self::STATUS_TERSEDIA;
        return $this->save();
    }
}
