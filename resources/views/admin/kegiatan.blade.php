@extends('layouts.app')
@section('content')
<section class="rounded-xl border border-gray-200 bg-white p-5 mb-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Kegiatan Donor Darah &amp; Data Pendonor</h1>
            <p class="mt-2 text-xs text-gray-500">FR-09, FR-10, FR-12, &amp; FR-14: Pengelolaan jadwal donor mobile unit, pendaftaran peserta, dan database pendonor.</p>
        </div>
        <button class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">+ Buat Kegiatan Donor</button>
    </div>
</section>

<div class="flex gap-6 border-b border-gray-200 mb-4 text-xs font-semibold">
    <button data-tab="jadwal" class="tab pb-3 border-b-2 border-red-600 text-red-600">Jadwal &amp; Absensi Peserta ({{ count($events) }})</button>
    <button data-tab="pendonor" class="tab pb-3 border-b-2 border-transparent text-gray-500">Data Pendonor Terdaftar ({{ $pendonor->count() }})</button>
</div>

<div id="jadwal">
    <div class="grid gap-4 md:grid-cols-3 mb-4">
        @foreach ($events as [$nama, $jam, $tempat, $alamat, $tgl, $sisa, $kuota, $daftar])
            <article class="rounded-xl border border-gray-200 bg-white p-4 text-xs">
                <div class="flex items-center justify-between">
                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Terbuka</span>
                    <span class="text-gray-500">{{ $jam }}</span>
                </div>
                <h3 class="mt-3 font-bold text-gray-900">{{ $nama }}</h3>
                <p class="mt-3 text-gray-600"><b>{{ $tempat }}</b> - {{ $alamat }}</p>
                <p class="mt-2 text-gray-600">Tanggal: <b>{{ $tgl }}</b></p>
                <div class="mt-3 flex items-center justify-between border-t pt-3">
                    <span class="text-gray-500">Kuota Tersisa: <b class="text-red-600">{{ $sisa }}</b> / {{ $kuota }}</span>
                    <b class="text-gray-800">{{ $daftar }} Terdaftar</b>
                </div>
            </article>
        @endforeach
    </div>

    <section class="rounded-xl border border-gray-200 bg-white overflow-x-auto">
        <div class="flex justify-between p-4 text-xs"><h2 class="font-bold">Pencatatan Kehadiran Peserta Donor (Absensi Hari H)</h2><span class="text-gray-400">FR-14 Check Attendance</span></div>
        <table class="w-full text-xs">
            <thead class="text-left text-[10px] uppercase tracking-wide text-gray-500 border-y bg-gray-50">
                <tr><th class="p-3">Nama Kegiatan</th><th class="p-3">Nama Pendonor</th><th class="p-3">Gol. Darah</th><th class="p-3">Tanggal Daftar</th><th class="p-3">Status Kehadiran</th><th class="p-3 text-right">Aksi Verifikasi</th></tr>
            </thead>
            <tbody>
                <tr class="border-b" id="absen">
                    <td class="p-3 font-bold">{{ $events[0][0] }}</td>
                    <td class="p-3 font-bold">Muhammad Subhan</td>
                    <td class="p-3 font-bold text-red-600">AB (+)</td>
                    <td class="p-3 text-gray-500">2026-10-05</td>
                    <td class="p-3"><span id="status" class="rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-700">Terdaftar</span></td>
                    <td class="p-3 text-right">
                        <button onclick="tandai('Hadir','bg-green-100 text-green-700')" class="rounded border border-green-300 bg-green-50 px-3 py-1.5 font-semibold text-green-700">Tandai Hadir</button>
                        <button onclick="tandai('Tidak Hadir','bg-gray-200 text-gray-700')" class="rounded border bg-gray-100 px-3 py-1.5 font-semibold text-gray-600">Tidak Hadir</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
</div>

<section id="pendonor" class="hidden rounded-xl border border-gray-200 bg-white overflow-x-auto">
    <table class="w-full text-xs">
        <thead class="text-left text-[10px] uppercase tracking-wide text-gray-500 border-b bg-gray-50">
            <tr><th class="p-3">Nama</th><th class="p-3">Email</th><th class="p-3">Instansi</th><th class="p-3">Terdaftar Sejak</th></tr>
        </thead>
        <tbody>
        @forelse ($pendonor as $p)
            <tr class="border-b last:border-0"><td class="p-3 font-semibold">{{ $p->name }}</td><td class="p-3">{{ $p->email }}</td><td class="p-3">{{ $p->instansi }}</td><td class="p-3 text-gray-500">{{ $p->created_at->format('Y-m-d') }}</td></tr>
        @empty
            <tr><td colspan="4" class="p-6 text-center text-gray-500">Belum ada pendonor terdaftar.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

<script>
    document.querySelectorAll('.tab').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => {
            const on = t === b;
            t.classList.toggle('border-red-600', on); t.classList.toggle('text-red-600', on);
            t.classList.toggle('border-transparent', !on); t.classList.toggle('text-gray-500', !on);
        });
        document.getElementById('jadwal').classList.toggle('hidden', b.dataset.tab !== 'jadwal');
        document.getElementById('pendonor').classList.toggle('hidden', b.dataset.tab !== 'pendonor');
    }));
    function tandai(teks, kelas) {
        const s = document.getElementById('status');
        s.textContent = teks;
        s.className = 'rounded-full px-2 py-0.5 font-semibold ' + kelas;
    }
</script>
@endsection
