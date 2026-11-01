<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RumahSakit extends Model
{
    use HasFactory;

    protected $table = 'rumah_sakit';

    protected $fillable = [
        'kode_rs',
        'nama_rs',
        'telepon',
        'email',
        'alamat',
    ];

    /**
     * Users (Admin RS) yang terhubung ke rumah sakit ini
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'institution_id');
    }

    /**
     * Seluruh permintaan darah dari rumah sakit ini
     */
    public function permintaanDarah(): HasMany
    {
        return $this->hasMany(PermintaanDarah::class, 'rumah_sakit_id');
    }
}

