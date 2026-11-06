@php $isEdit = isset($instansi); $cls = 'w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500'; @endphp

<div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">Kode Instansi</label>
    @if ($isEdit)
        <input value="{{ $instansi->kode }}" disabled class="{{ $cls }} text-gray-500 cursor-not-allowed">
        <p class="mt-1 text-[11px] text-gray-400">Kode tidak dapat diubah.</p>
    @else
        <input name="kode" value="{{ old('kode') }}" placeholder="Kosongkan untuk dibuat otomatis (PMI-001 / RS-001)" class="{{ $cls }} uppercase">
        <p class="mt-1 text-[11px] text-gray-400">Huruf besar, angka, dan tanda minus. Harus unik.</p>
    @endif
</div>
<div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Instansi</label>
    <input name="nama" value="{{ old('nama', $instansi->nama ?? '') }}" required class="{{ $cls }}">
</div>
<div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">Tipe</label>
    <select name="tipe" required class="{{ $cls }}">
        @foreach (\App\Models\Instansi::TIPE as $v => $l)
            <option value="{{ $v }}" @selected(old('tipe', $instansi->tipe ?? 'rumah_sakit') === $v)>{{ $l }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">Alamat</label>
    <textarea name="alamat" rows="2" required class="{{ $cls }}">{{ old('alamat', $instansi->alamat ?? '') }}</textarea>
</div>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-xs font-semibold text-gray-700 mb-1">Kota</label>
        <input name="kota" value="{{ old('kota', $instansi->kota ?? '') }}" required class="{{ $cls }}">
    </div>
    <div>
        <label class="block text-xs font-semibold text-gray-700 mb-1">Telepon <span class="font-normal text-gray-400">(opsional)</span></label>
        <input name="telepon" value="{{ old('telepon', $instansi->telepon ?? '') }}" class="{{ $cls }}">
    </div>
</div>
<div>
    <label class="block text-xs font-semibold text-gray-700 mb-1">Email <span class="font-normal text-gray-400">(opsional)</span></label>
    <input type="email" name="email" value="{{ old('email', $instansi->email ?? '') }}" class="{{ $cls }}">
</div>
