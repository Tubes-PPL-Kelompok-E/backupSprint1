<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KantongDarah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UjiSaringController extends Controller
{
    public function index(Request $request)
    {
        $antrean = KantongDarah::karantina()->orderBy('tanggal_donor')->orderBy('id');
        $total = (clone $antrean)->count();
        $daftar = $antrean->limit(5)->get();

        $terpilih = $request->query('batch')
            ? KantongDarah::karantina()->find($request->query('batch'))
            : null;
        $terpilih ??= $daftar->first();

        if ($terpilih && ! $daftar->contains('id', $terpilih->id)) {
            $daftar->prepend($terpilih);
        }

        return view('admin.uji-saring.index', [
            'title'    => 'Uji Saring dan Berita Acara',
            'daftar'   => $daftar,
            'total'    => $total,
            'terpilih' => $terpilih,
        ]);
    }

    public function store(Request $request, KantongDarah $kantong)
    {
        if ($kantong->status !== 'karantina') {
            return back()->withErrors(['kantong' => 'Kantong ini sudah tidak berstatus Karantina.']);
        }

        $data = $request->validate([
            'hasil_uji'          => ['required', 'in:non_reaktif,reaktif'],
            'nomor_berita_acara' => ['required', 'string', 'max:50'],
            'tanggal_uji'        => ['required', 'date', 'after_or_equal:' . $kantong->tanggal_donor->toDateString(), 'before_or_equal:today'],
            'berkas'             => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'catatan_uji'        => ['nullable', 'string', 'max:1000'],
        ], [
            'tanggal_uji.after_or_equal'  => 'Tanggal uji tidak boleh sebelum tanggal donor.',
            'tanggal_uji.before_or_equal' => 'Tanggal uji tidak boleh di masa depan.',
            'berkas.required'             => 'Berkas berita acara wajib diunggah.',
            'berkas.mimes'                => 'Berkas harus berformat PDF, JPG, atau PNG.',
            'berkas.max'                  => 'Ukuran berkas maksimal 5 MB.',
        ]);

        $path = $request->file('berkas')->store('berita-acara'); // disk privat (storage/app/private)

        $aman = $data['hasil_uji'] === 'non_reaktif';

        $kantong->update([
            'hasil_uji'           => $data['hasil_uji'],
            'nomor_berita_acara'  => $data['nomor_berita_acara'],
            'tanggal_uji'         => $data['tanggal_uji'],
            'berkas_berita_acara' => $path,
            'catatan_uji'         => $data['catatan_uji'] ?? null,
            'status'              => $aman ? 'ready' : 'ditahan',   // non-reaktif -> Ready, reaktif -> Ditahan
            'lokasi_simpan'       => $aman ? 'Cold Room 1' : 'Ditahan',
        ]);

        $pesan = $aman
            ? "{$kantong->kode_batch} non-reaktif: status menjadi Ready dan masuk stok siap pakai."
            : "{$kantong->kode_batch} reaktif: kantong ditahan dan tidak dapat dialokasikan.";

        return redirect()->route('admin.uji-saring')->with('success', $pesan);
    }

    // Unduh berkas berita acara (hanya admin)
    public function berkas(KantongDarah $kantong)
    {
        abort_unless($kantong->berkas_berita_acara && Storage::disk('local')->exists($kantong->berkas_berita_acara), 404);

        return Storage::disk('local')->response($kantong->berkas_berita_acara);
    }
}
