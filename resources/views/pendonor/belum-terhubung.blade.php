@extends('layouts.app')
@section('content')
<section class="mx-auto max-w-xl rounded-xl border border-amber-200 bg-amber-50 p-8 text-center">
    <h1 class="text-lg font-bold text-amber-900">Akun belum terhubung dengan data pendonor</h1>
    <p class="mt-2 text-xs text-amber-800">Hubungi UTD PMI agar petugas menghubungkan akun Anda ({{ auth()->user()->email }}) dengan data pendonor. Setelah itu Anda dapat mendaftar kegiatan donor.</p>
</section>
@endsection
