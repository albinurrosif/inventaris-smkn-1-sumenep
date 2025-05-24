<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Events\BarangQrCodeSynchronized;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;



class Barang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'barang';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'nama_barang',
        'kode_barang',
        'merk_model',
        'ukuran',
        'bahan',
        'tahun_pembuatan_pembelian',
        'jumlah_barang',
        'harga_beli',
        'sumber',

        'menggunakan_nomor_seri', // Field boolean untuk menentukan apakah input manual atau otomatis
        'id_kategori',

    ];

    protected $casts = [
        'menggunakan_nomor_seri' => 'boolean', // Memastikan field ini sebagai boolean
    ];

    /**
     * Relasi ke tabel Ruangan
     */


    /**
     * Relasi ke tabel KategoriBarang
     * Satu barang memiliki satu kategori
     */
    public function kategori()
    {
        return $this->belongsTo(KategoriBarang::class, 'id_kategori');
    }

    /**
     * Relasi ke tabel BarangQrCode (Satu Barang memiliki banyak unit fisik/QR Code)
     */
    public function qrCodes()
    {
        return $this->hasMany(BarangQrCode::class, 'id_barang');
    }

    /**
     * Method untuk mendapatkan nama kategori barang
     * 
     * @return string|null Nama kategori barang atau null jika tidak ada
     */
    public function getKategoriNama()
    {
        return $this->kategori ? $this->kategori->nama_kategori : null;
    }

    public function canSoftDelete()
    {
        return $this->qrCodes()->whereNull('deleted_at')->count() === 0;
    }


    /**
     * Generate nomor seri pabrik otomatis untuk barang
     * Format: kode_barang-nomor_urut (ex: 02.06.02.01.24-001)
     * 
     * @param int $count Jumlah nomor seri yang akan dibuat
     * @return array Array berisi nomor seri yang dibuat
     */
    public function generateSerialNumbers($count = 1)
    {
        $serialNumbers = [];

        $prefix = $this->kode_barang . '-';
        $lastQrCode = BarangQrCode::where('no_seri_pabrik', 'like', $prefix . '%')
            ->orderBy('no_seri_pabrik', 'desc')
            ->first();

        $lastNumber = 0;
        if ($lastQrCode) {
            $suffix = Str::after($lastQrCode->no_seri_pabrik, $prefix);
            if (is_numeric($suffix)) {
                $lastNumber = (int)$suffix;
            }
        }

        $i = 1;
        $tryCount = $lastNumber;
        while (count($serialNumbers) < $count) {
            $tryCount++;
            $formatted = str_pad($tryCount, 3, '0', STR_PAD_LEFT);
            $serial = $prefix . $formatted;

            if (!BarangQrCode::where('no_seri_pabrik', $serial)->exists()) {
                $serialNumbers[] = $serial;
            }

            // Optional: batasi infinite loop
            if ($tryCount > $lastNumber + 1000) {
                break;
            }
        }

        return $serialNumbers;
    }



    /**
     * Metode untuk menghitung jumlah QR Code yang terkait dengan barang
     * dan memperbarui jumlah_barang jika ada perubahan
     * 
     * @return bool True jika jumlah_barang diperbarui, false jika tidak ada perubahan
     */
    public function syncQrCodeCount()
    {
        $currentQrCodeCount = $this->qrCodes()->count();
        $oldCount = $this->jumlah_barang;

        // Validasi: Jumlah QR tidak boleh melebihi jumlah_barang
        if ($currentQrCodeCount > $this->jumlah_barang) {
            throw new \Exception("Jumlah QR Code ($currentQrCodeCount) melebihi jumlah barang yang ditetapkan ($this->jumlah_barang)");
        }

        if ($this->jumlah_barang !== $currentQrCodeCount) {
            $this->jumlah_barang = $currentQrCodeCount;
            $this->save();

            Event::dispatch(new BarangQrCodeSynchronized($this, $oldCount, $currentQrCodeCount));

            return true;
        }

        return false;
    }

    /**
     * Method untuk membuat QR Codes berdasarkan jumlah barang
     * 
     * @param array|null $customSerialNumbers Array berisi nomor seri kustom (diisi jika menggunakan_nomor_seri = true)
     * @return array BarangQrCode[] Array dari QR codes yang dibuat
     */
    public function createQrCodes(?array $customSerialNumbers = null)
    {
        Log::info('createQrCodes() dijalankan', [
            'barang_id' => $this->id,
            'jumlah_qr' => $this->jumlah_barang,
            'existing_qr' => $this->qrCodes()->count(),
            'kode_barang' => $this->kode_barang,
        ]);
        $createdQrCodes = [];

        // Cek apakah menggunakan nomor seri kustom atau otomatis
        if ($this->menggunakan_nomor_seri && $customSerialNumbers) {
            // Menggunakan nomor seri yang diinput user secara manual
            foreach ($customSerialNumbers as $serialNumber) {
                // Generate QR Code image
                $qr_image = QrCode::format('svg')->size(300)->generate($serialNumber);
                $filename = 'qr_codes/' . $serialNumber . '.svg';
                Storage::disk('public')->put($filename, $qr_image);

                $qrCode = BarangQrCode::create([
                    'id_barang' => $this->id,
                    'no_seri_pabrik' => $serialNumber,
                    'kondisi' => 'Baik',
                    'status' => 'Tersedia',
                    'qr_path' => $filename,
                    'keterangan' => null,
                ]);

                $createdQrCodes[] = $qrCode;
            }
        } else {
            // Auto-generate nomor seri
            $serialNumbers = $this->generateSerialNumbers($this->jumlah_barang);

            foreach ($serialNumbers as $serialNumber) {
                // Generate QR Code image
                $qr_image = QrCode::format('svg')->size(300)->generate($serialNumber);
                $filename = 'qr_codes/' . $serialNumber . '.svg';
                Storage::disk('public')->put($filename, $qr_image);

                $qrCode = BarangQrCode::create([
                    'id_barang' => $this->id,
                    'no_seri_pabrik' => $serialNumber,
                    'kondisi' => 'Baik',
                    'status' => 'Tersedia',
                    'qr_path' => $filename,
                    'keterangan' => null,
                ]);

                $createdQrCodes[] = $qrCode;
            }
        }

        // Setelah membuat semua QR Code, perbarui jumlah jika diperlukan
        $this->syncQrCodeCount();

        return $createdQrCodes;
    }

    /**
     * Method untuk generate QR Code tambahan ketika jumlah barang bertambah
     * 
     * @param int $additionalCount Jumlah QR Code tambahan yang akan dibuat
     * @return array BarangQrCode[] Array dari QR codes yang dibuat
     */
    public function generateAdditionalQrCodes($additionalCount)
    {
        $serialNumbers = $this->generateSerialNumbers($additionalCount);
        $createdQrCodes = [];

        foreach ($serialNumbers as $serialNumber) {
            // Generate QR Code image
            $qr_image = QrCode::format('svg')->size(300)->generate($serialNumber);
            $filename = 'qr_codes/' . $serialNumber . '.svg';
            Storage::disk('public')->put($filename, $qr_image);

            $qrCode = BarangQrCode::create([
                'id_barang' => $this->id,
                'no_seri_pabrik' => $serialNumber,
                'kondisi' => 'Baik',
                'status' => 'Tersedia',
                'qr_path' => $filename,
                'keterangan' => null,
            ]);

            $createdQrCodes[] = $qrCode;
        }

        return $createdQrCodes;
    }

    // Boot method untuk event
    protected static function boot()
    {
        parent::boot();

        // Memastikan jumlah QR Code selalu sinkron dengan jumlah barang
        static::updated(function ($barang) {
            if ($barang->isDirty('jumlah_barang')) {
                $oldCount = $barang->getOriginal('jumlah_barang');
                $newCount = $barang->jumlah_barang;

                // Jika jumlah bertambah, maka perlu membuat QR Codes baru
                if ($newCount > $oldCount && !$barang->menggunakan_nomor_seri) {
                    $additionalCount = $newCount - $oldCount;
                    $barang->generateAdditionalQrCodes($additionalCount);
                }

                // Jika jumlah berkurang, maka perlu menghapus sebagian QR Codes
                else if ($newCount < $oldCount && $barang->canReduceQrCodeCount($oldCount - $newCount)) {
                    $reduceCount = $oldCount - $newCount;
                    $qrCodesToDelete = $barang->qrCodes()
                        ->whereNotIn('status', ['Dipinjam', 'Rusak'])
                        ->orderBy('created_at', 'desc')
                        ->limit($reduceCount)
                        ->get();

                    foreach ($qrCodesToDelete as $qrCode) {
                        // Hapus file QR Code jika ada
                        if ($qrCode->qr_path && Storage::disk('public')->exists($qrCode->qr_path)) {
                            Storage::disk('public')->delete($qrCode->qr_path);
                        }
                        $qrCode->delete();
                    }
                }
            }
        });
    }

    /**
     * Method untuk mengecek apakah barang dalam kondisi incomplete
     *
     * @return bool True jika incomplete, false jika tidak
     */
    public function isIncomplete(): bool
    {
        // Barang dianggap incomplete jika:
        // 1. Menggunakan nomor seri tapi belum memiliki QR code/serial
        if ($this->menggunakan_nomor_seri && $this->qrCodes()->count() == 0) {
            return true;
        }

        // 2. Atau kriteria lain yang menandakan ketidaklengkapan
        // Misalnya, jika ada field tertentu yang kosong

        return false;
    }

    /**
     * Cek apakah jumlah QR Code dapat dikurangi
     * 
     * @param int $reduceCount Jumlah yang akan dikurangi
     * @return bool True jika bisa dikurangi, false jika tidak
     */
    public function canReduceQrCodeCount($reduceCount)
    {
        $availableQrCodes = $this->qrCodes()
            ->whereNotIn('status', ['Dipinjam', 'Rusak'])
            ->count();

        return $availableQrCodes >= $reduceCount;
    }

    /**
     * Scope untuk pencarian berdasarkan kondisi barang
     */
    public function scopeByCondition($query, $condition)
    {
        return $query->where('keadaan_barang', $condition);
    }

    /**
     * Scope untuk pencarian berdasarkan kategori barang
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('id_kategori', $categoryId);
    }

    /**
     * Relasi ke tabel DetailPeminjaman
     */
    public function peminjaman()
    {
        return $this->hasMany(DetailPeminjaman::class, 'id_barang');
    }

    /**
     * Relasi ke tabel Pemeliharaan
     */
    public function pemeliharaan()
    {
        return $this->hasMany(Pemeliharaan::class, 'id_barang');
    }

    /**
     * Relasi ke tabel DetailStokOpname
     */
    public function stokOpname()
    {
        return $this->hasMany(DetailStokOpname::class, 'id_barang');
    }
}
