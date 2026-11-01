<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pengiriman extends Model
{
    use HasFactory;

    protected $table = 'pengiriman';

    protected $fillable = [
        'permintaan_darah_id',
        'nomor_surat_jalan',
        'nama_kurir',
        'plat_kendaraan',
        'status_pengiriman',
        'waktu_berangkat',
        'estimasi_tiba',
        'suhu_kotak_pendingin',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'waktu_berangkat' => 'datetime',
            'estimasi_tiba' => 'datetime',
            'suhu_kotak_pendingin' => 'float',
        ];
    }

    public function permintaanDarah(): BelongsTo
    {
        return $this->belongsTo(PermintaanDarah::class, 'permintaan_darah_id');
    }

    public function penerimaan(): HasOne
    {
        return $this->hasOne(Penerimaan::class, 'pengiriman_id');
    }
}

