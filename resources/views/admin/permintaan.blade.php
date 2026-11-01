@extends('layouts.app')
@section('content')
@php
    $tone = [
        'menunggu' => 'bg-amber-100 text-amber-700', 'dalam_perjalanan' => 'bg-purple-100 text-purple-700',
        'diterima' => 'bg-green-100 text-green-700', 'ditolak' => 'bg-gray-200 text-gray-600',
    ];
    $inp = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-red-500';
    $totalSemua = $hitung->sum();
@endphp

<section class="rounded-xl border border-gray-200 bg-white p-5 mb-4 max-w-4xl">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Permintaan Darah Rumah Sakit &amp; Alokasi FEFO
                <span class="ml-2 align-middle rounded-full bg-red-50 border border-red-200 px-2 py-0.5 text-[10px] font-semibold text-red-600">Prioritas CITO Otomatis</span></h1>
            <p class="mt-2 text-xs text-gray-500">FR-15, FR-16, &amp; FR-17: Verifikasi permintaan RS, alokasi batch kedaluwarsa terdekat (FEFO), dan penugasan kurir cold chain.</p>
        </div>
        <a href="{{ route('admin.permintaan.create') }}" class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">+ Catat Permintaan</a>
    </div>
</section>

<nav class="mb-4 flex max-w-4xl flex-wrap gap-2 text-xs font-semibold">
    <a href="{{ route('admin.permintaan') }}" class="rounded-full border px-3 py-1.5 {{ ! $status ? 'border-red-600 bg-red-600 text-white' : 'border-gray-200 bg-white text-gray-600' }}">Semua ({{ $totalSemua }})</a>
    @foreach (\App\Models\PermintaanDarah::STATUS as $v => $l)
        <a href="{{ route('admin.permintaan', ['status' => $v]) }}" class="rounded-full border px-3 py-1.5 {{ $status === $v ? 'border-red-600 bg-red-600 text-white' : 'border-gray-200 bg-white text-gray-600' }}">{{ $l }} ({{ $hitung[$v] ?? 0 }})</a>
    @endforeach
</nav>

