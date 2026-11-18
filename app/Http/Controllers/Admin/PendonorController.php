<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donasi;
use App\Models\LogTransaksi;
use App\Services\PemeriksaanDonorService;
use App\Models\Pendonor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Kelola data pendonor, riwayat donasi, dan pemeriksaan pra-donor. */
class PendonorController extends Controller
{
    public function index(Request $request)
    {
        $pendonor = Pendonor::query()
            ->cari($request->q)
            ->when($request->golongan, fn ($q, $g) => $q->where('golongan', $g))
            ->when($request->rhesus, fn ($q, $r) => $q->where('rhesus', $r))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('admin.pendonor.index', ['title' => 'Data Pendonor', 'pendonor' => $pendonor]);
    }

    public function create()
    {
        return view('admin.pendonor.form', ['title' => 'Tambah Pendonor', 'pendonor' => new Pendonor(), 'akunBebas' => $this->akunBebas()]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);

        // Peringatan duplikat: nama + tanggal lahir yang sama
        $duplikat = Pendonor::whereLike('nama', $data['nama'])->whereDate('tanggal_lahir', $data['tanggal_lahir'])->first();
        if ($duplikat && ! $request->boolean('konfirmasi_duplikat')) {
            return back()->withInput()->with('duplikat', "Sudah ada pendonor bernama {$duplikat->nama} dengan tanggal lahir yang sama ({$duplikat->kode_donor}). Centang konfirmasi jika ini memang orang yang berbeda.");
        }

        $pendonor = Pendonor::create($data + ['kode_donor' => Pendonor::generateKode()]);

        return redirect()->route('admin.pendonor.show', $pendonor)->with('success', "Pendonor {$pendonor->kode_donor} berhasil ditambahkan.");
    }

    public function show(Pendonor $pendonor)
    {
        return view('admin.pendonor.show', ['title' => $pendonor->nama, 'pendonor' => $pendonor->load(['donasi.kegiatan', 'user']), 'pemeriksaan' => $pendonor->pemeriksaan()->limit(5)->with('pemeriksa')->get()]);
    }

    public function edit(Pendonor $pendonor)
    {
        return view('admin.pendonor.form', ['title' => 'Ubah Pendonor', 'pendonor' => $pendonor, 'akunBebas' => $this->akunBebas($pendonor)]);
    }

    public function update(Request $request, Pendonor $pendonor)
    {
        $lama = $pendonor->only(['nama', 'tanggal_lahir', 'jenis_kelamin', 'golongan', 'rhesus', 'no_hp', 'kota', 'alamat', 'user_id']);
        $pendonor->update($this->validasi($request, $pendonor));
        $baru = $pendonor->only(array_keys($lama));

        // Perubahan data profil dicatat pada log (hanya kolom yang benar-benar berubah).
        $beda = array_keys(array_filter($baru, fn ($v, $k) => (string) $v !== (string) $lama[$k], ARRAY_FILTER_USE_BOTH));
        if ($beda) {
            LogTransaksi::catat('pendonor_diubah', 'pendonor', $pendonor->id,
                "Data pendonor {$pendonor->nama} ({$pendonor->kode_donor}) diubah: " . implode(', ', $beda) . '.',
                ['lama' => array_intersect_key($lama, array_flip($beda)), 'baru' => array_intersect_key($baru, array_flip($beda))]);
        }

        return redirect()->route('admin.pendonor.show', $pendonor)->with('success', 'Data pendonor diperbarui.');
    }

    public function destroy(Pendonor $pendonor)
    {
        $pendonor->delete();

        return redirect()->route('admin.pendonor')->with('success', 'Pendonor dinonaktifkan.');
    }

