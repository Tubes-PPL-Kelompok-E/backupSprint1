<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanggilanPendonor extends Model
{
    use HasFactory;

    protected $table = 'panggilan_pendonor';

    protected $fillable = [
        'pendonor_id',
        'permintaan_darah_id',
        'tanggal_panggilan',
        'metode',
        'status_respon',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_panggilan' => 'datetime',
        ];
    }

    public function pendonor(): BelongsTo
    {
        return $this->belongsTo(Pendonor::class, 'pendonor_id');
    }

    public function permintaanDarah(): BelongsTo
    {
        return $this->belongsTo(PermintaanDarah::class, 'permintaan_darah_id');
    }
}

