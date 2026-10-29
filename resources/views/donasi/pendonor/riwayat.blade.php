@extends('donasi.layout')
@section('title', 'Riwayat Donasi Saya')

@section('content')
<p>Halo, {{ $user->name }}.</p>

@if ($eligibility['eligible'])
    <div class="status ok">
        <strong>Anda layak mendonor</strong>
        @if ($eligibility['last_date'])
            Donasi terakhir Anda pada {{ $eligibility['last_date']->locale('id')->translatedFormat('d F Y') }}.
        @else
            Anda belum pernah tercatat mendonor.
        @endif
    </div>
@else
    <div class="status wait">
        <strong>Belum layak mendonor</strong>
        Anda dapat mendonor lagi pada {{ $eligibility['next_date']->locale('id')->translatedFormat('d F Y') }}
        ({{ $eligibility['days_left'] }} hari lagi).
    </div>
@endif

<div class="card">
@if ($donations->isEmpty())
    <div class="empty">Belum ada riwayat donasi.</div>
@else
    <table>
        <thead>
            <tr><th>Tanggal</th><th>Kantong</th><th>Gol.</th><th>Volume</th><th>Status</th></tr>
        </thead>
        <tbody>
        @foreach ($donations as $d)
            <tr>
                <td data-label="Tanggal">{{ $d->donation_date->locale('id')->translatedFormat('d F Y') }}</td>
                <td data-label="Kantong">{{ $d->blood_bag_code }}</td>
                <td data-label="Gol.">{{ $d->blood_type ?? '-' }}</td>
                <td data-label="Volume">{{ $d->volume_ml }} ml</td>
                <td data-label="Status"><span class="badge {{ $d->status }}">{{ ucfirst($d->status) }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
</div>

<div style="margin-top:12px">{{ $donations->links() }}</div>
@endsection