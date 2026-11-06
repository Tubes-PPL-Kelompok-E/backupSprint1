@extends('layouts.app')
@section('content')
<section class="rounded-xl border border-gray-200 bg-white p-5 mb-4">
    <h1 class="text-xl font-bold text-gray-900">Laporan Pasokan &amp; Jejak Audit Transaksi</h1>
    <p class="mt-2 text-xs text-gray-500">FR-03, NFR-06, UC-18, &amp; UC-20: Rekapitulasi bulanan rantai pasok dan log transaksi append-only untuk auditabilitas.</p>
</section>

<div class="flex gap-6 border-b border-gray-200 mb-4 text-xs font-semibold">
    <button data-tab="rekap" class="tab pb-3 border-b-2 border-red-600 text-red-600">Rekap Bulanan Pasokan (FR-03)</button>
    <button data-tab="audit" class="tab pb-3 border-b-2 border-transparent text-gray-500">Jejak Audit Transaksi Darah (NFR-06)</button>
</div>

<section id="rekap" class="rounded-xl border border-gray-200 bg-white">
    <div class="flex flex-wrap items-center justify-between gap-2 p-4">
        <div class="flex items-center gap-2 text-xs">
            Periode:
            <select class="rounded border px-2 py-1.5">@foreach (['Oktober','November','Desember'] as $b)<option>{{ $b }}</option>@endforeach</select>
            <select class="rounded border px-2 py-1.5"><option>2026</option><option>2025</option></select>
        </div>
        <div class="flex gap-2 text-xs font-semibold">
            <button onclick="alert('Ekspor Excel belum tersedia.')" class="rounded border border-green-300 bg-green-50 px-3 py-2 text-green-700">Ekspor Excel (.xlsx)</button>
            <button onclick="ekspor()" class="rounded border px-3 py-2 text-gray-700">Ekspor CSV</button>
        </div>
    </div>
    <div class="overflow-x-auto">
    <table id="tblRekap" class="w-full text-xs">
        <thead class="text-left text-[10px] uppercase tracking-wide text-gray-500 border-y">
            <tr><th class="p-3">Golongan Darah</th><th class="p-3">Kantong Masuk</th><th class="p-3">Keluar (Terkirim RS)</th><th class="p-3">Kedaluwarsa</th><th class="p-3">Dimusnahkan</th><th class="p-3 text-right">Stok Siap (Ready)</th></tr>
        </thead>
        <tbody>
        @foreach ($rekap as [$gol, $masuk, $keluar, $exp, $musnah, $stok])
            <tr class="border-b">
                <td class="p-3 font-semibold">{{ $gol }}</td>
                <td class="p-3 font-semibold text-blue-600">+{{ $masuk }}</td>
                <td class="p-3 font-semibold text-green-600">-{{ $keluar }}</td>
                <td class="p-3 font-semibold text-amber-600">{{ $exp }}</td>
                <td class="p-3 font-semibold text-red-600">{{ $musnah }}</td>
                <td class="p-3 text-right font-bold">{{ $stok }}</td>
            </tr>
        @endforeach
            @php $t = fn ($i) => array_sum(array_column($rekap, $i)); @endphp
            <tr class="bg-gray-50 font-bold">
                <td class="p-3 uppercase">Total Keseluruhan</td>
                <td class="p-3 text-blue-600">+{{ $t(1) }}</td><td class="p-3 text-green-600">-{{ $t(2) }}</td>
                <td class="p-3 text-amber-600">{{ $t(3) }}</td><td class="p-3 text-red-600">{{ $t(4) }}</td>
                <td class="p-3 text-right">{{ $t(5) }}</td>
            </tr>
        </tbody>
    </table>
    </div>
</section>

<section id="audit" class="hidden rounded-xl border border-gray-200 bg-white overflow-x-auto">
    <table class="w-full text-xs">
        <thead class="text-left text-[10px] uppercase tracking-wide text-gray-500 border-b">
            <tr><th class="p-3">Waktu</th><th class="p-3">Pelaku</th><th class="p-3">Aksi</th><th class="p-3">Referensi</th></tr>
        </thead>
        <tbody>
        @foreach ($audit as [$waktu, $pelaku, $aksi, $ref])
            <tr class="border-b last:border-0"><td class="p-3 text-gray-500">{{ $waktu }}</td><td class="p-3">{{ $pelaku }}</td><td class="p-3 font-semibold">{{ $aksi }}</td><td class="p-3 text-gray-500">{{ $ref }}</td></tr>
        @endforeach
        </tbody>
    </table>
</section>

<script>
    document.querySelectorAll('.tab').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => {
            const on = t === b;
            t.classList.toggle('border-red-600', on); t.classList.toggle('text-red-600', on);
            t.classList.toggle('border-transparent', !on); t.classList.toggle('text-gray-500', !on);
        });
        document.getElementById('rekap').classList.toggle('hidden', b.dataset.tab !== 'rekap');
        document.getElementById('audit').classList.toggle('hidden', b.dataset.tab !== 'audit');
    }));
    function ekspor() {
        const csv = [...document.querySelectorAll('#tblRekap tr')]
            .map(tr => [...tr.children].map(c => '"' + c.textContent.trim().replace(/"/g, '""') + '"').join(',')).join('\n');
        const a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob([csv], {type: 'text/csv'}));
        a.download = 'rekap-pasokan.csv'; a.click();
    }
</script>
@endsection
