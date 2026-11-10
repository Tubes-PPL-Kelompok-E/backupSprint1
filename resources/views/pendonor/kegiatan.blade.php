@extends('layouts.app')
@section('content')
<section class="mb-4 rounded-xl border border-gray-200 bg-white p-5">
    <h1 class="text-xl font-bold text-gray-900">Jadwal Kegiatan Donor</h1>
    <p class="mt-2 text-xs text-gray-600">
        @if ($layakDari && $layakDari->isFuture())
            Anda dapat mendonor kembali mulai <b>{{ $layakDari->translatedFormat('d F Y') }}</b>. Pendaftaran hanya untuk kegiatan pada atau setelah tanggal tersebut.
        @else
            Pilih kegiatan lalu klik Daftar. Jeda minimal antar donor {{ config('bloodconnect.interval_donor_hari') }} hari.
        @endif
    </p>
</section>

<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
@forelse ($kegiatan as $k)
    @php
        $sudah = in_array($k->id, $terdaftar);
        $sisa = max(0, $k->kuota - $k->pendaftaran_count);
        $belumLayak = $layakDari && $k->tanggal->lt($layakDari);
    @endphp
    <article class="rounded-xl border border-gray-200 bg-white p-4 text-xs">
        <div class="flex items-center justify-between">
            <span class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Terbuka</span>
            <span class="text-gray-500">{{ $k->jam() }}</span>
        </div>
        <h3 class="mt-3 font-bold text-gray-900">{{ $k->judul }}</h3>
        <p class="mt-2 text-gray-600"><b>{{ $k->nama_tempat }}</b> - {{ $k->alamat }}</p>
        <p class="mt-2 text-gray-600">Tanggal: <b>{{ $k->tanggal->translatedFormat('l, d F Y') }}</b></p>
        <div class="mt-3 flex items-center justify-between border-t pt-3">
            <span class="text-gray-500">Kuota tersisa: <b class="{{ $sisa === 0 ? 'text-gray-500' : 'text-red-600' }}">{{ $sisa }}</b> / {{ $k->kuota }}</span>
            @if ($sudah)
                <span class="flex items-center gap-2">
                    <span class="font-semibold text-green-700">✓ Terdaftar</span>
                    <form method="POST" action="{{ route('pendonor.kegiatan.batal', $k) }}" onsubmit="return confirm('Batalkan pendaftaran pada kegiatan ini?')">@csrf @method('DELETE')<button class="rounded border border-gray-300 px-2 py-1 text-[11px] text-gray-600 hover:bg-gray-50">Batalkan</button></form>
                </span>
            @elseif ($sisa === 0)
                <span class="rounded bg-gray-100 px-3 py-1.5 font-semibold text-gray-500">Kuota penuh</span>
            @elseif ($belumLayak)
                <span class="rounded bg-amber-50 px-3 py-1.5 font-semibold text-amber-700">Belum layak</span>
            @else
                <form method="POST" action="{{ route('pendonor.kegiatan.daftar', $k) }}" onsubmit="this.querySelector('button').disabled = true">@csrf<button class="rounded bg-red-600 px-3 py-1.5 font-semibold text-white hover:bg-red-700">Daftar</button></form>
            @endif
        </div>
    </article>
@empty
    <p class="md:col-span-3 rounded-xl border border-gray-200 bg-white p-8 text-center text-xs text-gray-500">Belum ada kegiatan donor yang terbuka.</p>
@endforelse
</div>
@endsection
