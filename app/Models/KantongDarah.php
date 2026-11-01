<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KantongDarah extends Model
{
    use HasFactory;

    protected $table = 'kantong_darah';

    protected $fillable = [
        'donasi_id',
        'komponen_darah_id',
        'kode_batch',
        'golongan_darah',
        'rhesus',
        'volume',
        'tanggal_donor',
        'tanggal_kedaluwarsa',
        'lokasi_simpan',
        'status_kantong',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_donor' => 'date',
            'tanggal_kedaluwarsa' => 'date',
            'volume' => 'integer',
        ];
    }

    public function donasi(): BelongsTo
    {
        return $this->belongsTo(Donasi::class, 'donasi_id');
    }

    public function komponenDarah(): BelongsTo
    {
        return $this->belongsTo(KomponenDarah::class, 'komponen_darah_id');
    }

    public function ujiSaring(): HasOne
    {
        return $this->hasOne(UjiSaring::class, 'kantong_darah_id');
    }

    public function alokasiKantong(): HasMany
    {
        return $this->hasMany(AlokasiKantong::class, 'kantong_darah_id');
    }

    /**
     * Scope FEFO: First Expired, First Out
     * Mengurutkan kantong darah berdasarkan tanggal kedaluwarsa terdekat
     */
    public function scopeFefo(Builder $query): Builder
    {
        return $query->orderBy('tanggal_kedaluwarsa', 'asc');
    }

    /**
     * Scope kantong berstatus Ready (lolos lab & siap didistribusikan)
     */
    public function scopeReady(Builder $query): Builder
    {
        return $query->where('status_kantong', 'Ready');
    }

    /**
     * Scope kantong dalam masa Karantina
     */
    public function scopeKarantina(Builder $query): Builder
    {
        return $query->where('status_kantong', 'Karantina');
    }

    /**
     * Scope FEFO Alert: Kantong Ready yang mendekati kedaluwarsa (<= N hari)
     */
    public function scopeMendekatiKedaluwarsa(Builder $query, int $hari = 7): Builder
    {
        $batasMulai = Carbon::now()->startOfDay();
        $batasAkhir = Carbon::now()->addDays($hari)->endOfDay();

        return $query->where('status_kantong', 'Ready')
            ->whereBetween('tanggal_kedaluwarsa', [$batasMulai, $batasAkhir])
            ->orderBy('tanggal_kedaluwarsa', 'asc');
    }

    /**
     * Sisa hari sampai kedaluwarsa
     */
    public function getSisaHariKedaluwarsaAttribute(): int
    {
        return (int) Carbon::now()->startOfDay()->diffInDays($this->tanggal_kedaluwarsa, false);
    }

    /**
     * Cek apakah sudah lewat kedaluwarsa
     */
    public function isKedaluwarsa(): bool
    {
        return Carbon::now()->startOfDay()->greaterThan($this->tanggal_kedaluwarsa);
    }

    /**
     * Format golongan darah lengkap misal "B+"
     */
    public function getGolonganDarahLengkapAttribute(): string
    {
        return "{$this->golongan_darah}{$this->rhesus}";
    }
}

