<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KegiatanDonor extends Model
{
    use HasFactory;

    protected $table = 'kegiatan_donor';

    protected $fillable = [
        'kode_kegiatan',
        'judul',
        'deskripsi',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'kuota',
        'nama_tempat',
        'alamat',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'kuota' => 'integer',
        ];
    }

    /**
     * Pendaftaran peserta pada kegiatan ini
     */
    public function pendaftaran(): HasMany
    {
        return $this->hasMany(PendaftaranKegiatan::class, 'kegiatan_id');
    }

    /**
     * Donasi yang diperoleh dari kegiatan ini
     */
    public function donasi(): HasMany
    {
        return $this->hasMany(Donasi::class, 'kegiatan_id');
    }

    /**
     * Scope kegiatan terbuka untuk pendaftaran
     */
    public function scopeTerbuka(Builder $query): Builder
    {
        return $query->where('status', 'Terbuka');
    }

    /**
     * Scope event mendatang
     */
    public function scopeMendatang(Builder $query): Builder
    {
        return $query->where('tanggal', '>=', now()->toDateString());
    }

    /**
     * Sisa kuota peserta
     */
    public function getSisaKuotaAttribute(): int
    {
        $terdaftar = $this->pendaftaran()->count();
        return max(0, $this->kuota - $terdaftar);
    }
}

