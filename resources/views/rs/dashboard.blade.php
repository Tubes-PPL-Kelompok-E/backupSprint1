@extends('layouts.app')
@section('content')
@php
    $statKatalog = ['Tersedia' => 'bg-green-100 text-green-700', 'Terbatas' => 'bg-amber-100 text-amber-700', 'Kosong' => 'bg-red-100 text-red-700'];
@endphp
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Dashboard Layanan Bank Darah RS</h1>
    <p class="mt-1 text-xs text-gray-500">{{ $rs?->nama ?? 'Rumah sakit belum terhubung' }} • Ketersediaan stok darah UTD PMI • diperbarui otomatis tiap {{ config('bloodconnect.polling_detik') }} detik</p>
</div>

@unless ($rs)
    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">Akun Anda belum cocok dengan data rumah sakit (nama instansi pada akun harus sama dengan nama rumah sakit di Data Instansi). Hubungi Admin PMI.</div>
@endunless

@include('partials.dashboard-stok', ['bisaKelola' => false])

<section class="mt-6 rounded-xl border border-gray-200 bg-white p-5">
    <h2 class="text-xs font-bold text-gray-800">KATALOG STOK UTD PMI <span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] text-green-700">Live</span></h2>
    <div class="mt-3 grid gap-2 md:grid-cols-2 lg:grid-cols-4">
        @foreach ($katalog as $k)
            <div class="flex items-center justify-between rounded-lg border p-3 text-xs">
                <div><p class="font-bold">Golongan {{ $k['golongan'] }}</p><p class="text-gray-500">PRC: {{ $k['prc'] }} | WB: {{ $k['wb'] }} • Total siap: {{ $k['total'] }}</p></div>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statKatalog[$k['status']] }}">{{ $k['status'] }}</span>
            </div>
        @endforeach
    </div>
    <p class="mt-3 text-[11px] text-gray-400">Tampilan baca-saja.</p>
</section>
@endsection
