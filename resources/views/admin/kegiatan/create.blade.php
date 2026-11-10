@extends('layouts.app')
@section('content')
@php $edit = isset($kegiatan); $inp = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-red-500'; @endphp
<form method="POST" action="{{ $edit ? route('admin.kegiatan.update', $kegiatan) : route('admin.kegiatan.store') }}" class="max-w-2xl rounded-xl border border-gray-200 bg-white p-5 space-y-4">
    @csrf
    @if ($edit) @method('PUT') @endif
    <h1 class="text-xl font-bold text-gray-900">{{ $edit ? 'Ubah Kegiatan Donor' : 'Buat Kegiatan Donor' }}</h1>
    <div><label class="mb-1 block text-xs font-semibold">Judul kegiatan</label><input name="judul" value="{{ old('judul', $kegiatan->judul ?? '') }}" required class="{{ $inp }}"></div>
    <div class="grid gap-4 sm:grid-cols-3">
        <div><label class="mb-1 block text-xs font-semibold">Tanggal</label><input type="date" name="tanggal" value="{{ old('tanggal', isset($kegiatan) ? $kegiatan->tanggal->toDateString() : '') }}" min="{{ today()->toDateString() }}" required class="{{ $inp }}"></div>
        <div><label class="mb-1 block text-xs font-semibold">Jam mulai</label><input type="time" name="jam_mulai" value="{{ old('jam_mulai', isset($kegiatan) ? substr($kegiatan->jam_mulai, 0, 5) : '08:00') }}" required class="{{ $inp }}"></div>
        <div><label class="mb-1 block text-xs font-semibold">Jam selesai</label><input type="time" name="jam_selesai" value="{{ old('jam_selesai', isset($kegiatan) ? substr($kegiatan->jam_selesai, 0, 5) : '14:00') }}" required class="{{ $inp }}"></div>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="mb-1 block text-xs font-semibold">Nama tempat</label><input name="nama_tempat" value="{{ old('nama_tempat', $kegiatan->nama_tempat ?? '') }}" required class="{{ $inp }}"></div>
        <div><label class="mb-1 block text-xs font-semibold">Kuota peserta</label><input type="number" name="kuota" min="1" max="5000" value="{{ old('kuota', $kegiatan->kuota ?? 100) }}" required class="{{ $inp }}"></div>
    </div>
    <div><label class="mb-1 block text-xs font-semibold">Alamat</label><textarea name="alamat" rows="2" required class="{{ $inp }}">{{ old('alamat', $kegiatan->alamat ?? '') }}</textarea></div>
    <div class="flex gap-2">
        <button class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">{{ $edit ? 'Simpan Perubahan' : 'Simpan Kegiatan' }}</button>
        <a href="{{ $edit ? route('admin.kegiatan.show', $kegiatan) : route('admin.kegiatan') }}" class="rounded-lg border px-4 py-2 text-xs font-semibold text-gray-700">Batal</a>
    </div>
</form>
@endsection
