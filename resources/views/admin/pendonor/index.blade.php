@extends('layouts.app')
@section('content')
<section class="rounded-xl border border-gray-200 bg-white p-5 mb-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Data Pendonor</h1>
            <p class="mt-2 text-xs text-gray-500">Data pendonor, riwayat donasi, dan status kelayakan (jeda minimal {{ config('bloodconnect.interval_donor_hari') }} hari).</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.pendonor.create') }}" class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">+ Tambah Pendonor</a>
        </div>
    </div>
</section>

<form method="GET" class="mb-4 flex flex-wrap gap-2 text-xs">
    <input name="q" value="{{ request('q') }}" placeholder="Cari nama, kode, atau kota" class="w-64 rounded-lg border border-gray-200 bg-white px-3 py-2">
    <select name="golongan" class="rounded-lg border border-gray-200 bg-white px-3 py-2"><option value="">Semua golongan</option>@foreach (['A','B','AB','O'] as $g)<option value="{{ $g }}" @selected(request('golongan') === $g)>{{ $g }}</option>@endforeach</select>
    <select name="rhesus" class="rounded-lg border border-gray-200 bg-white px-3 py-2"><option value="">Semua rhesus</option><option value="pos" @selected(request('rhesus') === 'pos')>Positif (+)</option><option value="neg" @selected(request('rhesus') === 'neg')>Negatif (-)</option></select>
    <button class="rounded-lg bg-gray-800 px-4 py-2 font-semibold text-white">Cari</button>
</form>

<section class="rounded-xl border border-gray-200 bg-white overflow-x-auto">
    <table class="w-full text-xs">
        <thead class="text-left text-[10px] uppercase tracking-wide text-gray-500 border-b bg-gray-50">
            <tr><th class="p-3">Kode</th><th class="p-3">Nama</th><th class="p-3">Gol.</th><th class="p-3">Kota</th><th class="p-3">Donor Terakhir</th><th class="p-3">Kelayakan</th><th class="p-3"></th></tr>
        </thead>
        <tbody>
        @forelse ($pendonor as $p)
            @php $layak = $p->layakPada(); $dari = $p->tanggalLayak(); @endphp
            <tr class="border-b last:border-0">
                <td class="p-3 text-gray-500">{{ $p->kode_donor }}</td>
                <td class="p-3 font-semibold">{{ $p->nama }}</td>
                <td class="p-3 font-bold text-red-600">{{ $p->golonganLengkap() }}</td>
                <td class="p-3">{{ $p->kota }}</td>
                <td class="p-3 text-gray-500">{{ $p->tanggalDonorTerakhir()?->format('Y-m-d') ?? '-' }}</td>
                <td class="p-3"><span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $layak ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $layak ? 'Layak' : 'Layak mulai ' . $dari->format('d M Y') }}</span></td>
                <td class="p-3 text-right"><a href="{{ route('admin.pendonor.show', $p) }}" class="font-semibold text-red-600">Detail</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="p-6 text-center text-gray-500">Tidak ada pendonor yang sesuai.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
<div class="mt-4">{{ $pendonor->links() }}</div>
@endsection
