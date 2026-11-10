<?php

namespace App\Http\Controllers\Pendonor;

use App\Models\Pendonor;
use Illuminate\Http\Request;

/** Bantuan bersama portal pendonor: ambil data pendonor milik akun yang login. */
trait Concerns
{
    protected function pendonorSaya(Request $request): ?Pendonor
    {
        return $request->user()->pendonor;
    }

    /** Tampilan standar bila akun belum terhubung dengan data pendonor. */
    protected function belumTerhubung()
    {
        return view('pendonor.belum-terhubung', ['title' => 'Akun Belum Terhubung']);
    }
}
