<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendaftaranKegiatan extends Model
{
    use HasFactory;

    protected $table = 'pendaftaran_kegiatan';

    protected $fillable = [
        'kegiatan_id',
        'pendonor_id',
        'tanggal_pendaftaran',
        'status_kehadiran',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pendaftaran' => 'datetime',
        ];
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(KegiatanDonor::class, 'kegiatan_id');
    }

    public function pendonor(): BelongsTo
    {
        return $this->belongsTo(Pendonor::class, 'pendonor_id');
    }
}

