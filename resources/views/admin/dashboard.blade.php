@extends('layouts.app')
@section('content')
<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard Ketersediaan Stok Darah</h1>
        <p class="text-xs text-gray-500 mt-1">Unit Transfusi Darah (UTD) PMI Kota • Real-Time Monitoring &amp; FEFO Alert • diperbarui otomatis tiap {{ config('bloodconnect.polling_detik') }} detik</p>
    </div>
    <div class="flex gap-2">
        <button id="btn-refresh" type="button" class="rounded-lg border border-gray-200 bg-gray-100 px-4 py-2 text-xs font-semibold text-gray-700">&#10227; Refresh Data</button>
        <a href="{{ route('admin.batas-minimum') }}" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-700">Batas Minimum</a>
        <a href="{{ route('admin.kantong.create') }}" class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">+ Input Darah Masuk</a>
    </div>
</div>

@include('partials.dashboard-stok', ['bisaKelola' => true])
@endsection
