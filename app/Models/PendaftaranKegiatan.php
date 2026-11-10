<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendaftaranKegiatan extends Model
{
    protected $table = 'pendaftaran_kegiatan';

    protected $fillable = ['pendonor_id', 'kegiatan_id', 'tanggal_daftar', 'status_kehadiran'];

    protected function casts(): array
    {
        return ['tanggal_daftar' => 'datetime'];
    }

    public const KEHADIRAN = ['terdaftar' => 'Terdaftar', 'hadir' => 'Hadir', 'tidak_hadir' => 'Tidak Hadir'];

    public function pendonor(): BelongsTo
    {
        return $this->belongsTo(Pendonor::class)->withTrashed();
    }

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(KegiatanDonor::class, 'kegiatan_id');
    }
}
