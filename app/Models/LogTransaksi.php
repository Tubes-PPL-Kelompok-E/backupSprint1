<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Log transaksi tambah-saja (append-only). Dibuat lewat LogTransaksi::catat().
 * Ubah/hapus ditolak di level model.
 */
class LogTransaksi extends Model
{
    protected $table = 'log_transaksi';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'nama_pengguna', 'aksi', 'entitas_tipe', 'entitas_id',
        'ringkasan', 'nilai_lama', 'nilai_baru', 'ip_address', 'created_at',
    ];

    protected function casts(): array
    {
        return ['nilai_lama' => 'array', 'nilai_baru' => 'array', 'created_at' => 'datetime'];
    }

    public const AKSI = [
        'kantong_masuk'        => 'Kantong masuk',
        'kantong_diubah'       => 'Data kantong diubah',
        'kantong_dihapus'      => 'Kantong dihapus',
        'donasi_dicatat'       => 'Donasi dicatat',
        'donasi_diubah'        => 'Donasi diubah',
        'donasi_dihapus'       => 'Donasi dihapus',
        'kegiatan_diubah'      => 'Kegiatan donor diubah',
        'pendaftaran_dibatalkan' => 'Pendaftaran kegiatan dibatalkan',
        'uji_saring'           => 'Uji saring',
        'kantong_kedaluwarsa'  => 'Kantong kedaluwarsa',
        'kantong_dimusnahkan'  => 'Kantong dimusnahkan',
        'pendaftaran_kegiatan' => 'Pendaftaran kegiatan donor',
        'batas_minimum_diubah' => 'Batas minimum stok diubah',
        'pendonor_diubah'      => 'Data pendonor diubah',
        'pemeriksaan_donor'    => 'Pemeriksaan pra-donor',
        'lokasi_diubah'        => 'Lokasi penyimpanan diubah',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Log transaksi bersifat tambah-saja dan tidak dapat diubah.'));
        static::deleting(fn () => throw new LogicException('Log transaksi bersifat tambah-saja dan tidak dapat dihapus.'));
    }

    /**
     * Catat satu transaksi.
     *
     * @param  array{lama?:array,baru?:array,waktu?:\DateTimeInterface|string,user?:?User}  $opsi
     */
    public static function catat(string $aksi, string $entitasTipe, ?int $entitasId, string $ringkasan, array $opsi = []): self
    {
        $user = array_key_exists('user', $opsi) ? $opsi['user'] : auth()->user();

        return static::create([
            'user_id'       => $user?->id,
            'nama_pengguna' => $user?->name,
            'aksi'          => $aksi,
            'entitas_tipe'  => $entitasTipe,
            'entitas_id'    => $entitasId,
            'ringkasan'     => mb_substr($ringkasan, 0, 255),
            'nilai_lama'    => $opsi['lama'] ?? null,
            'nilai_baru'    => $opsi['baru'] ?? null,
            'ip_address'    => app()->runningInConsole() ? null : request()->ip(),
            'created_at'    => $opsi['waktu'] ?? now(),
        ]);
    }

    public const ENTITAS = [
        'kantong' => 'Kantong darah', 'kegiatan' => 'Kegiatan donor', 'pendonor' => 'Pendonor',
        'lokasi' => 'Lokasi penyimpanan', 'batas_minimum' => 'Batas minimum', 'sistem' => 'Sistem',
    ];

    public function labelAksi(): string
    {
        return self::AKSI[$this->aksi] ?? $this->aksi;
    }
}
