@extends('layouts.app')
@section('content')
<section class="rounded-xl border border-gray-200 bg-white p-5 mb-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900">{{ $kegiatan->judul }}</h1>
            <p class="mt-2 text-xs text-gray-500">{{ $kegiatan->tanggal->translatedFormat('l, d F Y') }} • {{ $kegiatan->jam() }} • {{ $kegiatan->nama_tempat }}, {{ $kegiatan->alamat }}</p>
            <p class="mt-1 text-xs text-gray-700">Status: <b>{{ \App\Models\KegiatanDonor::STATUS[$kegiatan->status] }}</b> • Terdaftar <b>{{ $kegiatan->pendaftaran_count }}</b> dari kuota <b>{{ $kegiatan->kuota }}</b></p>
        </div>
        <div class="flex flex-wrap gap-2 text-xs font-semibold">
            @if ($kegiatan->status === 'terbuka')
                <a href="{{ route('admin.kegiatan.edit', $kegiatan) }}" class="rounded border px-3 py-2 text-gray-700">Ubah Kegiatan</a>
                <form method="POST" action="{{ route('admin.kegiatan.tutup', $kegiatan) }}">@csrf @method('PATCH')<button class="rounded border px-3 py-2 text-gray-700">Tutup Pendaftaran</button></form>
                <form method="POST" action="{{ route('admin.kegiatan.batalkan', $kegiatan) }}" onsubmit="return confirm('Batalkan kegiatan ini?')">@csrf @method('PATCH')<button class="rounded border border-red-300 px-3 py-2 text-red-600">Batalkan Kegiatan</button></form>
            @endif
            <a href="{{ route('admin.kegiatan') }}" class="rounded border px-3 py-2 text-gray-700">&larr; Daftar</a>
        </div>
    </div>
</section>

<section class="rounded-xl border border-gray-200 bg-white overflow-x-auto">
    <div class="p-4 text-xs"><h2 class="font-bold">Peserta &amp; Kehadiran (Hari H)</h2></div>
    <table class="w-full text-xs">
        <thead class="text-left text-[10px] uppercase tracking-wide text-gray-500 border-y bg-gray-50">
            <tr><th class="p-3">Pendonor</th><th class="p-3">Gol. Darah</th><th class="p-3">Tanggal Daftar</th><th class="p-3">Kehadiran</th><th class="p-3 text-right">Aksi</th></tr>
        </thead>
        <tbody>
        @forelse ($peserta as $p)
            @php $tone = ['terdaftar' => 'bg-amber-100 text-amber-700', 'hadir' => 'bg-green-100 text-green-700', 'tidak_hadir' => 'bg-gray-200 text-gray-700'][$p->status_kehadiran]; @endphp
            <tr class="border-b">
                <td class="p-3 font-bold">{{ $p->pendonor->nama }} <span class="font-normal text-gray-400">({{ $p->pendonor->kode_donor }})</span></td>
                <td class="p-3 font-bold text-red-600">{{ $p->pendonor->golonganLengkap() }}</td>
                <td class="p-3 text-gray-500">{{ $p->tanggal_daftar->format('Y-m-d H:i') }}</td>
                <td class="p-3"><span class="rounded-full px-2 py-0.5 font-semibold {{ $tone }}">{{ \App\Models\PendaftaranKegiatan::KEHADIRAN[$p->status_kehadiran] }}</span></td>
                <td class="p-3 text-right">
                    <form method="POST" action="{{ route('admin.kegiatan.kehadiran', [$kegiatan, $p]) }}" class="inline-flex gap-1">
                        @csrf @method('PATCH')
                        <button name="status_kehadiran" value="hadir" class="rounded border border-green-300 bg-green-50 px-3 py-1.5 font-semibold text-green-700">Hadir</button>
                        <button name="status_kehadiran" value="tidak_hadir" class="rounded border bg-gray-100 px-3 py-1.5 font-semibold text-gray-600">Tidak Hadir</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="p-6 text-center text-gray-500">Belum ada peserta terdaftar.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endsection
