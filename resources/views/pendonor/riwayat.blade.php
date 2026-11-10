@extends('layouts.app')
@section('content')
<section class="mb-4 rounded-xl border border-gray-200 bg-white p-5">
    <h1 class="text-xl font-bold text-gray-900">Riwayat Donor</h1>
    <p class="mt-2 text-xs text-gray-600">Status: <b>{{ $layak ? 'Layak mendonor' : 'Layak kembali mulai ' . $layakDari->translatedFormat('d F Y') }}</b> (jeda minimal {{ config('bloodconnect.interval_donor_hari') }} hari).</p>
</section>
<section class="rounded-xl border border-gray-200 bg-white overflow-x-auto">
    <table class="w-full text-xs">
        <thead class="text-left text-[10px] uppercase tracking-wide text-gray-500 border-b bg-gray-50"><tr><th class="p-3">Tanggal</th><th class="p-3">Volume</th><th class="p-3">Lokasi / Kegiatan</th></tr></thead>
        <tbody>
        @forelse ($riwayat as $d)
            <tr class="border-b last:border-0"><td class="p-3">{{ $d->tanggal_donor->translatedFormat('d F Y') }}</td><td class="p-3">{{ $d->volume_ml }} ml</td><td class="p-3 text-gray-600">{{ $d->kegiatan?->judul ?? $d->lokasi ?? '-' }}</td></tr>
        @empty
            <tr><td colspan="3" class="p-6 text-center text-gray-500">Belum ada riwayat donasi.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
<div class="mt-4">{{ $riwayat->links() }}</div>
@endsection
