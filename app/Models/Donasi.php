<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donasi extends Model
{
    protected $table = 'donasi';

    protected $fillable = ['pendonor_id', 'kegiatan_id', 'tanggal_donor', 'volume_ml', 'lokasi'];

    protected function casts(): array
    {
        return ['tanggal_donor' => 'date'];
    }

    public function pendonor(): BelongsTo
    {
        return $this->belongsTo(Pendonor::class);
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(KegiatanDonor::class, 'kegiatan_id');
    }
}
