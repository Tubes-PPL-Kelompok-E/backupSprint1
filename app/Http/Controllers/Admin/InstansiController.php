<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstansiRequest;
use App\Http\Requests\UpdateInstansiRequest;
use App\Models\Instansi;
use Illuminate\Http\Request;

class InstansiController extends Controller
{
    // READ: daftar + pencarian + filter tipe
    public function index(Request $request)
    {
        $daftar = Instansi::query()
            ->cari($request->q)
            ->when($request->tipe, fn ($q, $t) => $q->where('tipe', $t))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('admin.instansi.index', ['title' => 'Data Instansi', 'daftar' => $daftar]);
    }

    // CREATE
    public function create()
    {
        return view('admin.instansi.create', ['title' => 'Tambah Instansi']);
    }

    public function store(StoreInstansiRequest $request)
    {
        $data = $request->validated();
        $data['kode'] = $data['kode'] ?? Instansi::generateKode($data['tipe']);

        $instansi = Instansi::create($data);

        return redirect()->route('instansi.index')
            ->with('success', "Instansi {$instansi->nama} berhasil ditambahkan dengan kode {$instansi->kode}.");
    }

    // UPDATE
    public function edit(Instansi $instansi)
    {
        return view('admin.instansi.edit', ['title' => 'Ubah Instansi', 'instansi' => $instansi]);
    }

    public function update(UpdateInstansiRequest $request, Instansi $instansi)
    {
        $instansi->update($request->validated());

        return redirect()->route('instansi.index')->with('success', 'Data instansi berhasil diperbarui.');
    }

    // DELETE (soft delete, kode tetap tercatat dan tidak bisa dipakai ulang)
    public function destroy(Instansi $instansi)
    {
        $instansi->delete();

        return redirect()->route('instansi.index')->with('success', "Instansi {$instansi->nama} berhasil dihapus.");
    }
}
