@extends('layouts.app')

@section('content')
    @include('kegiatan._style')

    <div class="kd-wrap">
        <div class="kd-card">
            <h1 class="kd-title">Ubah Kegiatan Donor</h1>
            <p class="kd-sub">Perbarui data kegiatan.</p>

            <form action="{{ route('kegiatan-donor.update', $kegiatan) }}" method="POST">
                @method('PUT')
                @include('kegiatan._form')

                <div class="kd-info">Status saat ini: <strong>{{ $kegiatan->status }}</strong></div>

                <div class="kd-actions">
                    <button type="submit" class="kd-btn">Perbarui</button>
                    <a href="{{ route('kegiatan-donor.show', $kegiatan) }}" class="kd-btn kd-btn-outline">Batal</a>
                </div>
            </form>
        </div>
    </div>
@endsection