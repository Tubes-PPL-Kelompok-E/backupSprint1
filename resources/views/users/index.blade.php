@extends('layouts.app')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold text-gray-800">Manajemen Akun</h1>
    <a href="{{ route('users.create') }}" class="bg-red-600 text-white text-sm font-semibold rounded px-4 py-2">+ Tambah Akun</a>
</div>

<form method="GET" class="flex gap-2 mb-4">
    <input name="q" value="{{ request('q') }}" placeholder="Cari nama / email" class="border rounded px-3 py-2 text-sm flex-1">
    <select name="role" class="border rounded px-3 py-2 text-sm">
        <option value="">Semua peran</option>
        @foreach (['admin_pmi','petugas_rs','pendonor'] as $r)
            <option value="{{ $r }}" @selected(request('role') === $r)>{{ $r }}</option>
        @endforeach
    </select>
    <select name="instansi" class="border rounded px-3 py-2 text-sm">
        <option value="">Semua instansi</option>
        @foreach ($daftarInstansi as $i)
            <option value="{{ $i }}" @selected(request('instansi') === $i)>{{ $i }}</option>
        @endforeach
    </select>
    <button class="bg-gray-800 text-white text-sm rounded px-4">Cari</button>
</form>

<div class="bg-white rounded shadow overflow-x-auto">
<table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-600">
        <tr><th class="p-3">Nama</th><th class="p-3">Email</th><th class="p-3">Peran</th><th class="p-3">Instansi</th><th class="p-3">Status</th><th class="p-3">Aksi</th></tr>
    </thead>
    <tbody>
    @forelse ($users as $u)
        <tr class="border-t">
            <td class="p-3">{{ $u->name }}</td>
            <td class="p-3">{{ $u->email }}</td>
            <td class="p-3">{{ $u->getRoleNames()->first() }}</td>
            <td class="p-3">{{ $u->instansi }}</td>
            <td class="p-3">
                @if ($u->trashed()) <span class="text-red-600 font-semibold">Nonaktif</span>
                @else <span class="text-green-600 font-semibold">Aktif</span> @endif
            </td>
            <td class="p-3 flex gap-2">
                @if ($u->trashed())
                    <form method="POST" action="{{ route('users.restore', $u->id) }}">@csrf @method('PATCH')
                        <button class="text-green-700 underline">Aktifkan</button></form>
                @else
                    <a href="{{ route('users.edit', $u) }}" class="text-blue-700 underline">Edit</a>
                    <form method="POST" action="{{ route('users.destroy', $u) }}"
                          onsubmit="return confirm('Nonaktifkan akun ini?')">@csrf @method('DELETE')
                        <button class="text-red-700 underline">Nonaktifkan</button></form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="p-6 text-center text-gray-500">Belum ada akun. Tambahkan akun pertama.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection