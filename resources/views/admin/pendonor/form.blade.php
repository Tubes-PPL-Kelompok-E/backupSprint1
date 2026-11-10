@extends('layouts.app')
@section('content')
@php
    $inp = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-red-500';
    $ada = $pendonor->exists;
    $v = fn ($f, $d = null) => old($f, $ada ? $pendonor->{$f} : $d);
@endphp
<form method="POST" action="{{ $ada ? route('admin.pendonor.update', $pendonor) : route('admin.pendonor.store') }}" class="max-w-2xl rounded-xl border border-gray-200 bg-white p-5 space-y-4">
    @csrf @if ($ada) @method('PUT') @endif
    <h1 class="text-xl font-bold text-gray-900">{{ $title }}</h1>

    @if (session('duplikat'))
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
            {{ session('duplikat') }}
            <label class="mt-2 flex items-center gap-2"><input type="checkbox" name="konfirmasi_duplikat" value="1"> Ini orang yang berbeda, tetap simpan</label>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="mb-1 block text-xs font-semibold">Nama lengkap</label><input name="nama" value="{{ $v('nama') }}" required class="{{ $inp }}"></div>
        <div><label class="mb-1 block text-xs font-semibold">Tanggal lahir</label><input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $ada ? $pendonor->tanggal_lahir->toDateString() : '') }}" required class="{{ $inp }}"></div>
    </div>
    <div class="grid gap-4 sm:grid-cols-3">
        <div><label class="mb-1 block text-xs font-semibold">Jenis kelamin</label><select name="jenis_kelamin" class="{{ $inp }}"><option value="L" @selected($v('jenis_kelamin') === 'L')>Laki-laki</option><option value="P" @selected($v('jenis_kelamin') === 'P')>Perempuan</option></select></div>
        <div><label class="mb-1 block text-xs font-semibold">Golongan darah</label><select name="golongan" class="{{ $inp }}">@foreach (['A','B','AB','O'] as $g)<option value="{{ $g }}" @selected($v('golongan') === $g)>{{ $g }}</option>@endforeach</select></div>
        <div><label class="mb-1 block text-xs font-semibold">Rhesus</label><select name="rhesus" class="{{ $inp }}"><option value="pos" @selected($v('rhesus', 'pos') === 'pos')>Positif (+)</option><option value="neg" @selected($v('rhesus') === 'neg')>Negatif (-)</option></select></div>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="mb-1 block text-xs font-semibold">No. HP</label><input name="no_hp" value="{{ $v('no_hp') }}" class="{{ $inp }}"></div>
        <div><label class="mb-1 block text-xs font-semibold">Kota</label><input name="kota" value="{{ $v('kota') }}" required class="{{ $inp }}"></div>
    </div>
    <div><label class="mb-1 block text-xs font-semibold">Alamat</label><textarea name="alamat" rows="2" class="{{ $inp }}">{{ $v('alamat') }}</textarea></div>
    <div>
        <label class="mb-1 block text-xs font-semibold">Hubungkan dengan akun pendonor (opsional)</label>
        <select name="user_id" class="{{ $inp }}">
            <option value="">- Tidak dihubungkan -</option>
            @foreach ($akunBebas as $u)<option value="{{ $u->id }}" @selected((int) $v('user_id') === $u->id)>{{ $u->name }} ({{ $u->email }})</option>@endforeach
        </select>
        <p class="mt-1 text-[11px] text-gray-500">Akun yang terhubung dapat mendaftar kegiatan donor.</p>
    </div>
    <div class="flex gap-2">
        <button class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">Simpan</button>
        <a href="{{ $ada ? route('admin.pendonor.show', $pendonor) : route('admin.pendonor') }}" class="rounded-lg border px-4 py-2 text-xs font-semibold text-gray-700">Batal</a>
    </div>
</form>
@endsection
