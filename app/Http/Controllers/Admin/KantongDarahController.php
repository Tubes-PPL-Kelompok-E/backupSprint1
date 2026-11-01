<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKantongRequest;
use App\Models\KantongDarah;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KantongDarahController extends Controller
{
    // Daftar stok, urut FEFO (kedaluwarsa terdekat dulu)
    public function index(Request $request)
    {
        $kantong = KantongDarah::query()
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('kode_batch', 'like', "%{$s}%")
                ->orWhere('lokasi_simpan', 'like', "%{$s}%")))
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

        $data['kode_batch'] = $data['kode_batch'] ?? KantongDarah::generateKode($data['tanggal_donor'], $data['golongan']);
        $data['tanggal_kedaluwarsa'] = Carbon::parse($data['tanggal_donor'])->addDays(KantongDarah::MASA_SIMPAN[$data['komponen']]);
        $data['status'] = 'karantina';           // kantong baru wajib lolos uji saring dulu
        $data['lokasi_simpan'] = 'Karantina';
        $data['dibuat_oleh'] = $request->user()->id;

        $kantong = KantongDarah::create($data);
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

        $tanggal = $request->query('tanggal');
        $gol = $request->query('golongan');
        if ($tanggal && strtotime($tanggal) && in_array($gol, ['A', 'B', 'AB', 'O'], true)) {
            $saran = KantongDarah::generateKode($tanggal, $gol);
        }

        return response()->json([
            'tersedia' => $kode !== '' && ! KantongDarah::where('kode_batch', $kode)->exists(),
            'saran'    => $saran,
        ]);
    }

    // Catat pemusnahan kantong yang sudah kedaluwarsa
    public function musnahkan(KantongDarah $kantong)
    {
        if ($kantong->sisaHari() >= 0 || $kantong->status === 'dimusnahkan') {
            return back()->withErrors(['kantong' => 'Hanya kantong kedaluwarsa yang dapat dicatat pemusnahannya.']);
        }

        $kantong->update(['status' => 'dimusnahkan', 'lokasi_simpan' => 'Dimusnahkan']);

        return back()->with('success', "Pemusnahan {$kantong->kode_batch} berhasil dicatat.");
    }
}