<div class="max-w-4xl space-y-4">
@forelse ($permintaan as $p)
    @php
        $cito = $p->prioritas === 'cito';
        $siap = $stokSiap["{$p->golongan}|{$p->rhesus}|{$p->komponen}"] ?? 0;
        $suhu = $p->suhu_cold_box;
        $suhuAman = $suhu !== null && $suhu >= 1 && $suhu <= 10;
    @endphp
    <article class="rounded-xl border bg-white p-5 {{ $cito ? 'border-red-300' : 'border-gray-200' }}">
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="rounded px-2 py-1 text-[10px] font-bold {{ $cito ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700' }}">{{ \App\Models\PermintaanDarah::PRIORITAS[$p->prioritas] }}</span>
            <span class="font-bold">Req #{{ $p->kode }}</span>
            <span class="text-gray-400">Diajukan: {{ $p->diajukan_pada->isToday() ? $p->diajukan_pada->format('H:i') : $p->diajukan_pada->format('d M H:i') }} WIB</span>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $tone[$p->status] }}">{{ \App\Models\PermintaanDarah::STATUS[$p->status] }}</span>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <div class="space-y-2 text-xs">
                <p class="font-bold text-gray-800">{{ $p->instansi->nama }} • Pasien: <span class="text-red-600">{{ $p->nama_pasien }}{{ $p->ruang ? ' (' . $p->ruang . ')' : '' }}</span>
                    <span class="ml-2 font-normal text-gray-400">(No. RM: {{ $p->no_rm }})</span></p>
                <div class="flex flex-wrap gap-2">
                    <span class="rounded border bg-gray-50 px-2 py-1">Dibutuhkan: {{ $p->kebutuhanTeks() }}</span>
                    <span class="rounded border border-red-200 bg-red-50 px-2 py-1 font-semibold text-red-600">Golongan {{ $p->golonganLengkap() }}</span>
                    @if ($p->status === 'menunggu')
                        <span class="rounded border px-2 py-1 {{ $siap >= $p->jumlah ? 'bg-gray-50 text-green-700' : 'border-red-200 bg-red-50 text-red-600' }}">Stok UTD Siap (Ready): {{ $siap }} Kantong{{ $siap < $p->jumlah ? ' (kurang)' : '' }}</span>
                    @endif
                </div>

                @if ($p->kantong->isNotEmpty())
                    <p class="rounded border bg-gray-50 px-3 py-2 text-gray-600">Batch Teralokasi FEFO: {{ $p->kantong->sortBy('tanggal_kedaluwarsa')->pluck('kode_batch')->implode(', ') }}</p>
                @endif
                @if ($p->kurir_nama)
                    <p class="text-gray-600">Kurir: <b>{{ $p->kurir_nama }} ({{ $p->kurir_kendaraan }})</b>
                        @if ($suhu !== null)<span class="ml-3 {{ $suhuAman ? 'text-green-700' : 'font-semibold text-red-600' }}">Suhu Cold Box: {{ number_format((float) $suhu, 1) }}°C{{ $suhuAman ? '' : ' (di luar 1-10°C!)' }}</span>@endif
                        @if ($p->posisi_terakhir)<span class="ml-3 text-gray-400">Posisi: {{ $p->posisi_terakhir }}</span>@endif</p>
                @endif
                @if ($p->status === 'ditolak')
                    <p class="rounded border border-gray-200 bg-gray-50 px-3 py-2 text-gray-600">Alasan penolakan: {{ $p->alasan_penolakan }}</p>
                @endif
            </div>

            <div class="flex flex-col items-end gap-2">
                @if ($p->status === 'menunggu')
                    <div class="flex gap-2">
                        <button type="button" data-panel="tolak-{{ $p->id }}" class="toggle rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700">Tolak</button>
                        <button type="button" data-panel="validasi-{{ $p->id }}" class="toggle rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">Validasi &amp; Alokasi FEFO</button>
                    </div>
                @elseif ($p->status === 'dalam_perjalanan')
                    <span class="rounded-lg border border-purple-300 bg-purple-50 px-3 py-2 text-xs font-semibold text-purple-700">Sedang Dalam Perjalanan</span>
                    <div class="flex gap-2 text-[11px] font-semibold">
                        <button type="button" data-panel="lacak-{{ $p->id }}" class="toggle text-blue-700 underline">Perbarui perjalanan</button>
                        <form method="POST" action="{{ route('admin.permintaan.selesai', $p) }}" onsubmit="return confirm('Tandai {{ $p->kode }} sudah diterima RS?')">
                            @csrf
                            <button class="text-green-700 underline">Tandai diterima</button>
                        </form>
                    </div>
                @elseif ($p->status === 'diterima')
                    <span class="rounded-lg border border-green-300 bg-green-50 px-3 py-2 text-xs font-semibold text-green-700">&#10003; Selesai Diterima RS</span>
                @else
                    <span class="rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-600">Ditolak</span>
                @endif
            </div>
        </div>

        {{-- Panel aksi (dibuka lewat tombol) --}}
        @if ($p->status === 'menunggu')
            <form id="validasi-{{ $p->id }}" method="POST" action="{{ route('admin.permintaan.validasi', $p) }}" class="panel mt-4 hidden rounded-lg border border-red-200 bg-red-50 p-4">
                @csrf
                <p class="mb-3 text-xs text-red-700">Sistem akan mengalokasikan <b>{{ $p->jumlah }} kantong</b> dengan tanggal kedaluwarsa terdekat. Tugaskan kurir cold chain:</p>
                <div class="grid gap-3 sm:grid-cols-3">
                    <input name="kurir_nama" placeholder="Nama kurir" required class="{{ $inp }}">
                    <input name="kurir_kendaraan" placeholder="No. kendaraan (B 4125 PMI)" required class="{{ $inp }}">
                    <button class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">&#10003; Validasi</button>
                </div>
            </form>
            <form id="tolak-{{ $p->id }}" method="POST" action="{{ route('admin.permintaan.tolak', $p) }}" class="panel mt-4 hidden rounded-lg border border-gray-200 bg-gray-50 p-4">
                @csrf
                <div class="grid gap-3 sm:grid-cols-3">
                    <input name="alasan_penolakan" placeholder="Alasan penolakan" required class="{{ $inp }} sm:col-span-2">
                    <button class="rounded-lg bg-gray-800 px-4 py-2 text-xs font-semibold text-white">Tolak Permintaan</button>
                </div>
            </form>
        @elseif ($p->status === 'dalam_perjalanan')
            <form id="lacak-{{ $p->id }}" method="POST" action="{{ route('admin.permintaan.lacak', $p) }}" class="panel mt-4 hidden rounded-lg border border-blue-200 bg-blue-50 p-4">
                @csrf @method('PATCH')
                <div class="grid gap-3 sm:grid-cols-4">
                    <input name="suhu_cold_box" type="number" step="0.1" placeholder="Suhu (°C)" value="{{ $p->suhu_cold_box }}" class="{{ $inp }}">
                    <input name="posisi_terakhir" placeholder="Posisi terakhir" value="{{ $p->posisi_terakhir }}" class="{{ $inp }} sm:col-span-2">
                    <button class="rounded-lg bg-blue-700 px-4 py-2 text-xs font-semibold text-white">Simpan</button>
                </div>
            </form>
        @endif
    </article>
@empty
    <div class="rounded-xl border border-gray-200 bg-white p-10 text-center text-sm text-gray-500">
        {{ $status ? 'Tidak ada permintaan dengan status ini.' : 'Belum ada permintaan darah dari rumah sakit.' }}
        <a href="{{ route('admin.permintaan.create') }}" class="font-semibold text-red-600 underline">Catat permintaan</a>
    </div>
@endforelse
</div>
<div class="mt-4 max-w-4xl">{{ $permintaan->links() }}</div>

<script>
    document.querySelectorAll('.toggle').forEach(b => b.addEventListener('click', () => {
        const target = document.getElementById(b.dataset.panel);
        const card = b.closest('article');
        card.querySelectorAll('.panel').forEach(p => { if (p !== target) p.classList.add('hidden'); });
        target.classList.toggle('hidden');
    }));
</script>
@endsection
