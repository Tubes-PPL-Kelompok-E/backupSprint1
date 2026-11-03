@csrf

<div class="kd-field">
    <label for="nama">Nama Kegiatan</label>
    <input type="text" id="nama" name="nama" value="{{ old('nama', $kegiatan->nama ?? '') }}" required>
    @error('nama') <div class="kd-error">{{ $message }}</div> @enderror
</div>

<div class="kd-row">
    <div class="kd-field">
        <label for="tanggal">Tanggal</label>
        <input type="date" id="tanggal" name="tanggal"
               value="{{ old('tanggal', isset($kegiatan) ? $kegiatan->tanggal->format('Y-m-d') : '') }}" required>
        @error('tanggal') <div class="kd-error">{{ $message }}</div> @enderror
    </div>

    <div class="kd-field">
        <label for="kuota">Kuota</label>
        <input type="number" id="kuota" name="kuota" min="1" value="{{ old('kuota', $kegiatan->kuota ?? '') }}" required>
        @error('kuota') <div class="kd-error">{{ $message }}</div> @enderror
    </div>
</div>

<div class="kd-field">
    <label for="lokasi">Lokasi</label>
    <input type="text" id="lokasi" name="lokasi" value="{{ old('lokasi', $kegiatan->lokasi ?? '') }}" required>
    @error('lokasi') <div class="kd-error">{{ $message }}</div> @enderror
</div>

<div class="kd-field">
    <label for="deskripsi">Deskripsi (opsional)</label>
    <textarea id="deskripsi" name="deskripsi" rows="4">{{ old('deskripsi', $kegiatan->deskripsi ?? '') }}</textarea>
    @error('deskripsi') <div class="kd-error">{{ $message }}</div> @enderror
</div>