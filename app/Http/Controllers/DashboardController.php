<?php

namespace App\Http\Controllers;

use App\Services\StokService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private StokService $stok)
    {
    }

    public function admin(Request $request)
    {
        return view('admin.dashboard', [
            'title' => 'Dashboard Admin UTD PMI',
            'data'  => $this->stok->dashboard($request->query('komponen')),
        ]);
    }

    public function rs(Request $request)
    {
        $perKombinasi = $this->stok->stokSiapPerKombinasi();
        $katalog = [];
        foreach (['A', 'B', 'AB', 'O'] as $g) {
            $prc = ($perKombinasi["$g|pos|prc"] ?? 0) + ($perKombinasi["$g|neg|prc"] ?? 0);
            $wb = ($perKombinasi["$g|pos|wb"] ?? 0) + ($perKombinasi["$g|neg|wb"] ?? 0);
            $total = $perKombinasi->filter(fn ($v, $k) => str_starts_with($k, "$g|"))->sum();
            $katalog[] = ['golongan' => $g, 'prc' => $prc, 'wb' => $wb, 'total' => $total,
                'status' => $total === 0 ? 'Kosong' : ($total < 10 ? 'Terbatas' : 'Tersedia')];
        }

        return view('rs.dashboard', [
            'title'   => 'Dashboard Rumah Sakit',
            'data'    => $this->stok->dashboard($request->query('komponen')),
            'rs'      => $request->user()->instansiRs(),
            'katalog' => $katalog,
        ]);
    }

    public function data(Request $request)
    {
        return response()->json($this->stok->dashboard($request->query('komponen')));
    }
}
