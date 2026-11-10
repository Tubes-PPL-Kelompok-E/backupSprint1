@extends('layouts.app')
@section('content')
<section class="rounded-xl border border-gray-200 bg-white p-5 mb-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Kegiatan Donor Darah</h1>
            <p class="mt-2 text-xs text-gray-500">Jadwal donor, kuota, dan pendaftar. Pendonor mendaftar sendiri dari portal pendonor.</p>
        </div>
        <a href="{{ route('admin.kegiatan.create') }}" class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">+ Buat Kegiatan Donor</a>
    </div>
</section>

<nav class="mb-4 flex flex-wrap gap-2 text-xs font-semibold">
    <a href="{{ route('admin.kegiatan') }}" class="rounded-full border px-3 py-1.5 {{ ! $status ? 'border-red-600 bg-red-600 text-white' : 'border-gray-200 bg-white text-gray-600' }}">Semua</a>
    @foreach (\App\Models\KegiatanDonor::STATUS as $v => $l)
        <a href="{{ route('admin.kegiatan', ['status' => $v]) }}" class="rounded-full border px-3 py-1.5 {{ $status === $v ? 'border-red-600 bg-red-600 text-white' : 'border-gray-200 bg-white text-gray-600' }}">{{ $l }}</a>
    @endforeach
</nav>

<div class="grid gap-4 md:grid-cols-3">
@forelse ($kegiatan as $k)
    @php $tone = ['terbuka' => 'bg-green-100 text-green-700', 'ditutup' => 'bg-gray-200 text-gray-700', 'dibatalkan' => 'bg-red-100 text-red-700'][$k->status]; @endphp
    <a href="{{ route('admin.kegiatan.show', $k) }}" class="block rounded-xl border border-gray-200 bg-white p-4 text-xs hover:border-red-300">
        <div class="flex items-center justify-between">
            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $tone }}">{{ \App\Models\KegiatanDonor::STATUS[$k->status] }}</span>
            <span class="text-gray-500">{{ $k->jam() }}</span>
        </div>
        <h3 class="mt-3 font-bold text-gray-900">{{ $k->judul }}</h3>
        <p class="mt-2 text-gray-600"><b>{{ $k->nama_tempat }}</b> - {{ $k->alamat }}</p>
        <p class="mt-2 text-gray-600">Tanggal: <b>{{ $k->tanggal->format('Y-m-d') }}</b></p>
        <div class="mt-3 flex items-center justify-between border-t pt-3">
            <span class="text-gray-500">Kuota tersisa: <b class="text-red-600">{{ max(0, $k->kuota - $k->pendaftaran_count) }}</b> / {{ $k->kuota }}</span>
            <b class="text-gray-800">{{ $k->pendaftaran_count }} Terdaftar</b>
        </div>
    </a>
@empty
    <p class="md:col-span-3 rounded-xl border border-gray-200 bg-white p-8 text-center text-xs text-gray-500">Belum ada kegiatan donor.</p>
@endforelse
</div>
<div class="mt-4">{{ $kegiatan->links() }}</div>
@endsection
