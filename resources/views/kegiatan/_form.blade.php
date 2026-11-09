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

<div class="kd-row">
    <div class="kd-field">
        <label for="jam_mulai">Jam Mulai (opsional)</label>
        <input type="time" id="jam_mulai" name="jam_mulai"
               value="{{ old('jam_mulai', isset($kegiatan) && $kegiatan->jam_mulai ? substr($kegiatan->jam_mulai, 0, 5) : '') }}">
        @error('jam_mulai') <div class="kd-error">{{ $message }}</div> @enderror
    </div>
    <div class="kd-field">
        <label for="jam_selesai">Jam Selesai (opsional)</label>
        <input type="time" id="jam_selesai" name="jam_selesai"
               value="{{ old('jam_selesai', isset($kegiatan) && $kegiatan->jam_selesai ? substr($kegiatan->jam_selesai, 0, 5) : '') }}">
        @error('jam_selesai') <div class="kd-error">{{ $message }}</div> @enderror
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