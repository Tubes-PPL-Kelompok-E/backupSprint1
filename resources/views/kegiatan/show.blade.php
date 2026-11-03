@extends('layouts.app')

@section('content')
    @include('kegiatan._style')

    <div class="kd-wrap">
        <div class="kd-card">
            @if (session('success')) <div class="kd-alert kd-alert-success">{{ session('success') }}</div> @endif
            @if (session('error'))   <div class="kd-alert kd-alert-error">{{ session('error') }}</div>   @endif

            <div class="kd-event-top" style="margin-bottom:12px">
                <h1 class="kd-title">{{ $kegiatan->nama }}</h1>
                <span class="kd-badge {{ $kegiatan->status === 'Terbuka' ? 'kd-badge-terbuka' : 'kd-badge-ditutup' }}">
                    {{ $kegiatan->status }}
                </span>
            </div>

            <dl class="kd-detail">
                <dt>Tanggal</dt><dd>{{ $kegiatan->tanggal->format('d F Y') }}</dd>
                <dt>Jam</dt>
                <dd>
                    @if ($kegiatan->jam_mulai)
                        {{ substr($kegiatan->jam_mulai, 0, 5) }}@if ($kegiatan->jam_selesai) - {{ substr($kegiatan->jam_selesai, 0, 5) }}@endif WIB
                    @else - @endif
                </dd>
                <dt>Lokasi</dt><dd>{{ $kegiatan->lokasi }}</dd>
                <dt>Kuota</dt><dd>{{ $kegiatan->kuota }} (tersisa {{ $kegiatan->sisaKuota() }}, terdaftar {{ $kegiatan->jumlahPendaftar() }})</dd>
                <dt>Deskripsi</dt><dd>{{ $kegiatan->deskripsi ?: '-' }}</dd>
            </dl>

            <div class="kd-actions">
                {{-- tombol Ubah dan Tutup ditambahkan di DARAH-89 --}}
                <a href="{{ route('kegiatan-donor.index') }}" class="kd-btn kd-btn-outline">Kembali</a>
            </div>
        </div>
    </div>
@endsection