<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Donasi extends Model
{
    use HasFactory;

    protected $table = 'donasi';

    protected $fillable = [
        'kode_donasi',
        'pendonor_id',
        'kegiatan_id',
        'tanggal_donasi',
        'volume_ml',
        'status_donasi',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_donasi' => 'date',
            'volume_ml' => 'integer',
        ];
    }

    public function pendonor(): BelongsTo
    {
        return $this->belongsTo(Pendonor::class, 'pendonor_id');
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(KegiatanDonor::class, 'kegiatan_id');
    }

    public function kantongDarah(): HasMany
    {
        return $this->hasMany(KantongDarah::class, 'donasi_id');
    }
}