    /** Catat donasi (riwayat); menentukan kelayakan donor berikutnya. */
    public function catatDonasi(Request $request, Pendonor $pendonor)
    {
        $data = $this->validasiDonasi($request);

        // pemeriksaan hari ini yang menyatakan tidak layak menghalangi pencatatan donasi.
        $periksaHariIni = $pendonor->pemeriksaan()->whereDate('tanggal', today())->first();
        if ($periksaHariIni && ! $periksaHariIni->layak()) {
            return back()->withErrors(['donasi' => 'Pemeriksaan pra-donor hari ini menyatakan pendonor TIDAK LAYAK. Donasi tidak dapat dicatat.']);
        }

        $galat = DB::transaction(function () use ($pendonor, $data) {
            Pendonor::whereKey($pendonor->id)->lockForUpdate()->first();   // cegah dua catatan bersamaan lolos pemeriksaan jeda
            if ($galat = $this->pelanggaranJeda($pendonor, $data['tanggal_donor'])) {
                return $galat;
            }
            $donasi = Donasi::create($data + ['pendonor_id' => $pendonor->id]);
            LogTransaksi::catat('donasi_dicatat', 'pendonor', $pendonor->id,
                "Donasi {$pendonor->nama} ({$pendonor->kode_donor}) tanggal {$data['tanggal_donor']} dicatat ({$data['volume_ml']} ml).",
                ['baru' => $donasi->only(['id', 'tanggal_donor', 'volume_ml', 'lokasi'])]);

            return null;
        });

        return $galat ? back()->withInput()->withErrors(['donasi' => $galat])
            : back()->with('success', 'Donasi dicatat. Kelayakan donor berikutnya dihitung ulang.');
    }

    /** Koreksi donasi yang salah catat. */
    public function ubahDonasi(Request $request, Pendonor $pendonor, Donasi $donasi)
    {
        abort_unless($donasi->pendonor_id === $pendonor->id, 404);
        $data = $this->validasiDonasi($request);

        $galat = DB::transaction(function () use ($pendonor, $donasi, $data) {
            Pendonor::whereKey($pendonor->id)->lockForUpdate()->first();
            if ($galat = $this->pelanggaranJeda($pendonor, $data['tanggal_donor'], $donasi->id)) {
                return $galat;
            }
            $lama = $donasi->only(['tanggal_donor', 'volume_ml', 'lokasi']);
            $donasi->update($data);
            LogTransaksi::catat('donasi_diubah', 'pendonor', $pendonor->id,
                "Donasi {$pendonor->nama} ({$pendonor->kode_donor}) diubah.", ['lama' => $lama, 'baru' => $donasi->only(['tanggal_donor', 'volume_ml', 'lokasi'])]);

            return null;
        });

        return $galat ? back()->withErrors(['donasi' => $galat]) : back()->with('success', 'Donasi diperbarui. Kelayakan donor dihitung ulang.');
    }

    public function hapusDonasi(Pendonor $pendonor, Donasi $donasi)
    {
        abort_unless($donasi->pendonor_id === $pendonor->id, 404);

        DB::transaction(function () use ($pendonor, $donasi) {
            LogTransaksi::catat('donasi_dihapus', 'pendonor', $pendonor->id,
                "Donasi {$pendonor->nama} ({$pendonor->kode_donor}) tanggal {$donasi->tanggal_donor->toDateString()} dihapus.",
                ['lama' => $donasi->only(['tanggal_donor', 'volume_ml', 'lokasi'])]);
            $donasi->delete();
        });

        return back()->with('success', 'Donasi dihapus. Kelayakan donor dihitung ulang.');
    }

    private function validasiDonasi(Request $request): array
    {
        return $request->validate([
            'tanggal_donor' => ['required', 'date', 'before_or_equal:today'],
            'volume_ml'     => ['required', 'integer', 'min:100', 'max:500'],
            'lokasi'        => ['nullable', 'string', 'max:150'],
        ], ['tanggal_donor.before_or_equal' => 'Tanggal donor tidak boleh di masa depan.']);
    }

