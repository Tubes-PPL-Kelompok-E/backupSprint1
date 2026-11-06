<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penerimaan extends Model
{
    use HasFactory;

    protected $table = 'penerimaan';

    protected $fillable = [
        'pengiriman_id',
        'waktu_terima',
        'penerima_nama',
        'penerima_nip',
        'suhu_diterima',
        'catatan_kondisi',
    ];

    protected function casts(): array
    {
        return [
            'waktu_terima' => 'datetime',
            'suhu_diterima' => 'float',
        ];
    }

    public function pengiriman(): BelongsTo
    {
        return $this->belongsTo(Pengiriman::class, 'pengiriman_id');
    }
}

