@extends('layouts.app')
@section('content')
@php
    $cards = [
        ['TOTAL KANTONG READY', '1,245', 'Tersedia', 'border-blue-500 bg-blue-50 text-blue-700', 'bg-blue-100'],
        ['EXPIRED < 7 HARI (FEFO ALERT)', '18', 'Prioritas Keluar', 'border-red-500 bg-red-50 text-red-600', 'bg-red-100'],
        ['PERMINTAAN RS PENDING', (string) ($pending ?? 0), 'Perlu Verifikasi', 'border-amber-500 bg-amber-50 text-amber-700', 'bg-amber-100'],
    ];
    $max = max(array_column($stok, 1));
    $rendah = array_filter($stok, fn ($s) => $s[1] < $min);
@endphp

<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard Ketersediaan Stok Darah</h1>
        <p class="text-xs text-gray-500 mt-1">Unit Transfusi Darah (UTD) PMI Kota • Real-Time Monitoring &amp; FEFO Alert</p>
    </div>
    <div class="flex gap-2">
        <button onclick="location.reload()" class="rounded-lg border border-gray-200 bg-gray-100 px-4 py-2 text-xs font-semibold text-gray-700">&#10227; Refresh Data</button>
        <a href="{{ route('admin.kantong.create') }}" class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">+ Input Darah Masuk</a>
    </div>
</div>

<div class="grid gap-4 md:grid-cols-3 mb-6">
    @foreach ($cards as [$label, $angka, $badge, $tone, $badgeBg])
        <div class="rounded-xl border-l-4 p-5 shadow-sm {{ $tone }}">
            <p class="text-[11px] font-bold tracking-wide">{{ $label }}</p>
            <div class="mt-3 flex items-end justify-between">
                <span class="text-3xl font-bold">{{ $angka }}</span>
                <span class="rounded px-2 py-1 text-[11px] font-medium {{ $badgeBg }}">{{ $badge }}</span>
            </div>
        </div>
    @endforeach
</div>

<div class="grid gap-4 lg:grid-cols-3">
    <section class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex justify-between text-xs">
            <h2 class="font-bold text-gray-800">Ketersediaan Stok Per Golongan Darah</h2>
            <span class="text-gray-400">Diperbarui 5 menit lalu</span>
        </div>
        <div class="mt-4 flex h-52 items-end justify-around border-b border-gray-200">
            @foreach ($stok as [$gol, $jumlah])
                <div class="flex flex-col items-center gap-1">
                    <span class="text-xs font-semibold text-gray-700">{{ $jumlah }}</span>
                    <div class="w-14 rounded-t {{ $jumlah < $min ? 'bg-amber-500' : 'bg-red-600' }}" style="height: {{ round($jumlah / $max * 140) }}px"></div>
                    <span class="py-2 text-xs font-bold {{ $jumlah < $min ? 'text-amber-600' : 'text-gray-700' }}">Gol. {{ $gol }}{{ $jumlah < $min ? ' [!]' : '' }}</span>
                </div>
            @endforeach
        </div>
        @foreach ($rendah as [$gol, $jumlah])
            <div class="mt-4 flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                <span>&#9888; Stok Golongan <b>{{ $gol }}</b> dibawah batas minimum (&lt;{{ $min }} kantong).</span>
                <a href="{{ route('admin.kegiatan') }}" class="rounded bg-amber-500 px-3 py-1 font-semibold text-white">Panggil Pendonor (Matching)</a>
            </div>
        @endforeach
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="text-sm font-bold text-red-600">&#9888; FEFO Alert System</h2>
        <div class="mt-4 space-y-3">
            @foreach ($alerts as [$batch, $desc, $exp, $tgl, $tone])
                <div class="rounded-lg border p-3 {{ $tone === 'red' ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50' }}">
                    <div class="flex items-start justify-between">
                        <p class="text-xs font-bold text-gray-800">{{ $batch }}</p>
                        <span class="rounded px-2 py-0.5 text-[10px] font-bold text-white {{ $tone === 'red' ? 'bg-red-600' : 'bg-amber-500' }}">{{ $exp }}</span>
                    </div>
                    <p class="mt-2 text-xs text-gray-600">{{ $desc }}</p>
                    <p class="mt-1 text-[10px] {{ $tone === 'red' ? 'text-red-500' : 'text-amber-600' }}">{{ $tgl }}</p>
                </div>
            @endforeach
            <a href="{{ route('admin.stok') }}" class="block rounded-lg bg-red-50 border border-red-200 py-2 text-center text-xs font-semibold text-red-600">Lihat Selengkapnya (18 Alert)</a>
        </div>
    </section>
</div>
@endsection
