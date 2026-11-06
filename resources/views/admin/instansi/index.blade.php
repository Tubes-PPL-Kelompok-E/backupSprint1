@extends('layouts.app')
@section('content')
<div class="flex flex-wrap items-start justify-between gap-3 mb-4">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Data Instansi</h1>
        <p class="text-xs text-gray-500 mt-1">Kelola PMI dan rumah sakit mitra beserta kode uniknya.</p>
    </div>
    <a href="{{ route('instansi.create') }}" class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">+ Tambah Instansi</a>
</div>

<form method="GET" class="mb-4 flex flex-wrap gap-2 rounded-xl border border-gray-200 bg-white p-4">
    <input name="q" value="{{ request('q') }}" placeholder="Cari kode / nama / kota..."
           class="flex-1 min-w-[200px] rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-red-500">
    <select name="tipe" class="rounded-lg border border-gray-200 px-3 py-2 text-xs">
        <option value="">Semua Tipe</option>
        @foreach (\App\Models\Instansi::TIPE as $v => $l)
            <option value="{{ $v }}" @selected(request('tipe') === $v)>{{ $l }}</option>
        @endforeach
    </select>
    <button class="rounded-lg bg-gray-800 px-4 py-2 text-xs font-semibold text-white">Cari</button>
    @if (request()->hasAny(['q', 'tipe']))
        <a href="{{ route('instansi.index') }}" class="px-2 py-2 text-xs text-gray-500 underline">Reset</a>
    @endif
</form>

<section class="rounded-xl border border-gray-200 bg-white overflow-x-auto">
    <table class="w-full text-xs">
        <thead class="text-left text-[10px] uppercase tracking-wide text-gray-500 border-b bg-gray-50">
            <tr><th class="p-3">Kode</th><th class="p-3">Nama Instansi</th><th class="p-3">Tipe</th><th class="p-3">Kota</th><th class="p-3">Kontak</th><th class="p-3 text-right">Aksi</th></tr>
        </thead>
        <tbody>
        @forelse ($daftar as $i)
            <tr class="border-b last:border-0">
                <td class="p-3 font-bold text-gray-800">{{ $i->kode }}</td>
                <td class="p-3"><p class="font-semibold">{{ $i->nama }}</p><p class="text-[10px] text-gray-400">{{ $i->alamat }}</p></td>
                <td class="p-3">
                    <span class="rounded-full px-2 py-1 text-[10px] font-semibold {{ $i->tipe === 'pmi' ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                        {{ \App\Models\Instansi::TIPE[$i->tipe] }}</span>
                </td>
                <td class="p-3">{{ $i->kota }}</td>
                <td class="p-3 text-gray-500">{{ $i->telepon ?: '-' }}<br>{{ $i->email }}</td>
                <td class="p-3">
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('instansi.edit', $i) }}" class="font-semibold text-blue-700 underline">Ubah</a>
                        <form method="POST" action="{{ route('instansi.destroy', $i) }}" onsubmit="return confirm('Hapus instansi {{ $i->nama }}?')">
                            @csrf @method('DELETE')
                            <button class="font-semibold text-red-600 underline">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="p-8 text-center text-gray-500">
                {{ request()->hasAny(['q', 'tipe']) ? 'Tidak ada instansi yang cocok dengan pencarian.' : 'Belum ada instansi. Tambahkan instansi pertama.' }}
            </td></tr>
        @endforelse
        </tbody>
    </table>
</section>
<div class="mt-4">{{ $daftar->links() }}</div>
@endsection