    /**
     * Jarak antar donasi minimal sebesar jeda donor, ke arah sebelum maupun sesudah tanggal baru
     * (sehingga pencatatan donasi lama tetap bisa, tetapi donasi ganda atau terlalu rapat ditolak).
     */
    private function pelanggaranJeda(Pendonor $pendonor, string $tanggal, ?int $kecualiId = null): ?string
    {
        $jeda = (int) config('bloodconnect.interval_donor_hari');
        $t = Carbon::parse($tanggal)->startOfDay();

        $dekat = $pendonor->donasi()->reorder()
            ->when($kecualiId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->whereDate('tanggal_donor', '>', $t->copy()->subDays($jeda))
            ->whereDate('tanggal_donor', '<', $t->copy()->addDays($jeda))
            ->orderBy('tanggal_donor')->first();

        return $dekat
            ? "Jarak antar donasi minimal {$jeda} hari. Sudah ada donasi pada {$dekat->tanggal_donor->translatedFormat('d F Y')}."
            : null;
    }

    /** catat pemeriksaan pra-donor; sistem menentukan Layak / Tidak Layak beserta alasannya. */
    public function periksa(Request $request, Pendonor $pendonor, PemeriksaanDonorService $layanan)
    {
        $data = $request->validate([
            'hemoglobin' => ['required', 'numeric', 'between:3,25'],
            'sistolik'   => ['required', 'integer', 'between:50,260'],
            'diastolik'  => ['required', 'integer', 'between:30,180', 'lt:sistolik'],
            'berat_kg'   => ['required', 'numeric', 'between:20,250'],
            'suhu_tubuh' => ['required', 'numeric', 'between:34,42'],
            'nadi'       => ['required', 'integer', 'between:30,220'],
            'catatan'    => ['nullable', 'string', 'max:500'],
        ], [
            'hemoglobin.required' => 'Hemoglobin wajib diisi.', 'hemoglobin.between' => 'Hemoglobin tidak masuk akal (3-25 g/dL).',
            'sistolik.required' => 'Tekanan sistolik wajib diisi.', 'diastolik.required' => 'Tekanan diastolik wajib diisi.',
            'diastolik.lt' => 'Tekanan diastolik harus lebih rendah dari sistolik.',
            'berat_kg.required' => 'Berat badan wajib diisi.', 'suhu_tubuh.required' => 'Suhu tubuh wajib diisi.', 'nadi.required' => 'Denyut nadi wajib diisi.',
        ]);

        $p = $layanan->catat($pendonor, $data, $request->user());

        return back()->with($p->layak() ? 'success' : 'warning',
            $p->layak() ? 'Pemeriksaan selesai: pendonor LAYAK mendonor.' : 'Pemeriksaan selesai: pendonor TIDAK LAYAK. ' . implode(' ', $p->daftarAlasan()));
    }

    private function validasi(Request $request, ?Pendonor $pendonor = null): array
    {
        return $request->validate([
            'nama'          => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date', 'before:-17 years', 'after:-66 years'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'golongan'      => ['required', 'in:A,B,AB,O'],
            'rhesus'        => ['required', 'in:pos,neg'],
            'no_hp'         => ['nullable', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'kota'          => ['required', 'string', 'max:100'],
            'alamat'        => ['nullable', 'string', 'max:500'],
            'user_id'       => ['nullable', 'integer', Rule::exists('users', 'id'), Rule::unique('pendonor', 'user_id')->ignore($pendonor?->id),
                function ($attribute, $value, $fail) {
                    if ($value && ! User::role('pendonor')->whereKey($value)->exists()) {
                        $fail('Akun yang dihubungkan harus berperan Pendonor.');
                    }
                }],
        ], [
            'tanggal_lahir.before' => 'Usia pendonor minimal 17 tahun.',
            'tanggal_lahir.after'  => 'Usia pendonor maksimal 65 tahun.',
            'no_hp.regex'          => 'Nomor telepon tidak valid.',
            'user_id.unique'       => 'Akun tersebut sudah terhubung dengan pendonor lain.',
        ]);
    }

    /** Akun berperan pendonor yang belum terhubung ke data pendonor mana pun. */
    private function akunBebas(?Pendonor $sendiri = null)
    {
        return User::role('pendonor')
            ->where(fn ($q) => $q->whereDoesntHave('pendonor')->when($sendiri?->user_id, fn ($w, $id) => $w->orWhere('id', $id)))
            ->orderBy('name')->get();
    }
}
