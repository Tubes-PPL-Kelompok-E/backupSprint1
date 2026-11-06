<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'BloodConnect' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Inter',system-ui,sans-serif}
        summary::-webkit-details-marker{display:none}
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
@php
    $user = auth()->user();

    // Menu navigasi per peran (route yang belum ada diisi '#')
    $portal = match (true) {
        $user->hasRole('admin_pmi')  => 'Supply Chain System',
        $user->hasRole('admin_pmi')  => 'Admin Portal',
        $user->hasRole('petugas_rs') => 'Petugas RS Portal',
        default                      => 'Donor Portal',
    };

    $menus = match (true) {
        $user->hasRole('admin_pmi') => [
            ['Dashboard', route('admin.dashboard'), request()->routeIs('admin.dashboard')],
            ['Stok Darah', route('admin.stok'), request()->routeIs('admin.stok', 'admin.kantong.*', 'admin.uji-saring*')],
            ['Permintaan RS', route('admin.permintaan'), request()->routeIs('admin.permintaan*')],
            ['Laporan', route('admin.laporan'), request()->routeIs('admin.laporan')],
            ['Kegiatan Donor', route('admin.kegiatan'), request()->routeIs('admin.kegiatan')],
            ['Manajemen Akun', route('users.index'), request()->routeIs('users.*')],
            ['Kegiatan Donor', route('kegiatan-donor.index'), request()->routeIs('kegiatan-donor.*')],
        ],
        $user->hasRole('petugas_rs') => [
            ['Dashboard', route('petugas.dashboard'), request()->routeIs('petugas.dashboard')],
            ['Stok Darah', '#', false],
            ['Permintaan Darah', '#', false],
        ],
        default => [
            ['Dashboard', route('pendonor.dashboard'), request()->routeIs('pendonor.dashboard')],
            ['Jadwal Donor', '#', false],
            ['Riwayat Donor', '#', false],
            ['E-Kartu Donor', '#', false],
        ],
    };
@endphp

<header class="bg-red-700 text-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">

        {{-- Logo --}}
        <a href="/" class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-full bg-white text-red-700 flex items-center justify-center shrink-0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5c3.2 4.2 6 7.4 6 11a6 6 0 0 1-12 0c0-3.6 2.8-6.8 6-11z"/></svg>
            </span>
            <span class="leading-tight">
                <span class="block text-base font-bold">BloodConnect</span>
                <span class="block text-[11px] text-red-100">{{ $portal }}</span>
            </span>
        </a>

        {{-- Menu tengah (turun ke baris baru di layar kecil) --}}
        <nav class="order-last w-full md:order-none md:w-auto flex items-center gap-1 overflow-x-auto text-sm font-medium">
            @foreach ($menus as [$label, $url, $active])
                <a href="{{ $url }}"
                   class="whitespace-nowrap rounded-full px-4 py-2 {{ $active ? 'bg-white/15 border border-white/25 font-semibold' : 'hover:bg-white/10' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        {{-- Kanan: notifikasi + akun --}}
        <div class="flex items-center gap-3">
            <button type="button" class="p-2 rounded-full hover:bg-white/10" aria-label="Notifikasi">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/></svg>
            </button>

            <details class="relative">
                <summary class="list-none cursor-pointer flex items-center gap-2 rounded-xl bg-red-900/50 border border-white/20 px-3 py-2 text-sm hover:bg-red-900/70">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm0 2c-3.3 0-8 1.7-8 5v1h16v-1c0-3.3-4.7-5-8-5z"/></svg>
                    <span class="max-w-[140px] truncate">{{ $user->name }}</span>
                </summary>
                <div class="absolute right-0 mt-2 w-56 rounded-xl bg-white text-gray-800 shadow-xl border border-gray-100 overflow-hidden z-20">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <p class="text-sm font-semibold truncate">{{ $user->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $user->email }}</p>
                        <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $user->getRoleNames()->first() }}</p>
                    </div>
                    @role('admin_pmi')
                        <a href="{{ route('users.index') }}" class="block px-4 py-3 text-sm hover:bg-gray-50">Kelola Akun</a>
                        <a href="{{ route('instansi.index') }}" class="block px-4 py-3 text-sm hover:bg-gray-50">Data Instansi</a>
                    @endrole
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="w-full text-left px-4 py-3 text-sm text-red-600 font-semibold hover:bg-red-50">Keluar</button>
                    </form>
                </div>
            </details>
        </div>
    </div>
</header>

<main class="max-w-6xl mx-auto p-4 sm:p-6">
    @if (session('success'))
        <div class="mb-4 rounded bg-green-50 border border-green-200 text-green-700 text-sm p-3">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded bg-red-50 border border-red-200 text-red-700 text-sm p-3">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>