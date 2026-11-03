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
        //
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
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
