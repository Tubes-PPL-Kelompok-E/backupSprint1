@extends('layouts.app')
@section('content')
@php
    $statusStyle = [
        'karantina' => 'bg-amber-100 text-amber-700', 'ready' => 'bg-green-100 text-green-700',
        'reserved' => 'bg-blue-100 text-blue-700', 'ditahan' => 'bg-red-100 text-red-700',
        'dimusnahkan' => 'bg-gray-200 text-gray-600',
    ];
    $sel = 'rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs';
@endphp

<section class="rounded-xl border border-gray-200 bg-white p-5 mb-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Manajemen Stok Darah &amp; Urutan FEFO
                <span class="ml-2 align-middle rounded-full bg-red-50 border border-red-200 px-2 py-0.5 text-[10px] font-semibold text-red-600">First Expired, First Out</span></h1>
            <p class="mt-2 text-xs text-gray-500">FR-06 &amp; FR-07: Pemantauan masa simpan, urutan alokasi otomatis, dan penanganan kedaluwarsa.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.uji-saring') }}" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700">Uji Saring Lab</a>
            <a href="{{ route('admin.kantong.create') }}" class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">+ Input Darah Masuk</a>
        </div>
    </div>
</section>

<form method="GET" class="rounded-xl border border-gray-200 bg-white p-4 mb-4 flex flex-wrap gap-2">
    <input name="q" value="{{ request('q') }}" placeholder="Cari Batch / Lokasi..." class="flex-1 min-w-[200px] rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-red-500">
    <select name="golongan" class="{{ $sel }}" onchange="this.form.submit()">
        <option value="">Semua Golongan</option>
        @foreach (['A', 'B', 'AB', 'O'] as $g)<option value="{{ $g }}" @selected(request('golongan') === $g)>{{ $g }}</option>@endforeach
    </select>
    <select name="komponen" class="{{ $sel }}" onchange="this.form.submit()">
        <option value="">Semua Komponen</option>
        @foreach (array_keys(\App\Models\KantongDarah::KOMPONEN) as $k)<option value="{{ $k }}" @selected(request('komponen') === $k)>{{ strtoupper($k) }}</option>@endforeach
    </select>
    <select name="status" class="{{ $sel }}" onchange="this.form.submit()">
        <option value="">Semua Status</option>
        @foreach (\App\Models\KantongDarah::STATUS as $v => $l)<option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>@endforeach
    </select>
    <button class="rounded-lg bg-gray-800 px-4 py-2 text-xs font-semibold text-white">Cari</button>
</form>

<section class="rounded-xl border border-gray-200 bg-white overflow-x-auto">
    <table class="w-full text-xs">
        <thead class="text-left text-[10px] uppercase tracking-wide text-gray-500 border-b">
            <tr><th class="p-4">Urutan FEFO</th><th class="p-4">Nomor Batch</th><th class="p-4">Golongan / Rhesus</th><th class="p-4">Komponen &amp; Volume</th>
                <th class="p-4">Tanggal Kedaluwarsa</th><th class="p-4">Sisa Masa Simpan</th><th class="p-4">Lokasi Simpan</th><th class="p-4">Status</th></tr>
        </thead>
        <tbody>
        @forelse ($kantong as $i => $k)
            @php $sisa = $k->sisaHari(); $musnah = $k->status === 'dimusnahkan'; @endphp
            <tr class="border-b last:border-0">
                <td class="p-4 text-gray-400">#{{ $kantong->firstItem() + $i }}</td>
                <td class="p-4 font-bold text-gray-800">{{ $k->kode_batch }}</td>
                <td class="p-4"><span class="rounded border border-red-200 bg-red-50 px-2 py-1 font-semibold text-red-600">{{ $k->golonganLengkap() }}</span></td>
                <td class="p-4"><p class="font-semibold">{{ strtoupper($k->komponen) }}</p><p class="text-gray-400">{{ $k->volume_ml }} ml</p></td>
                <td class="p-4">{{ $k->tanggal_kedaluwarsa->format('d-m-Y') }}</td>
                <td class="p-4">
                    @if ($musnah) <span class="text-gray-400">-</span>
                    @elseif ($sisa < 0) <span class="rounded bg-red-600 px-2 py-1 font-bold text-white">KADALUWARSA</span>
                    @elseif ($sisa <= 7) <span class="rounded border border-red-300 bg-red-50 px-2 py-1 font-bold text-red-600">{{ $sisa }} Hari Lagi</span>
                    @else <span class="rounded bg-gray-100 px-2 py-1 font-semibold text-gray-700">{{ $sisa }} Hari</span>
                    @endif
                </td>
                <td class="p-4 text-gray-500">{{ $k->lokasi_simpan ?: '-' }}</td>
                <td class="p-4">
                    @if ($sisa < 0 && ! $musnah)
                        <form method="POST" action="{{ route('admin.kantong.musnahkan', $k) }}" onsubmit="return confirm('Catat pemusnahan {{ $k->kode_batch }}?')">
                            @csrf @method('PATCH')
                            <button class="rounded border border-red-300 bg-red-50 px-3 py-2 text-[10px] font-semibold text-red-600">Catat Pemusnahan</button>
                        </form>
                    @else
                        <span class="rounded-full px-2 py-1 font-semibold {{ $statusStyle[$k->status] }}">{{ \App\Models\KantongDarah::STATUS[$k->status] }}</span>
                        @if ($k->berkas_berita_acara)
                            <a href="{{ route('admin.uji-saring.berkas', $k) }}" target="_blank" class="ml-1 text-[10px] text-blue-600 underline">BA</a>
                        @endif
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="p-10 text-center text-gray-500">
                {{ request()->hasAny(['q', 'golongan', 'komponen', 'status']) ? 'Tidak ada kantong yang cocok dengan filter.' : 'Belum ada kantong darah. Catat kantong pertama lewat Input Darah Masuk.' }}
            </td></tr>
        @endforelse
        </tbody>
    </table>
</section>
<div class="mt-4">{{ $kantong->links() }}</div>
@endsection
