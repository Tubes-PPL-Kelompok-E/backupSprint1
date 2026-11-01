<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <label>Pendonor
        <select name="user_id" required>
            <option value="">-- pilih pendonor --</option>
            @foreach ($donors as $u)
                <option value="{{ $u->id }}" @selected(old('user_id', $donation?->user_id) == $u->id)>
                    {{ $u->name }} ({{ $u->email }})
                </option>
            @endforeach
        </select>
    </label>

    <label>Kode kantong
        <input type="text" name="blood_bag_code" maxlength="50" required
               value="{{ old('blood_bag_code', $donation?->blood_bag_code) }}">
    </label>

    <label>Tanggal donasi
        <input type="date" name="donation_date" max="{{ now()->toDateString() }}" required
               value="{{ old('donation_date', $donation?->donation_date?->toDateString()) }}">
    </label>

    <label>Volume (ml)
        <input type="number" name="volume_ml" min="250" max="500" required
               value="{{ old('volume_ml', $donation?->volume_ml ?? 350) }}">
    </label>

    <label>Golongan darah (opsional)
        <select name="blood_type">
            <option value="">-</option>
            @foreach (['A', 'B', 'AB', 'O'] as $g)
                <option value="{{ $g }}" @selected(old('blood_type', $donation?->blood_type) === $g)>{{ $g }}</option>
            @endforeach
        </select>
    </label>

    <label>Catatan (opsional)
        <textarea name="notes" rows="2" maxlength="255">{{ old('notes', $donation?->notes) }}</textarea>
    </label>

    <button type="submit">Simpan</button>
    <a class="btn light" href="{{ route('petugas.donasi.index') }}">Kembali</a>
</form>