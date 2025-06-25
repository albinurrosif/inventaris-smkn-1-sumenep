<?php

// File: app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;


/**
 * Model User merepresentasikan pengguna dalam sistem.
 * Pengguna dapat memiliki peran sebagai Admin, Operator, atau Guru.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Nama tabel database yang terkait dengan model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * Kunci utama tabel.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Menunjukkan apakah ID model otomatis bertambah (incrementing).
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Tipe data dari kunci utama.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Menunjukkan apakah model harus menggunakan timestamps (created_at, updated_at).
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Atribut tanggal yang harus diperlakukan sebagai instance Carbon.
     * Digunakan untuk SoftDeletes.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    // Konstanta untuk peran pengguna. Sesuai dengan enum di SQL dump: enum('Admin','Operator','Guru')
    public const ROLE_ADMIN = 'Admin';
    public const ROLE_OPERATOR = 'Operator';
    public const ROLE_GURU = 'Guru';

    /**
     * Mengembalikan daftar peran pengguna yang valid.
     *
     * @return array<string>
     */
    public static function getRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_OPERATOR,
            self::ROLE_GURU,
        ];
    }

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
    ];

    /**
     * Atribut yang harus disembunyikan saat serialisasi (misalnya, ke JSON).
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => 'string', // Enum disimpan sebagai string
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];


    /**
     * Mendapatkan prefix nama rute berdasarkan peran pengguna.
     * Contoh: 'admin.', 'operator.', 'guru.', atau string kosong jika tidak ada peran yang cocok.
     *
     * @return string
     */
    public function getRolePrefix(): string
    {
        $role = $this->role; // Ambil peran pengguna (seharusnya 'Admin', 'Operator', atau 'Guru')

        // Perbandingan case-sensitive dengan konstanta yang sudah ada
        if ($role === self::ROLE_ADMIN) {
            return 'admin.';
        } elseif ($role === self::ROLE_OPERATOR) {
            return 'operator.';
        } elseif ($role === self::ROLE_GURU) {
            return 'guru.';
        }

        return ''; // Fallback jika peran tidak dikenal
    }

    /**
     * Memeriksa apakah pengguna memiliki salah satu peran yang diberikan.
     *
     * @param array<string>|string $roles Peran atau daftar peran yang akan diperiksa.
     * @return bool True jika pengguna memiliki salah satu peran, false jika tidak.
     */
    public function hasRole($roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        return $this->role === $roles;
    }

    /**
     * Memeriksa apakah pengguna memiliki salah satu dari beberapa peran yang diberikan.
     * Alias untuk hasRole dengan parameter array.
     *
     * @param array<string> $roles Daftar peran yang akan diperiksa.
     * @return bool True jika pengguna memiliki salah satu peran, false jika tidak.
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Mutator untuk atribut 'role'.
     * Melakukan validasi otomatis saat mengatur peran pengguna.
     *
     * @param string $value Nilai peran yang akan diatur.
     * @return void
     * @throws \InvalidArgumentException Jika peran tidak valid.
     */
    public function setRoleAttribute(string $value): void
    {
        if (!in_array($value, [self::ROLE_ADMIN, self::ROLE_OPERATOR, self::ROLE_GURU])) {
            throw new \InvalidArgumentException("Role tidak valid: {$value}. Role yang valid adalah: " . implode(', ', self::getRoles()));
        }
        $this->attributes['role'] = $value;
    }

    /**
     * Mendefinisikan relasi HasMany ke model Ruangan.
     * Seorang pengguna (operator) dapat mengelola banyak ruangan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ruanganYangDiKelola(): HasMany
    {
        return $this->hasMany(Ruangan::class, 'id_operator', 'id');
    }

    /**
     * Mendefinisikan relasi HasMany ke model BarangQrCode.
     * Seorang pengguna dapat memegang banyak unit barang secara personal.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function barangQrCodesYangDipegang(): HasMany
    {
        return $this->hasMany(BarangQrCode::class, 'id_pemegang_personal');
    }

    /**
     * Mendefinisikan relasi HasMany ke model Peminjaman.
     * Seorang pengguna (guru) dapat mengajukan banyak peminjaman.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function peminjamanYangDiajukan(): HasMany
    {
        return $this->hasMany(Peminjaman::class, 'id_guru');
    }

    /**
     * Mendefinisikan relasi HasMany ke model Peminjaman.
     * Seorang pengguna (admin/operator) dapat menyetujui banyak peminjaman.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function peminjamanYangDisetujui(): HasMany
    {
        return $this->hasMany(Peminjaman::class, 'disetujui_oleh');
    }

    /**
     * Mendefinisikan relasi HasMany ke model Peminjaman.
     * Seorang pengguna (admin/operator) dapat menolak banyak peminjaman.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function peminjamanYangDitolak(): HasMany
    {
        return $this->hasMany(Peminjaman::class, 'ditolak_oleh');
    }

    /**
     * Mendefinisikan relasi HasMany ke model MutasiBarang.
     * Seorang pengguna (admin) dapat melakukan banyak mutasi barang.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mutasiYangDilakukan(): HasMany
    {
        return $this->hasMany(MutasiBarang::class, 'id_user_admin');
    }

    /**
     * Mendefinisikan relasi HasMany ke model Pemeliharaan.
     * Seorang pengguna dapat mengajukan banyak pemeliharaan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pemeliharaanDiajukan(): HasMany
    {
        return $this->hasMany(Pemeliharaan::class, 'id_user_pengaju');
    }

    /**
     * Mendefinisikan relasi HasMany ke model Pemeliharaan.
     * Seorang pengguna dapat menyetujui banyak pemeliharaan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pemeliharaanDisetujui(): HasMany
    {
        return $this->hasMany(Pemeliharaan::class, 'id_user_penyetuju');
    }

    /**
     * Mendefinisikan relasi HasMany ke model Pemeliharaan.
     * Seorang pengguna (operator) dapat mengerjakan banyak pemeliharaan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pemeliharaanDikerjakan(): HasMany
    {
        return $this->hasMany(Pemeliharaan::class, 'id_operator_pengerjaan');
    }

    /**
     * Mendefinisikan relasi HasMany ke model BarangStatus.
     * Seorang pengguna (operator) dapat mencatat banyak perubahan status barang.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function barangStatusDicatat(): HasMany
    {
        return $this->hasMany(BarangStatus::class, 'id_user_pencatat');
    }

    /**
     * Mendefinisikan relasi HasMany ke model StokOpname.
     * Seorang pengguna (operator) dapat melakukan banyak stok opname.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stokOpnameYangDilakukan(): HasMany
    {
        return $this->hasMany(StokOpname::class, 'id_operator');
    }

    /**
     * Mendefinisikan relasi HasMany ke model ArsipBarang.
     * Seorang pengguna dapat mengajukan banyak pengarsipan barang.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function arsipBarangDiajukan(): HasMany
    {
        return $this->hasMany(ArsipBarang::class, 'id_user_pengaju');
    }

    /**
     * Mendefinisikan relasi HasMany ke model ArsipBarang.
     * Seorang pengguna dapat menyetujui banyak pengarsipan barang.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function arsipBarangDisetujui(): HasMany
    {
        return $this->hasMany(ArsipBarang::class, 'id_user_penyetuju');
    }

    /**
     * Mendefinisikan relasi HasMany ke model ArsipBarang.
     * Seorang pengguna dapat memulihkan banyak barang dari arsip.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function arsipBarangDipulihkan(): HasMany
    {
        return $this->hasMany(ArsipBarang::class, 'dipulihkan_oleh');
    }

    /**
     * Mendefinisikan relasi HasMany ke model LogAktivitas.
     * Seorang pengguna dapat memiliki banyak log aktivitas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function logAktivitas(): HasMany
    {
        return $this->hasMany(LogAktivitas::class, 'id_user');
    }




    /**
     * Scope query untuk memfilter pengguna dengan peran Admin.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Instance query builder.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAdmin($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    /**
     * Scope query untuk memfilter pengguna dengan peran Operator.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Instance query builder.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOperator($query)
    {
        return $query->where('role', self::ROLE_OPERATOR);
    }

    /**
     * Scope query untuk memfilter pengguna dengan peran Guru.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query Instance query builder.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGuru($query)
    {
        return $query->where('role', self::ROLE_GURU);
    }
}
