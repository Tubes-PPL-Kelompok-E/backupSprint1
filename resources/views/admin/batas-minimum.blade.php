@extends('layouts.app')
@section('content')
@php
    $komponen = ['prc' => 'PRC', 'wb' => 'WB', 'tc' => 'TC', 'ffp' => 'FFP'];
    $inp = 'w-16 rounded border border-gray-200 bg-white px-2 py-1 text-center text-xs focus:outline-none focus:ring-2 focus:ring-red-500';
@endphp
<section class="rounded-xl border border-gray-200 bg-white p-5 mb-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Batas Minimum Stok</h1>
            <p class="mt-2 text-xs text-gray-500">Golongan darah dianggap <b>kritis</b> bila jumlah kantong siap pakai di bawah jumlah batas minimum semua komponennya. Nilai bawaan: PRC {{ $default['prc'] }}, WB {{ $default['wb'] }}, TC {{ $default['tc'] }}, FFP {{ $default['ffp'] }} (total {{ array_sum($default) }} kantong per golongan+rhesus).</p>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-xs font-semibold text-gray-700">&larr; Dashboard</a>
    </div>
</section>

<form method="POST" action="{{ route('admin.batas-minimum.update') }}" class="rounded-xl border border-gray-200 bg-white overflow-x-auto">
    @csrf @method('PUT')
    <table class="w-full text-xs">
        <thead class="text-left text-[10px] uppercase tracking-wide text-gray-500 border-b bg-gray-50">
            <tr><th class="p-3">Golongan</th>@foreach ($komponen as $l)<th class="p-3">{{ $l }}</th>@endforeach<th class="p-3">Total</th></tr>
        </thead>
        <tbody>
        @foreach (\App\Models\BatasMinimumStok::GOLONGAN as $g)
            @foreach (\App\Models\BatasMinimumStok::RHESUS as $r => $simbol)
                <tr class="border-b">
                    <td class="p-3 font-semibold">Gol. {{ $g }} ({{ $simbol }})</td>
                    @php $total = 0; @endphp
                    @foreach ($komponen as $k => $l)
                        @php $nilai = old("batas.$g.$r.$k", $peta["$g|$r|$k"]); $total += (int) $nilai; @endphp
                        <td class="p-3"><input type="number" min="0" max="9999" name="batas[{{ $g }}][{{ $r }}][{{ $k }}]" value="{{ $nilai }}" class="{{ $inp }}" aria-label="Batas {{ $l }} golongan {{ $g }} {{ $simbol }}"></td>
                    @endforeach
                    <td class="p-3 font-bold text-gray-700">{{ $total }}</td>
                </tr>
            @endforeach
        @endforeach
        </tbody>
    </table>
    <div class="flex flex-wrap items-center justify-between gap-2 p-4 border-t">
        <p class="text-[11px] text-gray-500">{{ $custom }} dari 32 kombinasi memakai nilai khusus. Perubahan dicatat pada log transaksi.</p>
        <div class="flex gap-2">
            <button class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">Simpan Perubahan</button>
        </div>
    </div>
</form>

@if ($custom)
<form method="POST" action="{{ route('admin.batas-minimum.reset') }}" class="mt-3" onsubmit="return confirm('Kembalikan semua batas minimum ke nilai bawaan?')">
    @csrf @method('DELETE')
    <button class="text-xs font-semibold text-gray-600 underline">Kembalikan ke nilai bawaan</button>
</form>
@endif
@endsection
