<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;


class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    // Nama tabel
    protected $table = 'users';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    // ENUM Role Constants
    public const ROLE_ADMIN = 'Admin';
    public const ROLE_OPERATOR = 'Operator';
    public const ROLE_GURU = 'Guru';

    /**
     * Mengembalikan daftar role yang valid.
     */
    public static function getRoles()
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_OPERATOR,
            self::ROLE_GURU,
        ];
    }

    // Kolom yang dapat diisi (fillable)
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    // Kolom yang harus disembunyikan
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Casting tipe data
    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => 'string', // ENUM disimpan sebagai string
    ];

    /**
     * Check if user has one of the given roles.
     *
     * @param array|string $roles
     * @return bool
     */
    public function hasRole($roles)
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }

        return $this->role === $roles;
    }

    /**
     * Validasi otomatis saat set role.
     */
    public function setRoleAttribute($value)
    {
        if (!in_array($value, self::getRoles())) {
            throw new \InvalidArgumentException("Role tidak valid.");
        }
        $this->attributes['role'] = $value;
    }

    /**
     * Relasi ke tabel Ruangan (Satu User bisa memiliki banyak Ruangan).
     */
    public function ruangan()
    {
        return $this->hasMany(Ruangan::class, 'id_operator');
    }

    /**
     * Scope Query untuk memfilter berdasarkan role.
     */
    public function scopeAdmin($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    public function scopeOperator($query)
    {
        return $query->where('role', self::ROLE_OPERATOR);
    }

    public function scopeGuru($query)
    {
        return $query->where('role', self::ROLE_GURU);
    }
}
