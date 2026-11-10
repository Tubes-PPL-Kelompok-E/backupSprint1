<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKantongRequest;
use App\Models\KantongDarah;
use App\Models\LogTransaksi;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KantongDarahController extends Controller
{
    // Daftar stok, urut FEFO (kedaluwarsa terdekat dulu)
    public function index(Request $request)
    {
        $kantong = KantongDarah::query()
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                ->whereLike('kode_batch', "%{$s}%")
                ->orWhereLike('lokasi_simpan', "%{$s}%")))
            ->when($request->golongan, fn ($q, $g) => $q->where('golongan', $g))
            ->when($request->komponen, fn ($q, $k) => $q->where('komponen', $k))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('tanggal_kedaluwarsa')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.stok', ['title' => 'Stok Darah', 'kantong' => $kantong]);
    }

    public function create()
    {
        return view('admin.kantong.create', ['title' => 'Input Kantong Darah Masuk']);
    }

    public function store(StoreKantongRequest $request)
    {
        $data = $request->validated();

        $otomatis = $data['kode_batch'] === null;
        $data['kode_batch'] ??= KantongDarah::generateKode($data['tanggal_donor'], $data['golongan']);
        $data['tanggal_kedaluwarsa'] = Carbon::parse($data['tanggal_donor'])->addDays(KantongDarah::MASA_SIMPAN[$data['komponen']]);
        $data['status'] = 'karantina';           // kantong baru wajib lolos uji saring dulu
        $data['lokasi_simpan'] = 'Karantina';
        $data['dibuat_oleh'] = $request->user()->id;

        // Kode otomatis dibuat dari hitungan batch; dua petugas yang menyimpan bersamaan bisa menghasilkan kode sama, jadi coba lagi.
        for ($coba = 1; ; $coba++) {
            try {
                $kantong = KantongDarah::create($data);
                break;
            } catch (UniqueConstraintViolationException $e) {
                if (! $otomatis || $coba >= 3) {
                    throw $e;
                }
                $data['kode_batch'] = KantongDarah::generateKode($data['tanggal_donor'], $data['golongan']);
            }
        }
        LogTransaksi::catat('kantong_masuk', 'kantong', $kantong->id,
            "Kantong {$kantong->kode_batch} ({$kantong->golonganLengkap()} {$kantong->komponen}) dicatat masuk berstatus Karantina.",
            ['baru' => $kantong->untukLog() + ['status' => 'karantina']]);
        $pesan = "Kantong {$kantong->kode_batch} berhasil dicatat dengan status Karantina.";

        return $request->has('tambah_lagi')
            ? redirect()->route('admin.kantong.create')->with('success', $pesan)
            : redirect()->route('admin.stok')->with('success', $pesan);
    }

    // Dipakai form untuk memeriksa keunikan kode dan menyarankan kode otomatis
    public function cekBatch(Request $request)
    {
        $kode = strtoupper(trim((string) $request->query('kode')));
        $saran = null;

        $kecuali = $request->query('kecuali');   // saat mengubah kantong: abaikan kode milik sendiri
        $tanggal = $request->query('tanggal');
        $gol = $request->query('golongan');
        if ($tanggal && strtotime($tanggal) && in_array($gol, ['A', 'B', 'AB', 'O'], true)) {
            $saran = KantongDarah::generateKode($tanggal, $gol);
        }

        return response()->json([
            'tersedia' => $kode !== '' && ! KantongDarah::where('kode_batch', $kode)->when($kecuali, fn ($q, $id) => $q->where('id', '!=', $id))->exists(),
            'saran'    => $saran,
        ]);
    }

    // Ubah data kantong yang masih Karantina (salah input)
    public function edit(KantongDarah $kantong)
    {
        if ($kantong->status !== 'karantina') {
            return redirect()->route('admin.stok')->withErrors(['kantong' => self::PESAN_BUKAN_KARANTINA]);
        }

        return view('admin.kantong.create', ['title' => 'Ubah Kantong Darah', 'kantong' => $kantong]);
    }

    public function update(StoreKantongRequest $request, KantongDarah $kantong)
    {
        $data = $request->validated();
        $data['kode_batch'] ??= $kantong->kode_batch;
        $data['tanggal_kedaluwarsa'] = Carbon::parse($data['tanggal_donor'])->addDays(KantongDarah::MASA_SIMPAN[$data['komponen']]);

        DB::transaction(function () use ($kantong, $data) {
            $k = KantongDarah::whereKey($kantong->id)->lockForUpdate()->firstOrFail();
            if ($k->status !== 'karantina') {
                throw ValidationException::withMessages(['kantong' => self::PESAN_BUKAN_KARANTINA]);
            }

            $lama = $k->untukLog();
            $k->update($data);
            LogTransaksi::catat('kantong_diubah', 'kantong', $k->id,
                "Data kantong {$k->kode_batch} diubah saat masih Karantina.", ['lama' => $lama, 'baru' => $k->untukLog()]);
        });

        return redirect()->route('admin.stok')->with('success', "Data kantong {$data['kode_batch']} diperbarui.");
    }

    // Hapus kantong yang salah input (hanya saat Karantina). Jejaknya tetap ada pada log transaksi.
    public function destroy(KantongDarah $kantong)
    {
        DB::transaction(function () use ($kantong) {
            $k = KantongDarah::whereKey($kantong->id)->lockForUpdate()->firstOrFail();
            if ($k->status !== 'karantina') {
                throw ValidationException::withMessages(['kantong' => self::PESAN_BUKAN_KARANTINA]);
            }

            LogTransaksi::catat('kantong_dihapus', 'kantong', $k->id,
                "Kantong {$k->kode_batch} dihapus karena salah input (masih Karantina).", ['lama' => $k->untukLog()]);
            $k->delete();
        });

        return redirect()->route('admin.stok')->with('success', "Kantong {$kantong->kode_batch} dihapus.");
    }

    private const PESAN_BUKAN_KARANTINA = 'Hanya kantong berstatus Karantina yang dapat diubah atau dihapus.';

    // Catat pemusnahan kantong yang sudah kedaluwarsa
    public function musnahkan(KantongDarah $kantong)
    {
        if ($kantong->sisaHari() >= 0 || $kantong->status === 'dimusnahkan') {
            return back()->withErrors(['kantong' => 'Hanya kantong kedaluwarsa yang dapat dicatat pemusnahannya.']);
        }

        $statusLama = $kantong->status;
        $kantong->update(['status' => 'dimusnahkan', 'lokasi_simpan' => 'Dimusnahkan']);
        LogTransaksi::catat('kantong_dimusnahkan', 'kantong', $kantong->id,
            "Pemusnahan kantong {$kantong->kode_batch} dicatat (kedaluwarsa {$kantong->tanggal_kedaluwarsa->toDateString()}).",
            ['lama' => ['status' => $statusLama], 'baru' => $kantong->untukLog() + ['status' => 'dimusnahkan']]);

        return back()->with('success', "Pemusnahan {$kantong->kode_batch} berhasil dicatat.");
    }
}
