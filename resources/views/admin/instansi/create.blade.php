@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold text-gray-900 mb-4">Tambah Instansi</h1>
<form method="POST" action="{{ route('instansi.store') }}" class="max-w-xl space-y-4 rounded-xl border border-gray-200 bg-white p-6">
    @csrf
    @include('admin.instansi._form')
    <div class="flex items-center gap-3">
        <button class="rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700">Simpan</button>
        <a href="{{ route('instansi.index') }}" class="text-sm text-gray-600">Batal</a>
    </div>
</form>
@endsection
