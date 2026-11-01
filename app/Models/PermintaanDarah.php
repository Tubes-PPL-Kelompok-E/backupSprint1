<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
<<<<<<< HEAD
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PermintaanDarah extends Model
{
    protected $table = 'permintaan_darah';

    protected $fillable = [
        'kode', 'instansi_id', 'nama_pasien', 'ruang', 'no_rm',
        'golongan', 'rhesus', 'komponen', 'jumlah', 'prioritas',
        'status', 'diajukan_pada', 'alasan_penolakan',
        'kurir_nama', 'kurir_kendaraan', 'suhu_cold_box', 'posisi_terakhir',
        'divalidasi_oleh', 'divalidasi_pada', 'selesai_pada', 'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'diajukan_pada'   => 'datetime',
            'divalidasi_pada' => 'datetime',
            'selesai_pada'    => 'datetime',
            'suhu_cold_box'   => 'decimal:1',
        ];
    }

    public const STATUS = [
        'menunggu'         => 'Menunggu Persetujuan',
        'dalam_perjalanan' => 'Dalam Perjalanan',
        'diterima'         => 'Diterima RS',
        'ditolak'          => 'Ditolak',
    ];

    public const PRIORITAS = ['cito' => 'CITO / DARURAT', 'rutin' => 'RUTIN / TERENCANA'];

    /** Kode otomatis: REQ-{tahun}-{nomor}, contoh REQ-2026-1001 */
    public static function generateKode(): string
    {
        $tahun = now()->year;
        $terakhir = static::where('kode', 'like', "REQ-{$tahun}-%")->orderByDesc('kode')->value('kode');
        $nomor = $terakhir ? ((int) substr($terakhir, -4)) + 1 : 1001;

        return sprintf('REQ-%d-%04d', $tahun, $nomor);
    }

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class)->withTrashed();
    }

    /** Kantong hasil alokasi FEFO */
    public function kantong(): BelongsToMany
    {
        return $this->belongsToMany(KantongDarah::class, 'permintaan_kantong', 'permintaan_id', 'kantong_id')
            ->withTimestamps();
    }

    public function golonganLengkap(): string
    {
        return $this->golongan . ' (' . ($this->rhesus === 'pos' ? '+' : '-') . ')';
    }

    public function kebutuhanTeks(): string
    {
        return $this->jumlah . ' Kantong ' . strtoupper($this->komponen);
    }

    public function scopeMenunggu(Builder $query): Builder
    {
        return $query->where('status', 'menunggu');
    }
}
=======
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

>>>>>>> 97273f945f0e085eb6ccceff1bb4058df17dfc78
