<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BatasMinimumStok;
use App\Models\LogTransaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** kelola batas minimum stok (nilai bawaan dari config, dapat diubah per golongan/rhesus/komponen). */
class BatasMinimumController extends Controller
{
    public function index()
    {
        return view('admin.batas-minimum', [
            'title'   => 'Batas Minimum Stok',
            'peta'    => BatasMinimumStok::peta(),
            'default' => config('bloodconnect.batas_minimum_default'),
            'custom'  => BatasMinimumStok::count(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'batas'       => ['required', 'array'],
            'batas.*.*.*' => ['required', 'integer', 'min:0', 'max:9999'],
        ], [
            'batas.*.*.*.required' => 'Semua batas minimum wajib diisi.',
            'batas.*.*.*.integer'  => 'Batas minimum harus berupa bilangan bulat.',
            'batas.*.*.*.min'      => 'Batas minimum tidak boleh negatif.',
            'batas.*.*.*.max'      => 'Batas minimum maksimal 9999 kantong.',
        ]);

        $lama = BatasMinimumStok::peta();
        $baru = [];
        foreach (BatasMinimumStok::GOLONGAN as $g) {
            foreach (array_keys(BatasMinimumStok::RHESUS) as $r) {
                foreach (BatasMinimumStok::KOMPONEN as $k) {
                    $nilai = $request->input("batas.$g.$r.$k");
                    if ($nilai !== null && (int) $nilai !== $lama["$g|$r|$k"]) {
                        $baru["$g|$r|$k"] = (int) $nilai;
                    }
                }
            }
        }

        DB::transaction(function () use ($baru, $request, $lama) {
            foreach ($baru as $kunci => $nilai) {
                [$g, $r, $k] = explode('|', $kunci);
                BatasMinimumStok::updateOrCreate(
                    ['golongan' => $g, 'rhesus' => $r, 'komponen' => $k],
                    ['minimum' => $nilai, 'diubah_oleh' => $request->user()->id]
                );
            }

            if ($baru) {
                LogTransaksi::catat('batas_minimum_diubah', 'batas_minimum', null,
                    count($baru) . ' batas minimum stok diubah.',
                    ['lama' => array_intersect_key($lama, $baru), 'baru' => $baru]);
            }
        });

        return redirect()->route('admin.batas-minimum')->with('success',
            $baru ? count($baru) . ' batas minimum berhasil diperbarui.' : 'Tidak ada perubahan.');
    }

    /** Hapus semua nilai khusus sehingga kembali ke nilai bawaan config. */
    public function reset()
    {
        $jumlah = BatasMinimumStok::count();
        BatasMinimumStok::query()->delete();

        if ($jumlah) {
            LogTransaksi::catat('batas_minimum_diubah', 'batas_minimum', null, 'Batas minimum stok dikembalikan ke nilai bawaan.');
        }

        return redirect()->route('admin.batas-minimum')->with('success', 'Batas minimum dikembalikan ke nilai bawaan.');
    }
}
