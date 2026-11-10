<?php

namespace App\Http\Controllers\Pendonor;

use App\Http\Controllers\Controller;
use App\Models\KegiatanDonor;
use Illuminate\Http\Request;

/** Dashboard pendonor: status kelayakan, kegiatan terdekat, riwayat. */
class DashboardController extends Controller
{
    use Concerns;

    public function index(Request $request)
    {
        $pendonor = $this->pendonorSaya($request);
        if (! $pendonor) {
            return $this->belumTerhubung();
        }

        return view('pendonor.dashboard', [
            'title'      => 'Dashboard Pendonor',
            'pendonor'   => $pendonor,
            'terakhir'   => $pendonor->tanggalDonorTerakhir(),
            'layakDari'  => $pendonor->tanggalLayak(),
            'layak'      => $pendonor->layakPada(),
            'totalDonor' => $pendonor->donasi()->count(),
            'kegiatan'   => KegiatanDonor::terbuka()->withCount('pendaftaran')->orderBy('tanggal')->limit(3)->get(),
            'terdaftar'  => $pendonor->pendaftaran()->pluck('kegiatan_id')->all(),
            'riwayat'    => $pendonor->donasi()->with('kegiatan')->limit(3)->get(),
        ]);
    }

    public function riwayat(Request $request)
    {
        $pendonor = $this->pendonorSaya($request);
        if (! $pendonor) {
            return $this->belumTerhubung();
        }

        return view('pendonor.riwayat', [
            'title'     => 'Riwayat Donor',
            'pendonor'  => $pendonor,
            'riwayat'   => $pendonor->donasi()->with('kegiatan')->paginate(10),
            'layakDari' => $pendonor->tanggalLayak(),
            'layak'     => $pendonor->layakPada(),
        ]);
    }
}
