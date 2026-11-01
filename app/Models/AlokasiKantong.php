<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlokasiKantong extends Model
{
    use HasFactory;

    protected $table = 'alokasi_kantong';

    protected $fillable = [
        'detail_permintaan_id',
        'kantong_darah_id',
        'tanggal_alokasi',
        'status_alokasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_alokasi' => 'datetime',
        ];
    }

    public function detailPermintaan(): BelongsTo
    {
        return $this->belongsTo(DetailPermintaan::class, 'detail_permintaan_id');
    }

    public function kantongDarah(): BelongsTo
    {
        return $this->belongsTo(KantongDarah::class, 'kantong_darah_id');
    }
}

