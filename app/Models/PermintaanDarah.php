<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PermintaanDarah extends Model
{
    use HasFactory;

    protected $table = 'permintaan_darah';

    protected $fillable = [
        'rumah_sakit_id',
        'user_id',
        'nomor_permintaan',
        'nama_pasien',
        'nomor_rekam_medis',
        'urgensi',
        'status_permintaan',
        'alasan_penolakan',
        'catatan_klinis',
    ];

    public function rumahSakit(): BelongsTo
    {
        return $this->belongsTo(RumahSakit::class, 'rumah_sakit_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detailPermintaan(): HasMany
    {
        return $this->hasMany(DetailPermintaan::class, 'permintaan_darah_id');
    }

    public function pengiriman(): HasOne
    {
        return $this->hasOne(Pengiriman::class, 'permintaan_darah_id');
    }

    public function panggilanPendonor(): HasMany
    {
        return $this->hasMany(PanggilanPendonor::class, 'permintaan_darah_id');
    }

    /**
     * Scope filter permintaan berstatus Cito (darurat)
     */
    public function scopeCito(Builder $query): Builder
    {
        return $query->where('urgensi', 'Cito');
    }

    /**
     * Scope filter permintaan menunggu verifikasi petugas
     */
    public function scopeMenungguPersetujuan(Builder $query): Builder
    {
        return $query->where('status_permintaan', 'Menunggu Persetujuan');
    }
}

