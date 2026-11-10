<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasRoles, SoftDeletes;

    protected $fillable = ['name', 'email', 'instansi', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // otomatis di-hash (bcrypt)
        ];
    }

    /** Nama route dashboard sesuai peran */
    public function homeRoute(): string
    {
        return match (true) {
            $this->hasRole('admin_pmi')  => 'admin.dashboard',
            $this->hasRole('petugas_rs') => 'petugas.dashboard',
            default                      => 'pendonor.dashboard',
        };
    }

    /** Data pendonor yang terhubung dengan akun ini (null bila belum dihubungkan oleh UTD). */
    public function pendonor(): HasOne
    {
        return $this->hasOne(Pendonor::class);
    }

    /**
     * Rumah sakit tempat Admin RS bertugas, dicocokkan lewat nama pada kolom users.instansi
     * dengan data Instansi bertipe rumah_sakit (dibuat di menu Data Instansi).
     */
    public function instansiRs(): ?Instansi
    {
        if (! $this->instansi) {
            return null;
        }

        return Instansi::where('tipe', 'rumah_sakit')->where('nama', $this->instansi)->first();
    }
}
