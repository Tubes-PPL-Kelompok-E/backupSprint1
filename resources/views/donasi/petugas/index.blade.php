@extends('donasi.layout')
@section('title', 'Pencatatan Donasi')

@section('content')
<div class="bar">
    <form method="GET" action="{{ route('petugas.donasi.index') }}" class="search">
        <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama pendonor / kode kantong">
        <button type="submit">Cari</button>
    </form>
    <a class="btn" href="{{ route('petugas.donasi.create') }}">+ Catat Donasi</a>
</div>

<div class="card">
@if ($donations->isEmpty())
    <div class="empty">Belum ada donasi tercatat.</div>
@else
    <table>
        <thead>
            <tr>
                <th>Tanggal</th><th>Pendonor</th><th>Kantong</th><th>Gol.</th>
                <th>Volume</th><th>Status</th><th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($donations as $d)
            <tr>
                <td data-label="Tanggal">{{ $d->donation_date->locale('id')->translatedFormat('d F Y') }}</td>
                <td data-label="Pendonor">{{ $d->donor?->name ?? '-' }}</td>
                <td data-label="Kantong">{{ $d->blood_bag_code }}</td>
                <td data-label="Gol.">{{ $d->blood_type ?? '-' }}</td>
                <td data-label="Volume">{{ $d->volume_ml }} ml</td>
                <td data-label="Status"><span class="badge {{ $d->status }}">{{ ucfirst($d->status) }}</span></td>
                <td data-label="Aksi">
                    @if ($d->isCancelled())
                        <small>Alasan: {{ $d->cancel_reason }}</small>
                    @else
                        <a href="{{ route('petugas.donasi.edit', $d) }}">Ubah</a>
                        <form method="POST" action="{{ route('petugas.donasi.destroy', $d) }}"
                              class="inline" onsubmit="return confirm('Batalkan donasi ini?')">
                            @csrf
                            @method('DELETE')
                            <input type="text" name="cancel_reason" placeholder="Alasan pembatalan" required>
                            <button type="submit" class="light">Batalkan</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
</div>

<div style="margin-top:12px">{{ $donations->links() }}</div>
@endsection