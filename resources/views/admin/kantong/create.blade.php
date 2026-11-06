@extends('layouts.app')
@section('content')
@php
    $in = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-500';
    $radio = 'block rounded-lg border border-gray-200 bg-white py-3 text-center text-xs font-semibold text-gray-600 peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-600';
@endphp

<div class="flex flex-wrap items-start justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Input Kantong Darah Masuk</h1>
        <p class="text-xs text-blue-600 mt-1">Catat kantong hasil donasi. Kantong baru berstatus Karantina sampai lolos uji saring.</p>
    </div>
    <a href="{{ route('admin.stok') }}" class="rounded-lg border border-gray-200 bg-gray-100 px-4 py-2 text-xs font-semibold text-gray-700">Kembali ke Stok Darah</a>
</div>

<form method="POST" action="{{ route('admin.kantong.store') }}" class="grid gap-4 lg:grid-cols-3">
    @csrf
    <div class="space-y-4 lg:col-span-2">
        <section class="rounded-xl border border-gray-200 bg-white p-5 space-y-4">
            <h2 class="text-[11px] font-bold tracking-wide text-gray-700">1. IDENTITAS KANTONG</h2>

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs p-3">
                    <ul class="list-disc pl-4 space-y-0.5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <div>
                <label class="block text-xs font-semibold mb-1" for="kode_batch">Kode batch</label>
                <input id="kode_batch" name="kode_batch" value="{{ old('kode_batch') }}" class="{{ $in }} uppercase" autocomplete="off">
                <p class="mt-1 text-[11px] text-gray-400">Harus unik. Terisi otomatis dari tanggal donor dan golongan, boleh diubah.</p>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" for="tanggal_donor">Tanggal donor</label>
                <input id="tanggal_donor" type="date" name="tanggal_donor" max="{{ now()->toDateString() }}" value="{{ old('tanggal_donor', now()->toDateString()) }}" class="{{ $in }}">
                <p class="mt-1 text-[11px] text-gray-400">Tidak boleh tanggal di masa depan.</p>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" for="volume_ml">Volume (ml)</label>
                <input id="volume_ml" type="number" name="volume_ml" min="50" max="1000" value="{{ old('volume_ml', 350) }}" class="{{ $in }}">
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" for="komponen">Komponen</label>
                <select id="komponen" name="komponen" class="{{ $in }}">
                    @foreach (\App\Models\KantongDarah::KOMPONEN as $v => $l)
                        <option value="{{ $v }}" @selected(old('komponen', 'prc') === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5">
            <h2 class="text-[11px] font-bold tracking-wide text-gray-700 mb-4">2. GOLONGAN DARAH DAN RHESUS</h2>
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
            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <a href="{{ route('admin.stok') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700">Batal</a>
                <button name="tambah_lagi" value="1" class="rounded-lg bg-gray-100 px-4 py-2 text-xs font-semibold text-gray-700">Simpan dan Tambah Lagi</button>
                <button class="rounded-lg bg-red-600 px-5 py-2 text-xs font-semibold text-white hover:bg-red-700">&#10003; Simpan Kantong</button>
            </div>
        </section>
    </div>

    <aside class="space-y-4">
        <section class="rounded-xl border border-gray-200 bg-white p-5 text-xs">
            <h2 class="font-bold mb-3">Dihitung otomatis</h2>
            <dl class="space-y-2">
                <div class="flex justify-between"><dt class="text-gray-500">Masa simpan</dt><dd id="o-masa" class="font-bold">-</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Tgl kedaluwarsa</dt><dd id="o-exp" class="font-bold">-</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Sisa masa simpan</dt><dd id="o-sisa" class="font-bold">-</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Status awal</dt><dd class="rounded bg-gray-100 px-2 py-0.5 font-semibold">Karantina</dd></div>
            </dl>
            <p class="mt-3 rounded-lg border border-blue-200 bg-blue-50 p-3 text-[11px] text-blue-700">Kantong tidak masuk stok siap pakai dan tidak ikut alokasi FEFO sampai hasil uji saring non-reaktif dicatat.</p>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 text-xs">
            <h2 class="font-bold mb-3">Pemeriksaan data</h2>
            <ul class="space-y-2">
                @foreach (['c1' => 'Kode batch belum terpakai', 'c2' => 'Tanggal donor bukan di masa depan', 'c3' => 'Semua kolom wajib terisi'] as $id => $t)
                    <li class="flex items-center gap-2"><span id="{{ $id }}" class="rounded bg-gray-200 px-1.5 py-0.5 text-[10px] font-bold text-gray-500">...</span>{{ $t }}</li>
                @endforeach
            </ul>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 text-xs">
            <h2 class="font-bold mb-3">Masa simpan per komponen</h2>
            <dl class="space-y-1.5">
                @foreach (\App\Models\KantongDarah::MASA_SIMPAN as $k => $hari)
                    <div class="flex justify-between"><dt class="text-gray-500">{{ strtoupper($k) }}</dt><dd class="font-bold">{{ $hari }} hari</dd></div>
                @endforeach
            </dl>
        </section>
    </aside>
</form>

<script>
    const $ = id => document.getElementById(id);
    const MASA = @json(\App\Models\KantongDarah::MASA_SIMPAN);
    const URL_CEK = @json(route('admin.kantong.cek'));
    let kodeManual = {{ old('kode_batch') ? 'true' : 'false' }};

    const gol = () => (document.querySelector('input[name=golongan]:checked') || {}).value;
    const tandai = (id, ok) => {
        const e = $(id);
        e.textContent = ok ? 'OK' : 'X';
        e.className = 'rounded px-1.5 py-0.5 text-[10px] font-bold ' + (ok ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700');
    };
    const fmt = d => d.toLocaleDateString('id-ID', {day: '2-digit', month: 'long', year: 'numeric'});

    function hitung() {
        const tgl = $('tanggal_donor').value, masa = MASA[$('komponen').value];
        const d = tgl ? new Date(tgl + 'T00:00:00') : null;
        const hariIni = new Date(); hariIni.setHours(0, 0, 0, 0);
        const valid = d && !isNaN(d) && d <= hariIni;

        tandai('c2', valid);
        tandai('c3', !!($('kode_batch').value && tgl && $('volume_ml').value && gol() && document.querySelector('input[name=rhesus]:checked')));

        if (valid) {
            const exp = new Date(d); exp.setDate(exp.getDate() + masa);
            $('o-masa').textContent = masa + ' hari (' + $('komponen').value.toUpperCase() + ')';
            $('o-exp').textContent = fmt(exp);
            $('o-sisa').textContent = Math.round((exp - hariIni) / 86400000) + ' hari';
        } else {
            ['o-masa', 'o-exp', 'o-sisa'].forEach(i => $(i).textContent = '-');
        }
    }

    async function cekKode() {
        const q = new URLSearchParams({kode: $('kode_batch').value, tanggal: $('tanggal_donor').value, golongan: gol() || ''});
        try {
            const r = await (await fetch(URL_CEK + '?' + q)).json();
            if (!kodeManual && r.saran) { $('kode_batch').value = r.saran; return cekKode(); }
            tandai('c1', r.tersedia);
        } catch (e) { tandai('c1', false); }
        hitung();
    }

    $('kode_batch').addEventListener('input', () => { kodeManual = $('kode_batch').value !== ''; cekKode(); });
    ['tanggal_donor', 'volume_ml', 'komponen'].forEach(i => $(i).addEventListener('input', () => { hitung(); cekKode(); }));
    document.querySelectorAll('input[type=radio]').forEach(r => r.addEventListener('change', () => { hitung(); cekKode(); }));
    hitung(); cekKode();
</script>
@endsection
