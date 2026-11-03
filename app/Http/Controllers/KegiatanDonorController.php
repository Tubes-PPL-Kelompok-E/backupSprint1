<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\KegiatanDonorRequest;
use App\Models\KegiatanDonor;

class KegiatanDonorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    $kegiatan = KegiatanDonor::orderByDesc('tanggal')->paginate(9);

    return view('kegiatan.index', compact('kegiatan'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
{
    return view('kegiatan.create');
}

public function store(KegiatanDonorRequest $request)
{
    KegiatanDonor::create($request->validated() + [
        'status'      => 'Terbuka',
        'dibuat_oleh' => auth()->id(),
    ]);

    return redirect()->route('kegiatan-donor.index')
        ->with('success', 'Kegiatan donor berhasil dibuat.');
}

    /**
     * Display the specified resource.
     */
    public function show(KegiatanDonor $kegiatan)
    {
    return view('kegiatan.show', compact('kegiatan'));
    }

    public function edit(KegiatanDonor $kegiatan)
    {
    return view('kegiatan.edit', compact('kegiatan'));
    }

    public function update(KegiatanDonorRequest $request, KegiatanDonor $kegiatan)
    {
    $kegiatan->update($request->validated());

    return redirect()->route('kegiatan-donor.show', $kegiatan)
        ->with('success', 'Kegiatan donor berhasil diperbarui.');
    }

    public function tutup(KegiatanDonor $kegiatan)
    {
    $kegiatan->update(['status' => 'Ditutup']);

    return back()->with('success', 'Kegiatan donor berhasil ditutup.');
    }

    public function buka(KegiatanDonor $kegiatan)
    {
    $kegiatan->update(['status' => 'Terbuka']);

    return back()->with('success', 'Kegiatan donor dibuka kembali.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
