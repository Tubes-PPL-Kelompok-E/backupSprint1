<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
<<<<<<< HEAD
use Illuminate\Database\Eloquent\Model;

class KantongDarah extends Model
{
    protected $table = 'kantong_darah';

    protected $fillable = [
        'kode_batch', 'tanggal_donor', 'volume_ml', 'komponen', 'golongan', 'rhesus',
        'tanggal_kedaluwarsa', 'status', 'lokasi_simpan',
        'hasil_uji', 'nomor_berita_acara', 'tanggal_uji', 'berkas_berita_acara', 'catatan_uji',
        'dibuat_oleh',
=======
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
>>>>>>> 97273f945f0e085eb6ccceff1bb4058df17dfc78
    ];

    protected function casts(): array
    {
        return [
<<<<<<< HEAD
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
        'reserved'    => 'Reserved',
        'ditahan'     => 'Ditahan',
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
}
=======
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

>>>>>>> 97273f945f0e085eb6ccceff1bb4058df17dfc78
