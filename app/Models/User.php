<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'instansi',
        'institution_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Nama route dashboard sesuai peran
     */
    public function homeRoute(): string
    {
        return match (true) {
            $this->hasAnyRole(['admin_pmi', 'Petugas Bank Darah'])  => 'admin.dashboard',
            $this->hasAnyRole(['petugas_rs', 'Admin Rumah Sakit']) => 'petugas.dashboard',
            default                                                => 'pendonor.dashboard',
        };
    }

    /**
     * Relasi ke Rumah Sakit (untuk Admin RS)
     */
    public function rumahSakit(): BelongsTo
    {
        return $this->belongsTo(RumahSakit::class, 'institution_id');
    }

    /**
     * Relasi ke Profil Pendonor (jika role pendonor)
     */
    public function pendonor(): HasOne
    {
        return $this->hasOne(Pendonor::class, 'user_id');
    }

    /**
     * Relasi uji saring yang diinput petugas
     */
    public function ujiSaring(): HasMany
    {
        return $this->hasMany(UjiSaring::class, 'petugas_id');
    }

    /**
     * Relasi permintaan darah yang diajukan admin RS
     */
    public function permintaanDarah(): HasMany
    {
        return $this->hasMany(PermintaanDarah::class, 'user_id');
    }

    /**
     * Relasi notifikasi user
     */
    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'user_id');
    }

    /**
     * Relasi audit logs yang dipicu user
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }
}
