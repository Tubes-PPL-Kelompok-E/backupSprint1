@extends('donasi.layout')
@section('title', 'Catat Donasi')

@section('content')
<div class="card" style="padding:16px">
    @include('donasi.petugas._form', [
        'action' => route('petugas.donasi.store'),
        'method' => 'POST',
        'donation' => null,
    ])
</div>
@endsection