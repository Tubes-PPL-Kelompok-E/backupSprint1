@extends('layouts.app')

@section('content')
    @include('kegiatan._style')

    <div class="kd-wrap">
        <div class="kd-card">
            <h1 class="kd-title">Tambah Kegiatan Donor</h1>
            <p class="kd-sub">Isi data kegiatan agar pendonor dapat mendaftar.</p>

            <form action="{{ route('kegiatan-donor.store') }}" method="POST">
                @include('kegiatan._form')

                <div class="kd-info">Status awal kegiatan: <strong>Terbuka</strong></div>

                <div class="kd-actions">
                    <button type="submit" class="kd-btn">Simpan</button>
                    <a href="{{ route('kegiatan-donor.index') }}" class="kd-btn kd-btn-outline">Batal</a>
                </div>
            </form>
        </div>
    </div>
@endsection