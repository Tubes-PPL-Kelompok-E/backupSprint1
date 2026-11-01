<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pendonor extends Model
{
    use HasFactory;

    protected $table = 'pendonor';

    protected $fillable = [
        'user_id',
        'kode_pendonor',
        'nik',
        'nama',
        'tanggal_lahir',
        'jenis_kelamin',
        'golongan_darah',
        'rhesus',
        'telepon',
        'kota',
        'alamat',
        'tanggal_terakhir_donor',
        'status_kelayakan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_terakhir_donor' => 'date',
        ];
    }

    /**
     * Akun user pendonor (opsional jika pendonor mendaftar web)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Riwayat pendaftaran kegiatan mobile unit
     */
    public function pendaftaranKegiatan(): HasMany
    {
        return $this->hasMany(PendaftaranKegiatan::class, 'pendonor_id');
    }

    /**
     * Riwayat donasi darah
     */
    public function donasi(): HasMany
    {
        return $this->hasMany(Donasi::class, 'pendonor_id');
    }

    /**
     * Riwayat panggilan donor darurat / matching alert
     */
    public function panggilan(): HasMany
    {
        return $this->hasMany(PanggilanPendonor::class, 'pendonor_id');
    }

    /**
     * Scope pendonor layak donor
     */
    public function scopeLayak(Builder $query): Builder
    {
        return $query->where('status_kelayakan', 'Layak');
    }

    /**
     * Scope pencarian berdasarkan golongan darah & rhesus
     */
    public function scopeGolonganDarah(Builder $query, string $gol, string $rhesus = '+'): Builder
    {
        return $query->where('golongan_darah', $gol)->where('rhesus', $rhesus);
    }

    /**
     * Format golongan darah lengkap misal "O+"
     */
    public function getGolonganDarahLengkapAttribute(): string
    {
        return "{$this->golongan_darah}{$this->rhesus}";
    }
}

