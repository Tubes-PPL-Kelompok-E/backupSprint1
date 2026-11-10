<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PemeriksaanDonor extends Model
{
    protected $table = 'pemeriksaan_donor';

    protected $fillable = [
        'pendonor_id', 'diperiksa_oleh', 'tanggal', 'hemoglobin', 'sistolik', 'diastolik',
        'berat_kg', 'suhu_tubuh', 'nadi', 'hasil', 'alasan', 'catatan',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'hemoglobin' => 'decimal:1', 'berat_kg' => 'decimal:1', 'suhu_tubuh' => 'decimal:1'];
    }

    public function pendonor(): BelongsTo
    {
        return $this->belongsTo(Pendonor::class);
    }

    public function pemeriksa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diperiksa_oleh');
    }

    public function layak(): bool
    {
        return $this->hasil === 'layak';
    }

    /** @return string[] */
    public function daftarAlasan(): array
    {
        return array_values(array_filter(explode("\n", (string) $this->alasan)));
    }
}
