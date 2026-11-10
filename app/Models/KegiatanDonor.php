<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KegiatanDonor extends Model
{
    protected $table = 'kegiatan_donor';

    protected $fillable = [
        'judul', 'tanggal', 'jam_mulai', 'jam_selesai', 'kuota',
        'nama_tempat', 'alamat', 'status', 'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public const STATUS = ['terbuka' => 'Terbuka', 'ditutup' => 'Ditutup', 'dibatalkan' => 'Dibatalkan'];

    public function pendaftaran(): HasMany
    {
        return $this->hasMany(PendaftaranKegiatan::class, 'kegiatan_id');
    }

    public function jumlahPendaftar(): int
    {
        return $this->pendaftaran_count ?? $this->pendaftaran()->count();
    }

    public function sisaKuota(): int
    {
        return max(0, $this->kuota - $this->jumlahPendaftar());
    }

    /** Dapat didaftar: status Terbuka dan tanggalnya belum lewat. */
    public function bisaDidaftar(): bool
    {
        return $this->status === 'terbuka' && $this->tanggal->greaterThanOrEqualTo(today());
    }

    public function jam(): string
    {
        return substr($this->jam_mulai, 0, 5) . ' - ' . substr($this->jam_selesai, 0, 5) . ' WIB';
    }

    public function scopeTerbuka(Builder $query): Builder
    {
        return $query->where('status', 'terbuka')->whereDate('tanggal', '>=', today());
    }
}
