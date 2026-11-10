<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KegiatanDonor;
use App\Models\PendaftaranKegiatan;
use App\Models\LogTransaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Kelola kegiatan donor. Pendaftaran oleh pendonor ada di Pendonor\KegiatanController. */
class KegiatanDonorController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $kegiatan = KegiatanDonor::withCount('pendaftaran')
            ->when(array_key_exists((string) $status, KegiatanDonor::STATUS), fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'terbuka' THEN 0 ELSE 1 END")
            ->orderBy('tanggal')
            ->paginate(9)
            ->withQueryString();

        return view('admin.kegiatan.index', ['title' => 'Kegiatan Donor Darah', 'kegiatan' => $kegiatan, 'status' => $status]);
    }

    public function create()
    {
        return view('admin.kegiatan.create', ['title' => 'Buat Kegiatan Donor']);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);

        $kegiatan = KegiatanDonor::create($data + ['status' => 'terbuka', 'dibuat_oleh' => $request->user()->id]);

        return redirect()->route('admin.kegiatan.show', $kegiatan)
            ->with('success', 'Kegiatan donor berhasil dibuat dan tampil di portal pendonor.');
    }

    public function edit(KegiatanDonor $kegiatan)
    {
        if ($kegiatan->status !== 'terbuka') {
            return redirect()->route('admin.kegiatan.show', $kegiatan)->withErrors(['kegiatan' => 'Hanya kegiatan yang masih terbuka yang dapat diubah.']);
        }

        return view('admin.kegiatan.create', ['title' => 'Ubah Kegiatan Donor', 'kegiatan' => $kegiatan]);
    }

    public function update(Request $request, KegiatanDonor $kegiatan)
    {
        $data = $this->validasi($request);

        DB::transaction(function () use ($kegiatan, $data, $request) {
            // Kunci baris kegiatan: pendonor yang mendaftar bersamaan menunggu sampai perubahan selesai.
            $k = KegiatanDonor::whereKey($kegiatan->id)->lockForUpdate()->firstOrFail();
            if ($k->status !== 'terbuka') {
                throw ValidationException::withMessages(['kegiatan' => 'Hanya kegiatan yang masih terbuka yang dapat diubah.']);
            }
            $terdaftar = $k->pendaftaran()->count();
            if ($data['kuota'] < $terdaftar) {
                throw ValidationException::withMessages(['kuota' => "Kuota tidak boleh di bawah jumlah pendaftar saat ini ($terdaftar)."]);
            }

            $lama = $k->only(array_keys($data));
            $k->update($data);
            LogTransaksi::catat('kegiatan_diubah', 'kegiatan', $k->id, "Kegiatan \"{$k->judul}\" diubah.",
                ['lama' => $lama, 'baru' => $k->only(array_keys($data)), 'user' => $request->user()]);
        });

        return redirect()->route('admin.kegiatan.show', $kegiatan)->with('success', 'Kegiatan donor diperbarui.');
    }

    public function show(KegiatanDonor $kegiatan)
    {
        $kegiatan->loadCount('pendaftaran');
        $peserta = $kegiatan->pendaftaran()->with('pendonor')->orderBy('tanggal_daftar')->get();

        return view('admin.kegiatan.show', ['title' => $kegiatan->judul, 'kegiatan' => $kegiatan, 'peserta' => $peserta]);
    }

    public function tutup(KegiatanDonor $kegiatan)
    {
        return $this->ubahStatus($kegiatan, 'ditutup');
    }

    public function batalkan(KegiatanDonor $kegiatan)
    {
        return $this->ubahStatus($kegiatan, 'dibatalkan');
    }

    private function ubahStatus(KegiatanDonor $kegiatan, string $status)
    {
        if ($kegiatan->status !== 'terbuka') {
            return back()->withErrors(['kegiatan' => 'Hanya kegiatan yang masih terbuka yang dapat ' . ($status === 'ditutup' ? 'ditutup' : 'dibatalkan') . '.']);
        }

        $kegiatan->update(['status' => $status]);

        return back()->with('success', "Kegiatan {$status}.");
    }

    /** Tandai kehadiran peserta pada hari kegiatan. */
    public function kehadiran(Request $request, KegiatanDonor $kegiatan, PendaftaranKegiatan $pendaftaran)
    {
        abort_unless($pendaftaran->kegiatan_id === $kegiatan->id, 404);

        $pendaftaran->update($request->validate(['status_kehadiran' => ['required', 'in:terdaftar,hadir,tidak_hadir']]));

        return back()->with('success', 'Kehadiran diperbarui.');
    }

    /** Kegiatan yang sudah memiliki pendaftar tidak dapat dihapus, hanya ditutup/dibatalkan. */
    public function destroy(KegiatanDonor $kegiatan)
    {
        if ($kegiatan->pendaftaran()->exists()) {
            return back()->withErrors(['kegiatan' => 'Kegiatan yang sudah memiliki pendaftar tidak dapat dihapus; tutup atau batalkan saja.']);
        }

        $kegiatan->delete();

        return redirect()->route('admin.kegiatan')->with('success', 'Kegiatan dihapus.');
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'judul'       => ['required', 'string', 'max:150'],
            'tanggal'     => ['required', 'date', 'after_or_equal:today'],
            'jam_mulai'   => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'kuota'       => ['required', 'integer', 'min:1', 'max:5000'],
            'nama_tempat' => ['required', 'string', 'max:150'],
            'alamat'      => ['required', 'string', 'max:500'],
        ], [
            'tanggal.after_or_equal' => 'Tanggal kegiatan tidak boleh di masa lalu.',
            'kuota.min'              => 'Kuota minimal 1 peserta.',
            'jam_selesai.after'      => 'Jam selesai harus setelah jam mulai.',
        ]);
    }
}
