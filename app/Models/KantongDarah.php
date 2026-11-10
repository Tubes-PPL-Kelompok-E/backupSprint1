<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class KantongDarah extends Model
{
    protected $table = 'kantong_darah';

    protected $fillable = [
        'kode_batch', 'tanggal_donor', 'volume_ml', 'komponen', 'golongan', 'rhesus',
        'tanggal_kedaluwarsa', 'status', 'lokasi_simpan',
        'hasil_uji', 'nomor_berita_acara', 'tanggal_uji', 'berkas_berita_acara', 'catatan_uji',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_donor'       => 'date',
            'tanggal_kedaluwarsa' => 'date',
            'tanggal_uji'         => 'date',
        ];
    }

    /** Masa simpan (hari) per komponen, dihitung dari tanggal donor. */
    public const MASA_SIMPAN = ['prc' => 35, 'wb' => 35, 'tc' => 5, 'ffp' => 365];

    public const KOMPONEN = [
        'prc' => 'PRC (Packed Red Cells)',
        'wb'  => 'WB (Whole Blood)',
        'tc'  => 'TC (Thrombocyte Concentrate)',
        'ffp' => 'FFP (Fresh Frozen Plasma)',
    ];

    public const STATUS = [
        'karantina'   => 'Karantina',
        'ready'       => 'Ready',
        'ditahan'     => 'Ditahan',
        'kedaluwarsa' => 'Kedaluwarsa',
        'dimusnahkan' => 'Dimusnahkan',
    ];

    /** Kode otomatis: BATCH-YYYYMMDD-{GOL}{NN}, contoh BATCH-20261006-A01 */
    public static function generateKode(string $tanggal, string $golongan): string
    {
        $prefix = 'BATCH-' . Carbon::parse($tanggal)->format('Ymd') . '-' . $golongan;
        $nomor  = static::where('kode_batch', 'like', $prefix . '__')->count() + 1;

        do {
            $kode = sprintf('%s%02d', $prefix, $nomor++);
        } while (static::where('kode_batch', $kode)->exists());

        return $kode;
    }

    public function golonganLengkap(): string
    {
        return $this->golongan . ' (' . ($this->rhesus === 'pos' ? '+' : '-') . ')';
    }

    /** Sisa hari sampai kedaluwarsa; negatif jika sudah lewat. */
    public function sisaHari(): int
    {
        return (int) Carbon::today()->diffInDays($this->tanggal_kedaluwarsa, false);
    }

    public function scopeKarantina(Builder $query): Builder
    {
        return $query->where('status', 'karantina');
    }

    /** Stok siap pakai: status Ready dan belum melewati tanggal kedaluwarsa. */
    public function scopeSiapPakai(Builder $query): Builder
    {
        return $query->where('status', 'ready')->whereDate('tanggal_kedaluwarsa', '>=', today());
    }

    /** Ringkasan nilai untuk log transaksi. */
    public function untukLog(): array
    {
        return [
            'kode_batch' => $this->kode_batch,
            'golongan'   => $this->golongan,
            'rhesus'     => $this->rhesus,
            'komponen'   => $this->komponen,
        ];
    }
}
