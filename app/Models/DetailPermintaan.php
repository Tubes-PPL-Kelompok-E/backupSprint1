<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailPermintaan extends Model
{
    use HasFactory;

    protected $table = 'detail_permintaan';

    protected $fillable = [
        'permintaan_darah_id',
        'komponen_darah_id',
        'golongan_darah',
        'rhesus',
        'jumlah_diminta',
        'jumlah_dialokasikan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_diminta' => 'integer',
            'jumlah_dialokasikan' => 'integer',
        ];
    }

    public function permintaanDarah(): BelongsTo
    {
        return $this->belongsTo(PermintaanDarah::class, 'permintaan_darah_id');
    }

    public function komponenDarah(): BelongsTo
    {
        return $this->belongsTo(KomponenDarah::class, 'komponen_darah_id');
    }

    public function alokasiKantong(): HasMany
    {
        return $this->hasMany(AlokasiKantong::class, 'detail_permintaan_id');
    }

    /**
     * Cek apakah alokasi sudah terpenuhi
     */
    public function isTerpenuhi(): bool
    {
        return $this->jumlah_dialokasikan >= $this->jumlah_diminta;
    }
}

