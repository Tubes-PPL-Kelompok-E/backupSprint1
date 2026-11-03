@extends('layouts.app')

@section('content')
    @include('kegiatan._style')

    <div class="kd-wrap kd-wide">
        <div class="kd-card kd-head-card">
            <div>
                <h1 class="kd-title">Kegiatan Donor Darah</h1>
                <p class="kd-sub" style="margin-bottom:0">Pengelolaan jadwal kegiatan donor darah.</p>
            </div>
            <a href="{{ route('kegiatan-donor.create') }}" class="kd-btn">+ Buat Kegiatan Donor</a>
        </div>

        @if (session('success')) <div class="kd-alert kd-alert-success">{{ session('success') }}</div> @endif
        @if (session('error'))   <div class="kd-alert kd-alert-error">{{ session('error') }}</div>   @endif

        <div class="kd-grid">
            @forelse ($kegiatan as $k)
                <div class="kd-event">
                    <div class="kd-event-top">
                        <span class="kd-badge {{ $k->status === 'Terbuka' ? 'kd-badge-terbuka' : 'kd-badge-ditutup' }}">
                            {{ $k->status }}
                        </span>
                        @if ($k->jam_mulai)
                            <span class="kd-time">
                                {{ substr($k->jam_mulai, 0, 5) }}@if ($k->jam_selesai) - {{ substr($k->jam_selesai, 0, 5) }}@endif WIB
                            </span>
                        @endif
                    </div>

                    <h3 class="kd-event-title">{{ $k->nama }}</h3>
                    <div class="kd-meta">Lokasi: {{ $k->lokasi }}</div>
                    <div class="kd-meta">Tanggal: <strong>{{ $k->tanggal->format('Y-m-d') }}</strong></div>

                    <div class="kd-event-foot">
                        <span>Kuota Tersisa: <strong class="kd-red">{{ $k->sisaKuota() }}</strong> / {{ $k->kuota }}</span>
                        <strong>{{ $k->jumlahPendaftar() }} Terdaftar</strong>
                    </div>

                    <div class="kd-event-actions">
                        <a href="{{ route('kegiatan-donor.show', $k) }}" class="kd-btn kd-btn-outline kd-btn-sm">Detail</a>

                        <a href="{{ route('kegiatan-donor.edit', $k) }}" class="kd-btn kd-btn-outline kd-btn-sm">Ubah</a>

                        @if ($k->status === 'Terbuka')
                            <form action="{{ route('kegiatan-donor.tutup', $k) }}" method="POST"
                                onsubmit="return confirm('Tutup kegiatan ini? Pendonor tidak bisa mendaftar lagi.')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="kd-btn kd-btn-outline kd-btn-sm">Tutup</button>
                            </form>
                            @else
                            <form action="{{ route('kegiatan-donor.buka', $k) }}" method="POST"
                                onsubmit="return confirm('Buka kembali kegiatan ini?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="kd-btn kd-btn-outline kd-btn-sm">Buka Kembali</button>
                            </form>
                        @endif

                         {{-- tombol Hapus ditambahkan di DARAH-90 --}}
                    </div>
                </div>
            @empty
                <div class="kd-card" style="grid-column: 1 / -1">Belum ada kegiatan donor.</div>
            @endforelse
        </div>

        <div style="margin-top:20px">{{ $kegiatan->links() }}</div>
    </div>
@endsection