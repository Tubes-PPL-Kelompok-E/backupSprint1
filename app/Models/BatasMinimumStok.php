<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatasMinimumStok extends Model
{
    protected $table = 'batas_minimum_stok';

    protected $fillable = ['golongan', 'rhesus', 'komponen', 'minimum', 'diubah_oleh'];

    public const GOLONGAN = ['A', 'B', 'AB', 'O'];
    public const RHESUS = ['pos' => '+', 'neg' => '-'];
    public const KOMPONEN = ['prc', 'wb', 'tc', 'ffp'];

    /**
     * Peta batas minimum untuk semua 32 kombinasi: ["A|pos|prc" => 30, ...].
     * Nilai di database menimpa nilai bawaan dari config.
     */
    public static function peta(): array
    {
        $default = config('bloodconnect.batas_minimum_default');
        $tersimpan = static::all()->mapWithKeys(fn ($b) => ["{$b->golongan}|{$b->rhesus}|{$b->komponen}" => $b->minimum]);

        $peta = [];
        foreach (self::GOLONGAN as $g) {
            foreach (array_keys(self::RHESUS) as $r) {
                foreach (self::KOMPONEN as $k) {
                    $peta["$g|$r|$k"] = (int) ($tersimpan["$g|$r|$k"] ?? $default[$k] ?? 0);
                }
            }
        }

        return $peta;
    }
}
