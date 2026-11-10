@extends('layouts.app')
@section('content')
<section class="mb-4 rounded-xl border border-gray-200 bg-white p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Halo, {{ $pendonor->nama }}</h1>
            <p class="mt-1 text-xs text-gray-500">{{ $pendonor->kode_donor }} • Golongan <b class="text-red-600">{{ $pendonor->golonganLengkap() }}</b> • {{ $pendonor->kota }} • {{ $totalDonor }}x donor</p>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $layak ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $layak ? '✓ Layak mendonor' : 'Layak kembali ' . $layakDari->translatedFormat('d F Y') }}</span>
    </div>
    @if ($terakhir)<p class="mt-2 text-xs text-gray-500">Donor terakhir: {{ $terakhir->translatedFormat('d F Y') }}</p>@endif
</section>

<div class="grid gap-4 lg:grid-cols-2">
    <section class="rounded-xl border border-gray-200 bg-white p-5 text-xs">
        <div class="flex items-center justify-between"><h2 class="font-bold">Kegiatan donor terdekat</h2><a href="{{ route('pendonor.kegiatan') }}" class="font-semibold text-red-600">Semua &rarr;</a></div>
        <div class="mt-3 space-y-2">
        @forelse ($kegiatan as $k)
            <div class="rounded-lg border p-3"><p class="font-bold">{{ $k->judul }}</p><p class="text-gray-500">{{ $k->tanggal->translatedFormat('d F Y') }} • {{ $k->nama_tempat }} • sisa kuota {{ max(0, $k->kuota - $k->pendaftaran_count) }}</p>@if (in_array($k->id, $terdaftar))<p class="mt-1 font-semibold text-green-700">✓ Anda terdaftar</p>@endif</div>
        @empty
            <p class="rounded-lg bg-gray-50 p-3 text-gray-500">Belum ada kegiatan terbuka.</p>
        @endforelse
        </div>
    </section>
    <section class="rounded-xl border border-gray-200 bg-white p-5 text-xs">
        <div class="flex items-center justify-between"><h2 class="font-bold">Riwayat donor terbaru</h2><a href="{{ route('pendonor.riwayat') }}" class="font-semibold text-red-600">Semua &rarr;</a></div>
        <div class="mt-3 space-y-2">
        @forelse ($riwayat as $d)
            <div class="rounded-lg border p-3">{{ $d->tanggal_donor->translatedFormat('d F Y') }} • {{ $d->volume_ml }} ml • {{ $d->kegiatan?->judul ?? $d->lokasi ?? '-' }}</div>
        @empty
            <p class="rounded-lg bg-gray-50 p-3 text-gray-500">Belum ada riwayat donasi.</p>
        @endforelse
        </div>
    </section>
</div>
@endsection
