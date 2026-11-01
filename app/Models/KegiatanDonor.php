<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KegiatanDonor extends Model
{
    protected $table = 'kegiatan_donor';
    protected $fillable = ['nama','deskripsi','tanggal','lokasi','kuota','status','dibuat_oleh'];
    protected $casts = ['tanggal' => 'date'];
}
