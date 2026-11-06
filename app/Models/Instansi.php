<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instansi extends Model
{
    use SoftDeletes;

    protected $table = 'instansi';

    protected $fillable = ['kode', 'nama', 'tipe', 'alamat', 'kota', 'telepon', 'email'];

    public const TIPE = ['pmi' => 'PMI', 'rumah_sakit' => 'Rumah Sakit'];

    /** Buat kode otomatis: PMI-001, RS-001, dst. Dipakai jika kode dikosongkan. */
    public static function generateKode(string $tipe): string
    {
        $prefix = $tipe === 'pmi' ? 'PMI' : 'RS';
        $nomor  = static::withTrashed()->where('tipe', $tipe)->count() + 1;

        do {
            $kode = sprintf('%s-%03d', $prefix, $nomor++);
        } while (static::withTrashed()->where('kode', $kode)->exists());

        return $kode;
    }

    public function scopeCari(Builder $query, ?string $kata): Builder
    {
        return $query->when($kata, fn ($q) => $q->where(fn ($w) => $w
            ->where('kode', 'like', "%{$kata}%")
            ->orWhere('nama', 'like', "%{$kata}%")
            ->orWhere('kota', 'like', "%{$kata}%")));
    }
}
