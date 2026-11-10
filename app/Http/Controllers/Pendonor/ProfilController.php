<?php

namespace App\Http\Controllers\Pendonor;

use App\Http\Controllers\Controller;
use App\Models\LogTransaksi;
use Illuminate\Http\Request;

/**
 * Pendonor melihat profil, riwayat, dan kelayakan, serta memperbarui data kontak.
 * Identitas dan golongan darah hanya dapat diubah Petugas: input lain diabaikan.
 */
class ProfilController extends Controller
{
    use Concerns;

    public function show(Request $request)
    {
        $pendonor = $this->pendonorSaya($request);
        if (! $pendonor) {
            return $this->belumTerhubung();
        }

        return view('pendonor.profil', [
            'title'      => 'Profil Saya',
            'pendonor'   => $pendonor,
            'riwayat'    => $pendonor->donasi()->with('kegiatan')->limit(10)->get(),
            'layak'      => $pendonor->layakPada(),
            'layakDari'  => $pendonor->tanggalLayak(),
            'pemeriksaan' => $pendonor->pemeriksaan()->first(),
        ]);
    }

    public function update(Request $request)
    {
        $pendonor = $this->pendonorSaya($request);
        if (! $pendonor) {
            return $this->belumTerhubung();
        }

        $data = $request->validate([
            'no_hp'  => ['required', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'kota'   => ['required', 'string', 'max:100'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ], [
            'no_hp.required' => 'Nomor telepon wajib diisi.',
            'no_hp.regex'    => 'Nomor telepon tidak valid. Gunakan 8-20 karakter angka, spasi, + atau -.',
            'kota.required'  => 'Kota wajib diisi.',
        ]);

        $lama = $pendonor->only(['no_hp', 'kota', 'alamat']);
        $pendonor->update($data);

        if ($lama != $pendonor->only(['no_hp', 'kota', 'alamat'])) {
            LogTransaksi::catat('pendonor_diubah', 'pendonor', $pendonor->id,
                "{$pendonor->nama} ({$pendonor->kode_donor}) memperbarui data kontak.",
                ['lama' => $lama, 'baru' => $pendonor->only(['no_hp', 'kota', 'alamat'])]);
        }

        return redirect()->route('pendonor.profil')->with('success', 'Data kontak berhasil diperbarui.');
    }
}
