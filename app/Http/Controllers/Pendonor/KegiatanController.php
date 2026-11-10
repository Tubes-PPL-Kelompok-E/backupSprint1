<?php

namespace App\Http\Controllers\Pendonor;

use App\Http\Controllers\Controller;
use App\Models\KegiatanDonor;
use App\Models\LogTransaksi;
use App\Services\PendaftaranDitolak;
use App\Services\PendaftaranKegiatanService;
use Illuminate\Http\Request;

/** pendonor melihat kegiatan terbuka dan mendaftar secara daring. */
class KegiatanController extends Controller
{
    use Concerns;

    public function index(Request $request)
    {
        $pendonor = $this->pendonorSaya($request);
        if (! $pendonor) {
            return $this->belumTerhubung();
        }

        return view('pendonor.kegiatan', [
            'title'     => 'Jadwal Donor',
            'pendonor'  => $pendonor,
            'kegiatan'  => KegiatanDonor::terbuka()->withCount('pendaftaran')->orderBy('tanggal')->orderBy('jam_mulai')->get(),
            'terdaftar' => $pendonor->pendaftaran()->pluck('kegiatan_id')->all(),
            'layakDari' => $pendonor->tanggalLayak(),
        ]);
    }

    public function daftar(Request $request, KegiatanDonor $kegiatan, PendaftaranKegiatanService $layanan)
    {
        $pendonor = $this->pendonorSaya($request);
        if (! $pendonor) {
            return $this->belumTerhubung();
        }

        try {
            $layanan->daftar($pendonor, $kegiatan);
        } catch (PendaftaranDitolak $e) {
            return back()->withErrors(['pendaftaran' => $e->getMessage()]);
        }

        return back()->with('success', "Pendaftaran berhasil. Anda terdaftar pada \"{$kegiatan->judul}\" ({$kegiatan->tanggal->translatedFormat('d F Y')}, {$kegiatan->jam()}).");
    }

    /** Membatalkan pendaftaran sendiri selama kegiatan masih terbuka dan belum lewat. */
    public function batal(Request $request, KegiatanDonor $kegiatan)
    {
        $pendonor = $this->pendonorSaya($request);
        if (! $pendonor) {
            return $this->belumTerhubung();
        }

        $pendaftaran = $pendonor->pendaftaran()->where('kegiatan_id', $kegiatan->id)->first();
        if (! $pendaftaran) {
            return back()->withErrors(['pendaftaran' => 'Anda tidak terdaftar pada kegiatan ini.']);
        }
        if (! $kegiatan->bisaDidaftar()) {
            return back()->withErrors(['pendaftaran' => 'Kegiatan sudah ditutup atau lewat. Hubungi petugas untuk membatalkan pendaftaran.']);
        }

        $pendaftaran->delete();
        LogTransaksi::catat('pendaftaran_dibatalkan', 'kegiatan', $kegiatan->id,
            "{$pendonor->nama} ({$pendonor->kode_donor}) membatalkan pendaftaran pada kegiatan \"{$kegiatan->judul}\".",
            ['lama' => ['pendonor_id' => $pendonor->id, 'kegiatan_id' => $kegiatan->id]]);

        return back()->with('success', "Pendaftaran pada \"{$kegiatan->judul}\" dibatalkan. Kuota dikembalikan.");
    }
}
