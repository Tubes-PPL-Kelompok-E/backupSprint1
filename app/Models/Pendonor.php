<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pendonor extends Model
{
    use SoftDeletes;

    protected $table = 'pendonor';

    protected $fillable = [
        'user_id', 'kode_donor', 'nama', 'tanggal_lahir', 'jenis_kelamin',
        'golongan', 'rhesus', 'no_hp', 'kota', 'alamat',
    ];

    protected function casts(): array
    {
        return ['tanggal_lahir' => 'date'];
    }

    /** Kode otomatis: D-000001, D-000002, ... */
    public static function generateKode(): string
    {
        $terakhir = static::withTrashed()->where('kode_donor', 'like', 'D-%')->orderByDesc('kode_donor')->value('kode_donor');
        $nomor = $terakhir ? ((int) substr($terakhir, 2)) + 1 : 1;

        return sprintf('D-%06d', $nomor);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function donasi(): HasMany
    {
        return $this->hasMany(Donasi::class)->orderByDesc('tanggal_donor')->orderByDesc('id');
    }

    public function pendaftaran(): HasMany
    {
        return $this->hasMany(PendaftaranKegiatan::class);
    }

    public function pemeriksaan(): HasMany
    {
        return $this->hasMany(PemeriksaanDonor::class)->orderByDesc('tanggal')->orderByDesc('id');
    }

    public function umur(?Carbon $pada = null): int
    {
        return (int) $this->tanggal_lahir->diffInYears($pada ?? today());
    }

    public function golonganLengkap(): string
    {
        return $this->golongan . ' (' . ($this->rhesus === 'pos' ? '+' : '-') . ')';
    }

    public function tanggalDonorTerakhir(): ?Carbon
    {
        $tgl = $this->donasi()->reorder()->max('tanggal_donor');

        return $tgl ? Carbon::parse($tgl) : null;
    }

    /** Tanggal paling awal pendonor boleh mendonor lagi; null = boleh kapan saja (belum pernah donor). */
    public function tanggalLayak(): ?Carbon
    {
        return $this->tanggalDonorTerakhir()?->addDays(config('bloodconnect.interval_donor_hari'));
    }

    /** Layak pada tanggal tertentu (default hari ini). */
    public function layakPada(?Carbon $tanggal = null): bool
    {
        $layak = $this->tanggalLayak();

        return $layak === null || ($tanggal ?? today())->startOfDay()->greaterThanOrEqualTo($layak->startOfDay());
    }

    public function scopeCocok(Builder $query, string $golongan, string $rhesus): Builder
    {
        return $query->where('golongan', $golongan)->where('rhesus', $rhesus);
    }

    public function scopeCari(Builder $query, ?string $kata): Builder
    {
        return $query->when($kata, fn ($q) => $q->where(fn ($w) => $w
            ->whereLike('nama', "%{$kata}%")
            ->orWhereLike('kode_donor', "%{$kata}%")
            ->orWhereLike('kota', "%{$kata}%")));
    }
}
