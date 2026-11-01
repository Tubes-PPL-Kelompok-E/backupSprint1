<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KomponenDarah extends Model
{
    use HasFactory;

    protected $table = 'komponen_darah';

    protected $fillable = [
        'nama_komponen',
        'kode_komponen',
        'masa_simpan_hari',
        'suhu_simpan_min',
        'suhu_simpan_max',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            'masa_simpan_hari' => 'integer',
            'suhu_simpan_min' => 'float',
            'suhu_simpan_max' => 'float',
        ];
    }

    public function kantongDarah(): HasMany
    {
        return $this->hasMany(KantongDarah::class, 'komponen_darah_id');
    }

    public function detailPermintaan(): HasMany
    {
        return $this->hasMany(DetailPermintaan::class, 'komponen_darah_id');
    }
}

