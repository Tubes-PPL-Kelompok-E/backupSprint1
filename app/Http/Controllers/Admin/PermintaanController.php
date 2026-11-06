<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermintaanRequest;
use App\Models\Instansi;
use App\Models\KantongDarah;
use App\Models\PermintaanDarah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PermintaanController extends Controller
{
    // Daftar permintaan: CITO selalu di atas, lalu yang terbaru
    public function index(Request $request)
    {
        $status = $request->query('status');

        $permintaan = PermintaanDarah::with(['instansi', 'kantong'])
            ->when(array_key_exists((string) $status, PermintaanDarah::STATUS), fn ($q) => $q->where('status', $status))
            ->orderByRaw("CASE WHEN prioritas = 'cito' THEN 0 ELSE 1 END")
            ->orderByDesc('diajukan_pada')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $hitung = PermintaanDarah::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        // Stok siap (Ready, belum kedaluwarsa) per golongan, rhesus, dan komponen
        $stokSiap = KantongDarah::where('status', 'ready')
            ->whereDate('tanggal_kedaluwarsa', '>=', today())
            ->selectRaw('golongan, rhesus, komponen, count(*) as total')
            ->groupBy('golongan', 'rhesus', 'komponen')
            ->get()
            ->mapWithKeys(fn ($r) => ["{$r->golongan}|{$r->rhesus}|{$r->komponen}" => (int) $r->total]);

        return view('admin.permintaan', [
            'title'      => 'Permintaan RS',
            'permintaan' => $permintaan,
            'hitung'     => $hitung,
            'stokSiap'   => $stokSiap,
            'status'     => $status,
        ]);
    }

    public function create()
    {
        return view('admin.permintaan.create', [
            'title'       => 'Catat Permintaan RS',
            'rumahSakit'  => Instansi::where('tipe', 'rumah_sakit')->orderBy('nama')->get(),
        ]);
    }

    public function store(StorePermintaanRequest $request)
    {
        $data = $request->validated();
        $data['kode'] = PermintaanDarah::generateKode();
        $data['status'] = 'menunggu';
        $data['diajukan_pada'] = now();
        $data['dibuat_oleh'] = $request->user()->id;

        $permintaan = PermintaanDarah::create($data);

        return redirect()->route('admin.permintaan')
            ->with('success', "Permintaan {$permintaan->kode} berhasil dicatat dan menunggu persetujuan.");
    }

    /** Validasi: alokasikan kantong dengan kedaluwarsa terdekat (FEFO) dan tugaskan kurir. */
    public function validasi(Request $request, PermintaanDarah $permintaan)
    {
        $data = $request->validate([
            'kurir_nama'      => ['required', 'string', 'max:100'],
            'kurir_kendaraan' => ['required', 'string', 'max:30'],
        ], [
            'kurir_nama.required'      => 'Nama kurir wajib diisi.',
            'kurir_kendaraan.required' => 'Nomor kendaraan kurir wajib diisi.',
        ]);

        try {
            DB::transaction(function () use ($permintaan, $data, $request) {
                $p = PermintaanDarah::whereKey($permintaan->id)->lockForUpdate()->firstOrFail();

                if ($p->status !== 'menunggu') {
                    throw new RuntimeException('Permintaan ini sudah diproses.');
                }

                $kandidat = KantongDarah::where('status', 'ready')
                    ->where('golongan', $p->golongan)
                    ->where('rhesus', $p->rhesus)
                    ->where('komponen', $p->komponen)
                    ->whereDate('tanggal_kedaluwarsa', '>=', today())
                    ->orderBy('tanggal_kedaluwarsa')   // FEFO: yang paling cepat kedaluwarsa dulu
                    ->orderBy('id')
                    ->limit($p->jumlah)
                    ->lockForUpdate()
                    ->get();

                if ($kandidat->count() < $p->jumlah) {
                    throw new RuntimeException(
                        "Stok siap tidak mencukupi: tersedia {$kandidat->count()} dari {$p->jumlah} kantong {$p->golonganLengkap()} " . strtoupper($p->komponen) . '.'
                    );
                }

                foreach ($kandidat as $kantong) {
                    $kantong->update(['status' => 'reserved', 'lokasi_simpan' => "Dialokasikan {$p->kode}"]);
                }
                $p->kantong()->attach($kandidat->pluck('id')->all());

                $p->update([
                    'status'          => 'dalam_perjalanan',
                    'kurir_nama'      => $data['kurir_nama'],
                    'kurir_kendaraan' => $data['kurir_kendaraan'],
                    'divalidasi_oleh' => $request->user()->id,
                    'divalidasi_pada' => now(),
                ]);
            });
        } catch (RuntimeException $e) {
            return back()->withErrors(['permintaan' => $e->getMessage()]);
        }

        return back()->with('success', "Permintaan {$permintaan->kode} divalidasi. Kantong dialokasikan (FEFO) dan kurir ditugaskan.");
    }

    public function tolak(Request $request, PermintaanDarah $permintaan)
    {
        if ($permintaan->status !== 'menunggu') {
            return back()->withErrors(['permintaan' => 'Hanya permintaan yang menunggu persetujuan yang dapat ditolak.']);
        }

        $data = $request->validate(
            ['alasan_penolakan' => ['required', 'string', 'max:255']],
            ['alasan_penolakan.required' => 'Alasan penolakan wajib diisi.']
        );

        $permintaan->update([
            'status'           => 'ditolak',
            'alasan_penolakan' => $data['alasan_penolakan'],
            'divalidasi_oleh'  => $request->user()->id,
            'divalidasi_pada'  => now(),
        ]);

        return back()->with('success', "Permintaan {$permintaan->kode} ditolak.");
    }

    /** Perbarui suhu cold box dan posisi kurir selama pengiriman. */
    public function lacak(Request $request, PermintaanDarah $permintaan)
    {
        if ($permintaan->status !== 'dalam_perjalanan') {
            return back()->withErrors(['permintaan' => 'Pelacakan hanya untuk permintaan yang sedang dalam perjalanan.']);
        }

        $data = $request->validate([
            'suhu_cold_box'   => ['nullable', 'numeric', 'between:-10,30'],
            'posisi_terakhir' => ['nullable', 'string', 'max:150'],
        ], [
            'suhu_cold_box.numeric' => 'Suhu cold box harus berupa angka.',
            'suhu_cold_box.between' => 'Suhu cold box harus antara -10 dan 30 derajat Celsius.',
        ]);

        $permintaan->update(array_filter($data, fn ($v) => $v !== null && $v !== ''));

        return back()->with('success', "Data perjalanan {$permintaan->kode} diperbarui.");
    }

    public function selesai(Request $request, PermintaanDarah $permintaan)
    {
        if ($permintaan->status !== 'dalam_perjalanan') {
            return back()->withErrors(['permintaan' => 'Hanya permintaan yang sedang dalam perjalanan yang dapat ditandai diterima.']);
        }

        $permintaan->update(['status' => 'diterima', 'selesai_pada' => now()]);

        return back()->with('success', "Permintaan {$permintaan->kode} selesai diterima RS.");
    }
}
