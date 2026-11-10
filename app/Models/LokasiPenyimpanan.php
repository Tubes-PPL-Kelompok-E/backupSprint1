<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LokasiPenyimpanan extends Model
{
    protected $table = 'lokasi_penyimpanan';

    protected $fillable = ['nama', 'jenis', 'kapasitas', 'suhu_min', 'suhu_maks', 'aktif', 'keterangan'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean', 'suhu_min' => 'decimal:1', 'suhu_maks' => 'decimal:1'];
    }

    public const JENIS = ['cold_room' => 'Cold Room', 'freezer' => 'Freezer Plasma', 'agitator' => 'Agitator Trombosit', 'karantina' => 'Area Karantina'];

    /** Status kantong yang masih menempati ruang di lokasi. */
    public const STATUS_MENEMPATI = ['karantina', 'ready', 'ditahan'];

    public function terisi(): int
    {
        return KantongDarah::where('lokasi_simpan', $this->nama)->whereIn('status', self::STATUS_MENEMPATI)->count();
    }

    public function sisa(): int
    {
        return max(0, $this->kapasitas - $this->terisi());
    }

    public function penuh(): bool
    {
        return $this->terisi() >= $this->kapasitas;
    }
}
