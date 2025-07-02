<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode as GeneratorQrCode;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Model BarangQrCode merepresentasikan unit fisik dari sebuah barang.
 * Setiap unit memiliki QR Code unik dan detail spesifik.
 */
class BarangQrCode extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nama tabel database yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'barang_qr_codes'; // [cite: 117]

    /**
     * Kunci utama tabel.
     *
     * @var string
     */
    protected $primaryKey = 'id'; // [cite: 118]

    /**
     * Menunjukkan apakah ID model otomatis bertambah (incrementing).
     *
     * @var bool
     */
    public $incrementing = true; // [cite: 120]

    /**
     * Tipe data dari kunci utama.
     *
     * @var string
     */
    protected $keyType = 'int'; // [cite: 122]

    /**
     * Menunjukkan apakah model harus menggunakan timestamps (created_at, updated_at).
     *
     * @var bool
     */
    public $timestamps = true; // [cite: 125]

    /**
     * Atribut tanggal yang harus diperlakukan sebagai instance Carbon.
     * Digunakan untuk SoftDeletes.
     *
     * @var array
     */
    protected $dates = ['deleted_at']; // [cite: 127]

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_barang', // [cite: 129]
        'id_ruangan', // Sekarang bisa NULL // [cite: 129]
        'id_pemegang_personal', // [cite: 129]
        'no_seri_pabrik', // [cite: 129]
        'kode_inventaris_sekolah', // [cite: 129]
        'deskripsi_unit', // [cite: 129]
        'harga_perolehan_unit', // [cite: 129]
        'tanggal_perolehan_unit', // [cite: 129]
        'sumber_dana_unit', // [cite: 129]
        'no_dokumen_perolehan_unit', // [cite: 130]
        'kondisi', // [cite: 130]
        'status', // [cite: 130]
        'qr_path', // [cite: 130]
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'harga_perolehan_unit' => 'decimal:2', // [cite: 132]
        'tanggal_perolehan_unit' => 'date', // [cite: 132]
        'created_at' => 'datetime', // [cite: 132]
        'updated_at' => 'datetime', // [cite: 132]
        'deleted_at' => 'datetime', // [cite: 132]
        'kondisi' => 'string', // [cite: 132]
        'status' => 'string', // [cite: 132]
    ];

    // Konstanta untuk nilai enum 'kondisi'
    public const KONDISI_BAIK = 'Baik'; // [cite: 133]
    public const KONDISI_KURANG_BAIK = 'Kurang Baik'; // [cite: 133]
    public const KONDISI_RUSAK_BERAT = 'Rusak Berat'; // [cite: 134]
    public const KONDISI_HILANG = 'Hilang'; // [cite: 134]
    // public const KONDISI_DALAM_PERBAIKAN = 'Dalam Perbaikan'; // Dipertimbangkan, tapi untuk saat ini status 'Dalam Pemeliharaan' yang dipakai

    // Konstanta untuk nilai enum 'status' (sesuai SQL dump)
    public const STATUS_TERSEDIA = 'Tersedia'; // [cite: 135]
    public const STATUS_DIPINJAM = 'Dipinjam'; // [cite: 136]
    public const STATUS_DALAM_PEMELIHARAAN = 'Dalam Pemeliharaan'; // [cite: 136]
    public const STATUS_DIARSIPKAN = 'Diarsipkan/Dihapus'; // BARU: Ditambahkan untuk konsistensi logging di BarangStatus


    // Di App\Models\BarangQrCode.php
    public static function getKondisiColor(string $kondisi): string
    {
        // Tambahkan pengecekan jika $kondisi null di awal
        if (is_null($kondisi)) {
            return 'text-bg-secondary';
        }
        return match (strtolower($kondisi)) {
            strtolower(self::KONDISI_BAIK) => 'text-bg-success',
            strtolower(self::KONDISI_KURANG_BAIK) => 'text-bg-warning text-dark',
            strtolower(self::KONDISI_RUSAK_BERAT) => 'text-bg-danger',
            strtolower(self::KONDISI_HILANG) => 'text-bg-dark',
            default => 'text-bg-secondary',
        };
    }

    public static function getStatusColor(string $status): string
    {
        // Tambahkan pengecekan jika $status null di awal
        if (is_null($status)) {
            return 'text-bg-secondary';
        }
        return match (strtolower($status)) {
            strtolower(self::STATUS_TERSEDIA) => 'text-bg-success',
            strtolower(self::STATUS_DIPINJAM) => 'text-bg-primary',
            strtolower(self::STATUS_DALAM_PEMELIHARAAN) => 'text-bg-info',
            strtolower(self::STATUS_DIARSIPKAN) => 'text-bg-dark',
            default => 'text-bg-secondary',
        };
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model Barang (induk).
     */
    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang'); // [cite: 138]
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model Ruangan.
     */
    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class, 'id_ruangan'); // [cite: 140]
    }

    /**
     * Mendefinisikan relasi BelongsTo ke model User (pemegang personal).
     */
    public function pemegangPersonal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pemegang_personal'); // [cite: 142]
    }

    /**
     * Mendefinisikan relasi HasMany ke model DetailPeminjaman.
     */
    public function peminjamanDetails(): HasMany
    {
        return $this->hasMany(DetailPeminjaman::class, 'id_barang_qr_code'); // [cite: 144]
    }

    /**
     * Mendefinisikan relasi HasMany ke model MutasiBarang.
     */
    public function mutasiDetails(): HasMany
    {
        return $this->hasMany(MutasiBarang::class, 'id_barang_qr_code'); // [cite: 146]
    }

    /**
     * Mendefinisikan relasi HasMany ke model Pemeliharaan.
     */
    public function pemeliharaanRecords(): HasMany
    {
        return $this->hasMany(Pemeliharaan::class, 'id_barang_qr_code'); // [cite: 148]
    }

    /**
     * Mendefinisikan relasi HasMany ke model BarangStatus.
     */
    public function barangStatuses(): HasMany
    {
        return $this->hasMany(BarangStatus::class, 'id_barang_qr_code'); // [cite: 150]
    }

    /**
     * Mendefinisikan relasi HasMany ke model DetailStokOpname.
     */
    public function stokOpnameDetails(): HasMany
    {
        return $this->hasMany(DetailStokOpname::class, 'id_barang_qr_code'); // [cite: 152]
    }

    /**
     * Mendefinisikan relasi HasOne ke model ArsipBarang.
     */
    public function arsip(): HasOne
    {
        return $this->hasOne(ArsipBarang::class, 'id_barang_qr_code'); // [cite: 154]
    }

    // /**
    //  * Mendapatkan konten yang akan dijadikan data untuk QR Code.
    //  */
    // public function getQrCodeContent(): string
    // {
    //     return $this->kode_inventaris_sekolah ?? ('UNIT_ID_' . $this->id); // [cite: 156, 157]
    // }


    /**
     * Mengubah cara Laravel menemukan model ini dari ID menjadi kode unik.
     * Ini memungkinkan kita menggunakan URL seperti /barang-qr-code/SMKN1-PC-AIO-01-001
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'kode_inventaris_sekolah';
    }

    /**
     * Mengubah konten yang akan dijadikan data untuk QR Code.
     * Sekarang akan berisi URL lengkap ke halaman publik untuk dipindai.
     *
     * @return string
     */
    public function getQrCodeContent(): string
    {
        // Menghasilkan URL absolut ke rute publik yang baru kita buat.
        // Contoh: https://sima.smkn1sumenep.sch.id/scan/SMKN1-PC-AIO-01-001
        return route('scan.redirect', ['barangQrCode' => $this->kode_inventaris_sekolah]);
    }
    /**
     * Menghasilkan kode inventaris sekolah yang unik untuk unit barang.
     */
    public static function generateKodeInventarisSekolah(int $idBarangInduk): string
    {
        $barangInduk = Barang::find($idBarangInduk); // [cite: 158]
        if (!$barangInduk) {
            throw new \Exception("Barang induk dengan ID {$idBarangInduk} tidak ditemukan."); // [cite: 159]
        }

        $prefix = 'SMKN1-' . ($barangInduk->kode_barang ?: 'BRG' . $idBarangInduk) . '-'; // [cite: 160]
        $lastQrCode = self::where('id_barang', $idBarangInduk)
            ->where('kode_inventaris_sekolah', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING_INDEX(kode_inventaris_sekolah, "-", -1) AS UNSIGNED) DESC, kode_inventaris_sekolah DESC')
            ->first(); // [cite: 161]
        $lastNumber = 0; // [cite: 162]
        if ($lastQrCode) {
            $suffix = Str::afterLast($lastQrCode->kode_inventaris_sekolah, '-'); // [cite: 162]
            if (is_numeric($suffix)) {
                $lastNumber = (int)$suffix; // [cite: 163]
            }
        }
        $nextNumber = $lastNumber + 1; // [cite: 164]
        return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT); // [cite: 165]
    }

    /**
     * Membuat instance BarangQrCode baru beserta gambar QR Code-nya.
     * @param int|null $idPemegangPencatat User ID yang melakukan pencatatan (opsional, default user login)
     */
    public static function createWithQrCodeImage(
        int $idBarang,
        ?int $idRuangan,
        ?string $noSeriPabrik = null,
        ?string $kodeInventarisSekolah = null,
        ?float $hargaPerolehanUnit = null,
        ?string $tanggalPerolehanUnit = null,
        ?string $sumberDanaUnit = null, // [cite: 167]
        ?string $noDokumenPerolehanUnit = null,
        string $kondisi = self::KONDISI_BAIK,
        string $status = self::STATUS_TERSEDIA,
        ?string $deskripsiUnit = null,
        ?int $idPemegangPersonal = null,
        ?int $idPemegangPencatat = null
    ): self {
        if ($kodeInventarisSekolah === null) {
            $kodeInventarisSekolah = self::generateKodeInventarisSekolah($idBarang); // [cite: 167]
        }

        $actualIdRuangan = $idRuangan; // [cite: 168]
        if ($idPemegangPersonal !== null) {
            $actualIdRuangan = null; // [cite: 170]
        }

        $qrCodeUnit = new self([
            'id_barang' => $idBarang, // [cite: 174]
            'id_ruangan' => $actualIdRuangan, // [cite: 174]
            'id_pemegang_personal' => $idPemegangPersonal, // [cite: 174]
            'no_seri_pabrik' => $noSeriPabrik, // [cite: 174]
            'kode_inventaris_sekolah' => $kodeInventarisSekolah, // [cite: 174]
            'harga_perolehan_unit' => $hargaPerolehanUnit, // [cite: 174]
            'tanggal_perolehan_unit' => $tanggalPerolehanUnit, // [cite: 174]
            'sumber_dana_unit' => $sumberDanaUnit, // [cite: 175]
            'no_dokumen_perolehan_unit' => $noDokumenPerolehanUnit, // [cite: 175]
            'kondisi' => $kondisi, // [cite: 175]
            'status' => $status, // [cite: 175]
            'deskripsi_unit' => $deskripsiUnit, // [cite: 175]
        ]);
        $qrContent = $qrCodeUnit->getQrCodeContent(); // [cite: 176]
        $directory = 'qr_codes'; // [cite: 176]
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory); // [cite: 176]
        }
        $filename = $directory . '/' . Str::slug($kodeInventarisSekolah ?: 'unit-' . Str::random(10)) . '.svg'; // [cite: 177, 178]

        $qrImageContent = GeneratorQrCode::format('svg')->size(200)->errorCorrection('H')->generate($qrContent); // [cite: 178]
        Storage::disk('public')->put($filename, $qrImageContent); // [cite: 178]

        $qrCodeUnit->qr_path = $filename; // [cite: 178]
        $qrCodeUnit->save(); // Simpan untuk mendapatkan ID // [cite: 179]

        BarangStatus::create([
            'id_barang_qr_code' => $qrCodeUnit->id, // [cite: 179]
            'id_user_pencatat' => $idPemegangPencatat ?? Auth::id(), // [cite: 179]
            'tanggal_pencatatan' => now(), // [cite: 179]
            'kondisi_sebelumnya' => null, // Barang baru // [cite: 179]
            'kondisi_sesudahnya' => $qrCodeUnit->kondisi, // [cite: 180]
            'status_ketersediaan_sebelumnya' => null, // Barang baru // [cite: 180]
            'status_ketersediaan_sesudahnya' => $qrCodeUnit->status, // [cite: 180]
            'id_ruangan_sebelumnya' => null, // Barang baru // [cite: 180]
            'id_ruangan_sesudahnya' => $qrCodeUnit->id_ruangan, // [cite: 180]
            'id_pemegang_personal_sebelumnya' => null, // Barang baru // [cite: 180]
            'id_pemegang_personal_sesudahnya' => $qrCodeUnit->id_pemegang_personal, // [cite: 181]
            'deskripsi_kejadian' => 'Pencatatan unit barang baru.', // [cite: 181]
        ]);
        return $qrCodeUnit; // [cite: 182]
    }

    /**
     * Metode untuk menyerahkan barang ke pemegang personal.
     * @param int $userIdPenerima ID pengguna yang akan menjadi pemegang personal.
     * @param int|null $userIdPencatat ID pengguna yang melakukan pencatatan (opsional).
     * @return bool True jika berhasil, false jika gagal.
     */
    public function assignToPersonal(int $userIdPenerima, ?int $userIdPencatat = null): bool
    {
        if ($this->status === self::STATUS_DIPINJAM) {
            return false; // [cite: 186]
        }

        $kondisiSebelum = $this->kondisi; // [cite: 186]
        $statusKetersediaanSebelum = $this->status; // [cite: 186]
        $ruanganSebelum = $this->id_ruangan; // [cite: 186]
        $pemegangSebelum = $this->id_pemegang_personal; // [cite: 187]

        if ($pemegangSebelum === $userIdPenerima) {
            return true; // [cite: 187]
        }

        $this->id_pemegang_personal = $userIdPenerima; // [cite: 188]
        $this->id_ruangan = null; // Penting: set id_ruangan menjadi NULL // [cite: 189]
        $this->save(); // [cite: 190]

        BarangStatus::create([
            'id_barang_qr_code' => $this->id, // [cite: 191]
            'id_user_pencatat' => $userIdPencatat ?? Auth::id(), // [cite: 191]
            'tanggal_pencatatan' => now(), // [cite: 191]
            'kondisi_sebelumnya' => $kondisiSebelum, // [cite: 191]
            'kondisi_sesudahnya' => $this->kondisi, // Kondisi diasumsikan tidak berubah saat penyerahan // [cite: 191]
            'status_ketersediaan_sebelumnya' => $statusKetersediaanSebelum, // [cite: 191]
            'status_ketersediaan_sesudahnya' => $this->status, // [cite: 192]
            'id_ruangan_sebelumnya' => $ruanganSebelum, // [cite: 192]
            'id_ruangan_sesudahnya' => null, // id_ruangan menjadi NULL // [cite: 192]
            'id_pemegang_personal_sebelumnya' => $pemegangSebelum, // [cite: 192]
            'id_pemegang_personal_sesudahnya' => $this->id_pemegang_personal, // [cite: 192]
            'deskripsi_kejadian' => 'Barang diserahkan ke pemegang personal: ' . ($this->pemegangPersonal->username ?? 'N/A'), // [cite: 192]
        ]);
        return true; // [cite: 193]
    }

    /**
     * Metode untuk mengembalikan barang dari pemegang personal ke sebuah ruangan.
     * @param int $idRuangTujuan ID ruangan tujuan.
     * @param int|null $userIdPencatat ID pengguna yang melakukan pencatatan (opsional).
     * @return bool True jika berhasil, false jika gagal.
     */
    public function returnFromPersonalToRoom(int $idRuangTujuan, ?int $userIdPencatat = null): bool
    {
        if ($this->id_pemegang_personal === null) {
            return false; // [cite: 196]
        }

        $ruanganTujuan = Ruangan::find($idRuangTujuan); // [cite: 196]
        if (!$ruanganTujuan) {
            return false; // [cite: 198]
        }

        $kondisiSebelum = $this->kondisi; // [cite: 198]
        $statusKetersediaanSebelum = $this->status; // [cite: 198]
        $ruanganSebelum = $this->id_ruangan; // Akan NULL // [cite: 198]
        $pemegangSebelum = $this->id_pemegang_personal; // [cite: 199]

        $this->id_ruangan = $idRuangTujuan; // [cite: 199]
        $this->id_pemegang_personal = null; // Pemegang personal di-NULL-kan // [cite: 199]
        $this->save(); // [cite: 201]

        BarangStatus::create([
            'id_barang_qr_code' => $this->id, // [cite: 202]
            'id_user_pencatat' => $userIdPencatat ?? Auth::id(), // [cite: 202]
            'tanggal_pencatatan' => now(), // [cite: 202]
            'kondisi_sebelumnya' => $kondisiSebelum, // [cite: 202]
            'kondisi_sesudahnya' => $this->kondisi, // [cite: 202]
            'status_ketersediaan_sebelumnya' => $statusKetersediaanSebelum, // [cite: 202]
            'status_ketersediaan_sesudahnya' => $this->status, // [cite: 203]
            'id_ruangan_sebelumnya' => $ruanganSebelum, // Akan NULL // [cite: 203]
            'id_ruangan_sesudahnya' => $this->id_ruangan, // [cite: 203]
            'id_pemegang_personal_sebelumnya' => $pemegangSebelum, // [cite: 203]
            'id_pemegang_personal_sesudahnya' => null, // [cite: 203]
            'deskripsi_kejadian' => 'Barang dikembalikan dari pemegang personal (' . (User::find($pemegangSebelum)->username ?? 'N/A') . ') ke ruangan: ' . $ruanganTujuan->nama_ruangan, // [cite: 203]
        ]);
        return true; // [cite: 204]
    }

    /**
     * Metode untuk mentransfer barang dari satu pemegang personal ke pemegang personal lain.
     * @param int $newUserIdPenerima ID pengguna baru yang akan menjadi pemegang personal.
     * @param int|null $userIdPencatat ID pengguna yang melakukan pencatatan (opsional).
     * @return bool True jika berhasil, false jika gagal.
     */
    public function transferPersonalHolder(int $newUserIdPenerima, ?int $userIdPencatat = null): bool
    {
        if ($this->id_pemegang_personal === null) {
            return false; // [cite: 208]
        }
        if ($this->id_pemegang_personal === $newUserIdPenerima) {
            return true; // [cite: 208]
        }
        $newPemegang = User::find($newUserIdPenerima); // [cite: 209]
        if (!$newPemegang) {
            return false; // [cite: 211]
        }

        $kondisiSebelum = $this->kondisi; // [cite: 211]
        $statusKetersediaanSebelum = $this->status; // [cite: 211]
        $pemegangSebelumId = $this->id_pemegang_personal; // [cite: 211]
        $pemegangSebelumUsername = $this->pemegangPersonal->username ?? 'N/A'; // Ambil username sebelum diubah // [cite: 212]

        $this->id_pemegang_personal = $newUserIdPenerima; // [cite: 212]
        $this->save(); // [cite: 213]

        BarangStatus::create([
            'id_barang_qr_code' => $this->id, // [cite: 214]
            'id_user_pencatat' => $userIdPencatat ?? Auth::id(), // [cite: 214]
            'tanggal_pencatatan' => now(), // [cite: 214]
            'kondisi_sebelumnya' => $kondisiSebelum, // [cite: 214]
            'kondisi_sesudahnya' => $this->kondisi, // [cite: 214]
            'status_ketersediaan_sebelumnya' => $statusKetersediaanSebelum, // [cite: 214]
            'status_ketersediaan_sesudahnya' => $this->status, // [cite: 215]
            'id_ruangan_sebelumnya' => $this->id_ruangan, // Tetap NULL // [cite: 215]
            'id_ruangan_sesudahnya' => $this->id_ruangan, // Tetap NULL // [cite: 215]
            'id_pemegang_personal_sebelumnya' => $pemegangSebelumId, // [cite: 215]
            'id_pemegang_personal_sesudahnya' => $this->id_pemegang_personal, // [cite: 215]
            'deskripsi_kejadian' => 'Perpindahan pemegang personal barang dari ' . $pemegangSebelumUsername . ' ke ' . $newPemegang->username, // [cite: 215]
        ]);
        return true; // [cite: 216]
    }

    /**
     * Mendapatkan daftar nilai enum 'kondisi' yang valid.
     */
    public static function getValidKondisi(): array
    {
        return [
            self::KONDISI_BAIK, // [cite: 217]
            self::KONDISI_KURANG_BAIK, // [cite: 217]
            self::KONDISI_RUSAK_BERAT, // [cite: 217]
            self::KONDISI_HILANG // [cite: 217]
        ];
    }

    /**
     * Mendapatkan daftar nilai enum 'status' yang valid.
     */
    public static function getValidStatus(): array
    {
        return [
            self::STATUS_TERSEDIA, // [cite: 219]
            self::STATUS_DIPINJAM, // [cite: 219]
            self::STATUS_DALAM_PEMELIHARAAN, // [cite: 219]
            // self::STATUS_DIARSIPKAN, // Tidak secara langsung settable di sini, tapi ada untuk logging
        ];
    }

    /**
     * Memeriksa apakah unit barang tersedia untuk dipinjam atau digunakan.
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_TERSEDIA; // [cite: 221]
    }

    /**
     * Mengatur status unit barang menjadi 'Dipinjam'.
     */
    public function setBorrowed(): bool
    {
        if ($this->isAvailable()) {
            $this->status = self::STATUS_DIPINJAM; // [cite: 223]
            return $this->save(); // [cite: 225]
        }
        return false; // [cite: 225]
    }

    /**
     * Mengatur status unit barang menjadi 'Tersedia'.
     */
    public function setAvailable(): bool
    {
        $this->status = self::STATUS_TERSEDIA; // [cite: 227]
        return $this->save(); // [cite: 228]
    }

    /**
     * Mengatur status unit barang menjadi 'Dalam Pemeliharaan'.
     */
    public function setInMaintenance(): bool
    {
        $this->status = self::STATUS_DALAM_PEMELIHARAAN; // [cite: 229]
        return $this->save(); // [cite: 230]
    }

    /**
     * Metode boot model untuk mendaftarkan event listener.
     */
    protected static function boot()
    {
        parent::boot(); // [cite: 231]

        static::created(function ($qrCode) {
            if ($qrCode->barang) {
                $qrCode->barang()->increment('total_jumlah_unit'); // [cite: 234]
            }
        });
        static::deleted(function ($qrCode) { // Dipicu oleh soft delete // [cite: 235]
            if (!$qrCode->isForceDeleting()) {
                if ($qrCode->barang) {
                    $qrCode->barang()->decrement('total_jumlah_unit'); // [cite: 235]
                }
            }
        });
        static::restored(function ($qrCode) {
            if ($qrCode->barang) {
                $qrCode->barang()->increment('total_jumlah_unit'); // [cite: 242]
            }
        });
        static::forceDeleted(function ($qrCode) {
            if ($qrCode->qr_path && Storage::disk('public')->exists($qrCode->qr_path)) {
                Storage::disk('public')->delete($qrCode->qr_path); // [cite: 243]
            }
        });
    }

    /**
     * Scope untuk memfilter query unit barang berdasarkan request.
     */
    public function scopeFilter($query, Request $request)
    {
        return $query->when($request->id_barang, fn($q, $id) => $q->where('id_barang', $id))
            ->when($request->id_ruangan, function ($q, $id_ruangan) {
                if ($id_ruangan === 'tanpa-ruangan') {
                    $q->whereNull('id_ruangan');
                } else {
                    $q->where('id_ruangan', $id_ruangan);
                }
            })
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->kondisi, fn($q, $k) => $q->where('kondisi', $k))

            // ===== AWAL PENAMBAHAN KONDISI BARU =====
            ->when($request->arsip_selain_hilang, function ($q) {
                $q->whereHas('arsip', function ($qArsip) {
                    $qArsip->where('jenis_penghapusan', '!=', 'Hilang');
                });
            })
            // ===== AKHIR PENAMBAHAN KONDISI BARU =====

            ->when($request->search, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->where('no_seri_pabrik', 'like', "%{$term}%")
                        ->orWhere('kode_inventaris_sekolah', 'like', "%{$term}%")
                        ->orWhereHas('barang', fn($sub) => $sub->where('nama_barang', 'like', "%{$term}%"));
                });
            });
    }
}
