<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KegiatanDonor extends Model
{
    protected $table = 'kegiatan_donor';

    protected $fillable = [
        'nama', 'deskripsi', 'tanggal', 'jam_mulai', 'jam_selesai',
        'lokasi', 'kuota', 'status', 'dibuat_oleh',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    // Aman dipakai walau tabel pendaftaran belum ada (hasilnya 0).
    public function jumlahPendaftar(): int
    {
        return Schema::hasTable('pendaftaran_kegiatan')
            ? DB::table('pendaftaran_kegiatan')->where('kegiatan_id', $this->id)->count()
            : 0;
    }

    public function sisaKuota(): int
    {
        return max(0, $this->kuota - $this->jumlahPendaftar());
    }

    public function punyaPendaftar(): bool
    {
        return $this->jumlahPendaftar() > 0;
    }
}