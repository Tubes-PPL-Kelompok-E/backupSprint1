@extends('layouts.app')
@section('content')
@php $inp = 'rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs'; @endphp
<section class="mb-4 rounded-xl border border-gray-200 bg-white p-5">
    <h1 class="text-xl font-bold text-gray-900">Lokasi Penyimpanan Kantong</h1>
    <p class="mt-2 text-xs text-gray-500">Atur cold room, freezer, dan area karantina beserta kapasitasnya. Lokasi aktif dapat dipilih saat hasil uji saring dicatat.</p>
</section>
@if ($errors->any())<div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700">{{ $errors->first() }}</div>@endif

<section class="mb-4 rounded-xl border border-gray-200 bg-white p-5">
    <h2 class="mb-3 text-sm font-bold">Tambah Lokasi</h2>
    <form method="POST" action="{{ route('admin.lokasi.store') }}" class="flex flex-wrap items-end gap-3 text-xs">
        @csrf
        <div><label class="mb-1 block font-semibold">Nama</label><input name="nama" required class="{{ $inp }}" placeholder="Cold Room 3"></div>
        <div><label class="mb-1 block font-semibold">Jenis</label><select name="jenis" class="{{ $inp }}">@foreach (\App\Models\LokasiPenyimpanan::JENIS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
        <div><label class="mb-1 block font-semibold">Kapasitas (kantong)</label><input type="number" name="kapasitas" min="1" required class="{{ $inp }} w-28"></div>
        <div><label class="mb-1 block font-semibold">Suhu min (°C)</label><input type="number" step="0.1" name="suhu_min" class="{{ $inp }} w-24"></div>
        <div><label class="mb-1 block font-semibold">Suhu maks (°C)</label><input type="number" step="0.1" name="suhu_maks" class="{{ $inp }} w-24"></div>
        <button class="rounded-lg bg-red-600 px-4 py-2 font-semibold text-white hover:bg-red-700">Simpan</button>
    </form>
</section>

<section class="rounded-xl border border-gray-200 bg-white overflow-x-auto">
    <table class="w-full text-xs">
        <thead class="text-left text-[10px] uppercase tracking-wide text-gray-500 border-b bg-gray-50"><tr><th class="p-3">Lokasi</th><th class="p-3">Jenis</th><th class="p-3">Terisi</th><th class="p-3">Suhu</th><th class="p-3">Status</th><th class="p-3"></th></tr></thead>
        <tbody>
        @forelse ($lokasi as $l)
            @php $terisi = $l->terisi(); $pct = min(100, round($terisi / max(1, $l->kapasitas) * 100)); @endphp
            <tr class="border-b align-top last:border-0">
                <td class="p-3 font-semibold">{{ $l->nama }}</td>
                <td class="p-3">{{ \App\Models\LokasiPenyimpanan::JENIS[$l->jenis] ?? $l->jenis }}</td>
                <td class="p-3"><div class="w-32"><div class="h-2 rounded bg-gray-100"><div class="h-2 rounded {{ $pct >= 90 ? 'bg-red-500' : 'bg-green-500' }}" style="width: {{ $pct }}%"></div></div><span class="text-[10px] text-gray-500">{{ $terisi }} / {{ $l->kapasitas }} ({{ $pct }}%)</span></div></td>
                <td class="p-3">{{ $l->suhu_min !== null ? $l->suhu_min . '–' . $l->suhu_maks . ' °C' : '-' }}</td>
                <td class="p-3"><span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $l->aktif ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">{{ $l->aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                <td class="p-3">
                    <details><summary class="cursor-pointer font-semibold text-blue-600">Ubah</summary>
                        <form method="POST" action="{{ route('admin.lokasi.update', $l) }}" class="mt-2 flex flex-wrap items-end gap-2">
                            @csrf @method('PUT')
                            <input name="nama" value="{{ $l->nama }}" class="{{ $inp }}">
                            <select name="jenis" class="{{ $inp }}">@foreach (\App\Models\LokasiPenyimpanan::JENIS as $k => $jl)<option value="{{ $k }}" @selected($l->jenis === $k)>{{ $jl }}</option>@endforeach</select>
                            <input type="number" name="kapasitas" value="{{ $l->kapasitas }}" min="1" class="{{ $inp }} w-24">
                            <input type="number" step="0.1" name="suhu_min" value="{{ $l->suhu_min }}" class="{{ $inp }} w-20" placeholder="min">
                            <input type="number" step="0.1" name="suhu_maks" value="{{ $l->suhu_maks }}" class="{{ $inp }} w-20" placeholder="maks">
                            <label class="flex items-center gap-1"><input type="checkbox" name="aktif" value="1" @checked($l->aktif)> Aktif</label>
                            <button class="rounded bg-gray-800 px-3 py-2 font-semibold text-white">Simpan</button>
                        </form>
                    </details>
                    <form method="POST" action="{{ route('admin.lokasi.destroy', $l) }}" class="mt-1" onsubmit="return confirm('Hapus lokasi ini?')">@csrf @method('DELETE')<button class="font-semibold text-red-600">Hapus</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="p-6 text-center text-gray-500">Belum ada lokasi penyimpanan.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endsection
