@extends('layouts.app')
@section('content')
@php
    $layak = $pendonor->layakPada(); $dari = $pendonor->tanggalLayak();
    $inp = 'rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs';
@endphp
<section class="rounded-xl border border-gray-200 bg-white p-5 mb-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">{{ $pendonor->nama }} <span class="text-sm font-normal text-gray-400">{{ $pendonor->kode_donor }}</span></h1>
            <p class="mt-2 text-xs text-gray-600">Gol. <b class="text-red-600">{{ $pendonor->golonganLengkap() }}</b> • {{ $pendonor->kota }} • {{ $pendonor->no_hp ?? 'No. HP belum diisi' }} • Lahir {{ $pendonor->tanggal_lahir->format('d M Y') }}</p>
            <p class="mt-1 text-xs text-gray-600">Akun: {{ $pendonor->user ? $pendonor->user->email : 'belum dihubungkan' }}</p>
            <p class="mt-2"><span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $layak ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $layak ? 'Layak mendonor' : 'Layak kembali mulai ' . $dari->translatedFormat('d F Y') }}</span></p>
        </div>
        <div class="flex gap-2 text-xs font-semibold">
            <a href="{{ route('admin.pendonor.edit', $pendonor) }}" class="rounded border px-3 py-2 text-gray-700">Ubah</a>
            <a href="{{ route('admin.pendonor') }}" class="rounded border px-3 py-2 text-gray-700">&larr; Daftar</a>
        </div>
    </div>
</section>

@if ($errors->any())<div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700">{{ $errors->first() }}</div>@endif
@if (session('warning'))<div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-xs text-amber-800">{{ session('warning') }}</div>@endif

<section class="mb-4 rounded-xl border border-gray-200 bg-white p-5">
    <h2 class="text-sm font-bold">Pemeriksaan Pra-Donor</h2>
    <form method="POST" action="{{ route('admin.pendonor.pemeriksaan', $pendonor) }}" class="mt-3 flex flex-wrap items-end gap-3">
        @csrf
        <div><label class="mb-1 block text-[11px] font-semibold">Hb (g/dL)</label><input type="number" step="0.1" name="hemoglobin" required class="{{ $inp }} w-20"></div>
        <div><label class="mb-1 block text-[11px] font-semibold">Sistolik</label><input type="number" name="sistolik" required class="{{ $inp }} w-20"></div>
        <div><label class="mb-1 block text-[11px] font-semibold">Diastolik</label><input type="number" name="diastolik" required class="{{ $inp }} w-20"></div>
        <div><label class="mb-1 block text-[11px] font-semibold">Berat (kg)</label><input type="number" step="0.1" name="berat_kg" required class="{{ $inp }} w-20"></div>
        <div><label class="mb-1 block text-[11px] font-semibold">Suhu (°C)</label><input type="number" step="0.1" name="suhu_tubuh" required class="{{ $inp }} w-20"></div>
        <div><label class="mb-1 block text-[11px] font-semibold">Nadi</label><input type="number" name="nadi" required class="{{ $inp }} w-20"></div>
        <div><label class="mb-1 block text-[11px] font-semibold">Catatan</label><input name="catatan" class="{{ $inp }}"></div>
        <button class="rounded-lg bg-gray-800 px-4 py-2 text-xs font-semibold text-white">Periksa &amp; Tentukan Kelayakan</button>
    </form>
    <ul class="mt-4 divide-y text-xs">
        @forelse ($pemeriksaan as $x)
            <li class="py-2"><span class="text-gray-400">{{ $x->tanggal->format('d M Y') }}</span> • Hb {{ $x->hemoglobin }} • {{ $x->sistolik }}/{{ $x->diastolik }} • {{ $x->berat_kg }} kg • {{ $x->suhu_tubuh }} °C • nadi {{ $x->nadi }}
                <span class="ml-1 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $x->layak() ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $x->layak() ? 'Layak' : 'Tidak layak' }}</span>
                @foreach ($x->daftarAlasan() as $a)<p class="mt-1 text-red-600">• {{ $a }}</p>@endforeach</li>
        @empty
            <li class="py-2 text-gray-500">Belum ada pemeriksaan pra-donor.</li>
        @endforelse
    </ul>
</section>

<section class="mb-4 rounded-xl border border-gray-200 bg-white p-5">
    <h2 class="text-sm font-bold">Catat Donasi</h2>
    <form method="POST" action="{{ route('admin.pendonor.donasi', $pendonor) }}" class="mt-3 flex flex-wrap items-end gap-3">
        @csrf
        <div><label class="mb-1 block text-[11px] font-semibold">Tanggal donor</label><input type="date" name="tanggal_donor" max="{{ today()->toDateString() }}" required class="{{ $inp }}"></div>
        <div><label class="mb-1 block text-[11px] font-semibold">Volume (ml)</label><input type="number" name="volume_ml" value="350" min="100" max="500" required class="{{ $inp }} w-24"></div>
        <div><label class="mb-1 block text-[11px] font-semibold">Lokasi</label><input name="lokasi" value="UTD PMI Kota" class="{{ $inp }}"></div>
        <button class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">Simpan Donasi</button>
    </form>
</section>

<section class="rounded-xl border border-gray-200 bg-white overflow-x-auto">
    <div class="p-4 text-xs font-bold">Riwayat Donasi</div>
    <table class="w-full text-xs">
        <thead class="text-left text-[10px] uppercase tracking-wide text-gray-500 border-y bg-gray-50"><tr><th class="p-3">Tanggal</th><th class="p-3">Volume</th><th class="p-3">Lokasi / Kegiatan</th><th class="p-3 text-right">Aksi</th></tr></thead>
        <tbody>
        @forelse ($pendonor->donasi as $d)
            <tr class="border-b last:border-0"><td class="p-3">{{ $d->tanggal_donor->format('Y-m-d') }}</td><td class="p-3">{{ $d->volume_ml }} ml</td><td class="p-3 text-gray-600">{{ $d->kegiatan?->judul ?? $d->lokasi ?? '-' }}</td>
                <td class="p-3 text-right align-top">
                    <details class="inline-block text-left">
                        <summary class="cursor-pointer text-blue-600 underline">Ubah</summary>
                        <form method="POST" action="{{ route('admin.pendonor.donasi.update', [$pendonor, $d]) }}" class="mt-2 flex flex-wrap items-end gap-2">
                            @csrf @method('PUT')
                            <input type="date" name="tanggal_donor" value="{{ $d->tanggal_donor->toDateString() }}" max="{{ today()->toDateString() }}" required class="{{ $inp }}">
                            <input type="number" name="volume_ml" value="{{ $d->volume_ml }}" min="100" max="500" required class="{{ $inp }} w-20">
                            <input name="lokasi" value="{{ $d->lokasi }}" class="{{ $inp }} w-32">
                            <button class="rounded bg-red-600 px-3 py-2 text-[11px] font-semibold text-white">Simpan</button>
                        </form>
                    </details>
                    <form method="POST" action="{{ route('admin.pendonor.donasi.destroy', [$pendonor, $d]) }}" class="ml-2 inline" onsubmit="return confirm('Hapus donasi tanggal {{ $d->tanggal_donor->format('d-m-Y') }}?')">@csrf @method('DELETE')<button class="text-red-600 underline">Hapus</button></form>
                </td></tr>
        @empty
            <tr><td colspan="4" class="p-6 text-center text-gray-500">Belum ada riwayat donasi.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endsection
