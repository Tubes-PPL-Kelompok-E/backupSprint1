<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UjiSaring extends Model
{
    use HasFactory;

    protected $table = 'uji_saring';

    protected $fillable = [
        'kantong_darah_id',
        'petugas_id',
        'tanggal_uji',
        'hiv',
        'hbsag',
        'hcv',
        'sifilis',
        'kesimpulan',
        'nomor_berita_acara',
        'berkas_berita_acara',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_uji' => 'date',
        ];
    }

    public function kantongDarah(): BelongsTo
    {
        return $this->belongsTo(KantongDarah::class, 'kantong_darah_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    /**
     * Cek apakah seluruh 4 parameter screening IMLTD negatif (Non-Reaktif)
     */
    public function isAman(): bool
    {
        return $this->hiv === 'Non-Reaktif' &&
            $this->hbsag === 'Non-Reaktif' &&
            $this->hcv === 'Non-Reaktif' &&
            $this->sifilis === 'Non-Reaktif';
    }
}

