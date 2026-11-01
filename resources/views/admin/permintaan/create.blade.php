@extends('layouts.app')
@section('content')
@php
    $in = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-500';
    $radio = 'block rounded-lg border border-gray-200 bg-white py-3 text-center text-xs font-semibold text-gray-600 peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-600';
@endphp

<div class="flex flex-wrap items-start justify-between gap-3 mb-5 max-w-3xl">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Catat Permintaan RS</h1>
        <p class="text-xs text-gray-500 mt-1">Catat permintaan darah dari rumah sakit. Permintaan baru berstatus Menunggu Persetujuan.</p>
    </div>
    <a href="{{ route('admin.permintaan') }}" class="rounded-lg border border-gray-200 bg-gray-100 px-4 py-2 text-xs font-semibold text-gray-700">Kembali ke Permintaan RS</a>
</div>

@if ($rumahSakit->isEmpty())
    <div class="max-w-3xl rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
        Belum ada instansi bertipe Rumah Sakit. <a href="{{ route('instansi.create') }}" class="font-semibold underline">Tambahkan rumah sakit</a> terlebih dahulu.
    </div>
@else
<form method="POST" action="{{ route('admin.permintaan.store') }}" class="max-w-3xl space-y-4">
    @csrf

    @if ($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs p-3">
            <ul class="list-disc pl-4 space-y-0.5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="rounded-xl border border-gray-200 bg-white p-5 space-y-4">
        <h2 class="text-[11px] font-bold tracking-wide text-gray-700">1. RUMAH SAKIT DAN PASIEN</h2>
        <div>
            <label class="block text-xs font-semibold mb-1">Rumah sakit peminta</label>
            <select name="instansi_id" class="{{ $in }}">
                <option value="">Pilih rumah sakit...</option>
                @foreach ($rumahSakit as $rs)
                    <option value="{{ $rs->id }}" @selected(old('instansi_id') == $rs->id)>{{ $rs->nama }} ({{ $rs->kode }})</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-semibold mb-1">Nama pasien</label>
                <input name="nama_pasien" value="{{ old('nama_pasien') }}" class="{{ $in }}">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Ruang / bed <span class="font-normal text-gray-400">(opsional)</span></label>
                <input name="ruang" value="{{ old('ruang') }}" placeholder="ICU Bed 04" class="{{ $in }}">
            </div>
        </div>
        <div>
            <label class="block text-xs font-semibold mb-1">Nomor rekam medis</label>
            <input name="no_rm" value="{{ old('no_rm') }}" placeholder="RM-000000" class="{{ $in }}">
        </div>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 space-y-4">
        <h2 class="text-[11px] font-bold tracking-wide text-gray-700">2. KEBUTUHAN DARAH</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <p class="text-xs font-semibold mb-2">Golongan</p>
                <div class="grid grid-cols-4 gap-2">
                    @foreach (['A', 'B', 'AB', 'O'] as $g)
                        <label class="cursor-pointer"><input type="radio" name="golongan" value="{{ $g }}" class="peer sr-only" @checked(old('golongan', 'A') === $g)><span class="{{ $radio }}">{{ $g }}</span></label>
                    @endforeach
                </div>
            </div>
            <div>
                <p class="text-xs font-semibold mb-2">Rhesus</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach (['pos' => 'Positif (+)', 'neg' => 'Negatif (-)'] as $v => $l)
                        <label class="cursor-pointer"><input type="radio" name="rhesus" value="{{ $v }}" class="peer sr-only" @checked(old('rhesus', 'pos') === $v)><span class="{{ $radio }}">{{ $l }}</span></label>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-semibold mb-1">Komponen</label>
                <select name="komponen" class="{{ $in }}">
                    @foreach (\App\Models\KantongDarah::KOMPONEN as $v => $l)
                        <option value="{{ $v }}" @selected(old('komponen', 'prc') === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1">Jumlah kantong</label>
                <input type="number" name="jumlah" min="1" max="20" value="{{ old('jumlah', 1) }}" class="{{ $in }}">
            </div>
        </div>
        <div>
            <p class="text-xs font-semibold mb-2">Prioritas</p>
            <div class="grid grid-cols-2 gap-2">
                @foreach (\App\Models\PermintaanDarah::PRIORITAS as $v => $l)
                    <label class="cursor-pointer"><input type="radio" name="prioritas" value="{{ $v }}" class="peer sr-only" @checked(old('prioritas', 'rutin') === $v)><span class="{{ $radio }}">{{ $l }}</span></label>
                @endforeach
            </div>
        </div>
    </section>

    <div class="flex justify-end gap-2">
        <a href="{{ route('admin.permintaan') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700">Batal</a>
        <button class="rounded-lg bg-red-600 px-5 py-2 text-xs font-semibold text-white hover:bg-red-700">&#10003; Simpan Permintaan</button>
    </div>
</form>
@endif
@endsection
