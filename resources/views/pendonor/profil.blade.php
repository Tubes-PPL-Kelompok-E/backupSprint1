@extends('layouts.app')
@section('content')
@php $inp = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs'; @endphp
<section class="mb-4 rounded-xl border border-gray-200 bg-white p-5 text-xs">
    <h1 class="text-xl font-bold text-gray-900">Profil Saya</h1>
    <dl class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div><dt class="text-gray-400">Nama</dt><dd class="font-semibold">{{ $pendonor->nama }}</dd></div>
        <div><dt class="text-gray-400">Kode donor</dt><dd class="font-semibold">{{ $pendonor->kode_donor }}</dd></div>
        <div><dt class="text-gray-400">Golongan darah</dt><dd class="font-semibold text-red-600">{{ $pendonor->golonganLengkap() }}</dd></div>
        <div><dt class="text-gray-400">Tanggal lahir</dt><dd class="font-semibold">{{ $pendonor->tanggal_lahir->translatedFormat('d F Y') }}</dd></div>
    </dl>
    <p class="mt-4"><span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $layak ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $layak ? 'Layak mendonor' : 'Layak kembali mulai ' . $layakDari->translatedFormat('d F Y') }}</span>
        @if ($pemeriksaan)<span class="ml-2 text-gray-500">Pemeriksaan terakhir {{ $pemeriksaan->tanggal->translatedFormat('d F Y') }}: <b>{{ $pemeriksaan->layak() ? 'Layak' : 'Tidak layak' }}</b></span>@endif</p>
    <p class="mt-2 text-[11px] text-gray-400">Nama, tanggal lahir, dan golongan darah hanya dapat diubah oleh Petugas UTD.</p>
</section>

<section class="mb-4 rounded-xl border border-gray-200 bg-white p-5 text-xs">
    <h2 class="mb-3 text-sm font-bold">Data Kontak</h2>
    @if ($errors->any())<div class="mb-3 rounded-lg border border-red-200 bg-red-50 p-3 text-red-700">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('pendonor.profil.update') }}" class="grid gap-3 sm:grid-cols-3">
        @csrf @method('PUT')
        <div><label class="mb-1 block font-semibold">Nomor telepon</label><input name="no_hp" value="{{ old('no_hp', $pendonor->no_hp) }}" class="{{ $inp }}"></div>
        <div><label class="mb-1 block font-semibold">Kota</label><input name="kota" value="{{ old('kota', $pendonor->kota) }}" class="{{ $inp }}"></div>
        <div><label class="mb-1 block font-semibold">Alamat</label><input name="alamat" value="{{ old('alamat', $pendonor->alamat) }}" class="{{ $inp }}"></div>
        <div class="sm:col-span-3"><button class="rounded-lg bg-red-600 px-4 py-2 font-semibold text-white hover:bg-red-700">Simpan Perubahan</button></div>
    </form>
</section>

<section class="rounded-xl border border-gray-200 bg-white p-5 text-xs">
    <h2 class="mb-2 text-sm font-bold">Riwayat Donasi</h2>
    <ul class="divide-y">
        @forelse ($riwayat as $d)<li class="py-2">{{ $d->tanggal_donor->translatedFormat('d F Y') }} • {{ $d->volume_ml }} ml • {{ $d->kegiatan?->judul ?? $d->lokasi ?? '-' }}</li>
        @empty<li class="py-2 text-gray-500">Belum ada riwayat donasi.</li>@endforelse
    </ul>
</section>
@endsection
