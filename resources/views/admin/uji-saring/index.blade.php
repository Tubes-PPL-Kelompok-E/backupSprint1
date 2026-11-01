@extends('layouts.app')
@section('content')
@php $in = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-500'; @endphp

<div class="flex flex-wrap items-start justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Uji Saring dan Berita Acara</h1>
        <p class="text-xs text-gray-500 mt-1">Catat hasil uji saring tiap kantong Karantina. Hanya kantong non-reaktif yang masuk stok siap pakai.</p>
    </div>
    <a href="{{ route('admin.stok') }}" class="rounded-lg border border-gray-200 bg-gray-100 px-4 py-2 text-xs font-semibold text-gray-700">Kembali ke Stok Darah</a>
</div>

<div class="grid gap-4 lg:grid-cols-5">
    {{-- Antrean --}}
    <section class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5 self-start">
        <h2 class="text-sm font-bold">Antrean uji saring
            <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600">{{ $total }} kantong</span></h2>
        <table class="mt-4 w-full text-xs">
            <thead class="text-left text-[10px] uppercase tracking-wide text-gray-500 border-b">
                <tr><th class="py-2">Kode batch</th><th>Gol / Rh</th><th>Komponen</th><th>Tgl donor</th><th>Status</th></tr>
            </thead>
            <tbody>
            @forelse ($daftar as $k)
                @php $aktif = $terpilih && $terpilih->id === $k->id; @endphp
                <tr class="border-b last:border-0 {{ $aktif ? 'bg-red-50' : 'hover:bg-gray-50' }}">
                    <td class="py-3 font-bold"><a href="{{ route('admin.uji-saring', ['batch' => $k->id]) }}">{{ $k->kode_batch }}</a></td>
                    <td>{{ $k->golonganLengkap() }}</td>
                    <td>{{ strtoupper($k->komponen) }}</td>
                    <td>{{ $k->tanggal_donor->translatedFormat('d M') }}</td>
                    <td><span class="rounded px-2 py-0.5 text-[10px] font-semibold {{ $aktif ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">{{ $aktif ? 'Dipilih' : 'Karantina' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-8 text-center text-gray-500">Tidak ada kantong berstatus Karantina.</td></tr>
            @endforelse
            </tbody>
        </table>
        <p class="mt-4 text-[11px] text-gray-400">Menampilkan {{ $daftar->count() }} dari {{ $total }} kantong Karantina, urut dari tanggal donor terlama.</p>
    </section>

    {{-- Form hasil uji --}}
    <section class="lg:col-span-3 rounded-xl border border-gray-200 bg-white p-5">
        @if ($terpilih)
        <form method="POST" action="{{ route('admin.uji-saring.store', $terpilih) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold">Hasil uji saring</h2>
                <span class="text-xs font-semibold text-gray-600">{{ $terpilih->kode_batch }}</span>
            </div>
            <p class="text-[11px] text-gray-500">{{ $terpilih->golonganLengkap() }} • {{ strtoupper($terpilih->komponen) }} {{ $terpilih->volume_ml }} ml • Donor {{ $terpilih->tanggal_donor->format('d-m-Y') }}</p>

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs p-3">
                    <ul class="list-disc pl-4 space-y-0.5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <div>
                <p class="text-xs font-semibold mb-2">Hasil uji saring</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (['non_reaktif' => 'Non-reaktif (aman)', 'reaktif' => 'Reaktif (ditahan)'] as $v => $l)
                        <label class="cursor-pointer">
                            <input type="radio" name="hasil_uji" value="{{ $v }}" class="peer sr-only" @checked(old('hasil_uji', 'non_reaktif') === $v)>
                            <span class="flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-3 text-xs font-semibold text-gray-600 peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-700">
                                <span class="h-3 w-3 rounded-full border border-gray-300"></span>{{ $l }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold mb-1">Nomor berita acara</label>
                    <input name="nomor_berita_acara" value="{{ old('nomor_berita_acara') }}" placeholder="BA-0000/UTD/X/2026" class="{{ $in }}">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1">Tanggal uji</label>
                    <input type="date" name="tanggal_uji" min="{{ $terpilih->tanggal_donor->toDateString() }}" max="{{ now()->toDateString() }}" value="{{ old('tanggal_uji', now()->toDateString()) }}" class="{{ $in }}">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Berkas berita acara</label>
                <label class="flex cursor-pointer items-center justify-between rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4">
                    <span><span id="namaBerkas" class="block text-xs font-bold">Pilih berkas...</span><span class="text-[11px] text-gray-400">PDF atau gambar (JPG/PNG), maks 5 MB</span></span>
                    <span id="badgeBerkas" class="hidden rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Terunggah</span>
                    <input id="berkas" type="file" name="berkas" accept=".pdf,.jpg,.jpeg,.png" class="sr-only">
                </label>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1">Catatan <span class="font-normal text-gray-400">(opsional)</span></label>
                <textarea name="catatan_uji" rows="3" placeholder="Catatan petugas laboratorium" class="{{ $in }}">{{ old('catatan_uji') }}</textarea>
            </div>

            <p id="infoAman" class="rounded-lg border border-green-200 bg-green-50 p-3 text-[11px] text-green-700">Hasil <b>non-reaktif</b>: status berubah dari Karantina menjadi <b>Ready</b> dan kantong masuk stok siap pakai.</p>
            <p id="infoTahan" class="hidden rounded-lg border border-amber-200 bg-amber-50 p-3 text-[11px] text-amber-700">Hasil <b>reaktif</b>: kantong berstatus <b>Ditahan</b>, tidak dapat dialokasikan, dan diproses sesuai berita acara.</p>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.stok') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700">Batal</a>
                <button class="rounded-lg bg-red-600 px-5 py-2 text-xs font-semibold text-white hover:bg-red-700">&#10003; Simpan Hasil</button>
            </div>
        </form>
        @else
            <p class="py-16 text-center text-sm text-gray-500">Semua kantong sudah diuji. Tidak ada antrean uji saring.</p>
        @endif
    </section>
</div>

<script>
    const berkas = document.getElementById('berkas');
    if (berkas) {
        berkas.addEventListener('change', () => {
            const f = berkas.files[0];
            document.getElementById('namaBerkas').textContent = f ? f.name : 'Pilih berkas...';
            document.getElementById('badgeBerkas').classList.toggle('hidden', !f);
        });
        const ubah = () => {
            const aman = document.querySelector('input[name=hasil_uji]:checked').value === 'non_reaktif';
            document.getElementById('infoAman').classList.toggle('hidden', !aman);
            document.getElementById('infoTahan').classList.toggle('hidden', aman);
        };
        document.querySelectorAll('input[name=hasil_uji]').forEach(r => r.addEventListener('change', ubah));
        ubah();
    }
</script>
@endsection
