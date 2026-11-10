<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrasi - BloodConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',system-ui,sans-serif}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4"
      style="background:linear-gradient(135deg,#8b1a1a 0%,#b91c1c 55%,#1a1a2e 100%)">

<div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden">
    <div class="bg-red-700 text-white text-center py-7 px-6">
        <div class="mx-auto mb-3 w-10 h-10 rounded-full bg-white text-red-700 flex items-center justify-center">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5c3.2 4.2 6 7.4 6 11a6 6 0 0 1-12 0c0-3.6 2.8-6.8 6-11z"/></svg>
        </div>
        <h1 class="text-xl font-bold">Registrasi Akun</h1>
        <p class="text-xs text-red-100 mt-1">Bergabung bersama BloodConnect</p>
    </div>

    <form method="POST" action="{{ route('register.store') }}" class="p-6 space-y-4">
        @csrf
        <input type="hidden" name="role" id="role" value="{{ old('role', 'pendonor') }}">

        <p class="text-center text-[11px] font-semibold text-gray-400">Daftar sebagai</p>
        <div class="grid grid-cols-3 gap-1 bg-gray-100 rounded-xl p-1">
            @foreach (['admin_pmi' => 'Admin PMI', 'petugas_rs' => 'Petugas RS', 'pendonor' => 'Pendonor'] as $val => $label)
                <button type="button" data-role="{{ $val }}"
                        class="tab py-2 text-xs font-semibold rounded-lg text-gray-500">{{ $label }}</button>
            @endforeach
        </div>

        @if ($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs p-3">
                <ul class="list-disc pl-4 space-y-0.5">
                    @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Lengkap</label>
            <input type="text" name="name" value="{{ old('name') }}" required autofocus
                   class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required placeholder="nama@email.com"
                   class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
        </div>
        <div>
            <label id="instansiLabel" class="block text-xs font-semibold text-gray-700 mb-1">Instansi</label>
            <input type="text" name="instansi" id="instansi" value="{{ old('instansi') }}"
                   class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
        </div>

        <div id="kodeWrap" class="hidden">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Kode Registrasi Admin</label>
            <input type="password" name="kode_admin" id="kode" autocomplete="off" placeholder="Diberikan oleh administrator"
                   class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Kata Sandi</label>
            <input type="password" name="password" required placeholder="Min. 8 karakter, huruf dan angka"
                   class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Konfirmasi Kata Sandi</label>
            <input type="password" name="password_confirmation" required
                   class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
        </div>

        <button class="w-full rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-semibold py-2.5">
            Daftar Sekarang &rarr;
        </button>

        <p class="text-center text-xs text-gray-500">
            Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-red-600">Masuk</a>
        </p>
    </form>
    <div class="bg-gray-50 text-center text-[10px] text-gray-400 py-3">Mendukung Target SDG 3: Good Health and Well-being</div>
</div>

<script>
    const roleInput = document.getElementById('role');
    const tabs = document.querySelectorAll('.tab');
    const label = document.getElementById('instansiLabel');
    const instansi = document.getElementById('instansi');
    const kodeWrap = document.getElementById('kodeWrap');
    const kode = document.getElementById('kode');

    const CFG = {
        admin_pmi:  {label: 'Instansi (Unit / Cabang PMI)', ph: 'Contoh: PMI Jakarta Pusat', req: true,  kode: true},
        petugas_rs: {label: 'Nama Rumah Sakit',             ph: 'Contoh: RSUD Jakarta',      req: true,  kode: false},
        pendonor:   {label: 'Instansi (opsional)',          ph: 'Contoh: Universitas / Perusahaan', req: false, kode: false},
    };

    function setRole(r) {
        roleInput.value = r;
        tabs.forEach(t => {
            const on = t.dataset.role === r;
            t.classList.toggle('bg-red-600', on);
            t.classList.toggle('text-white', on);
            t.classList.toggle('shadow', on);
        });
        const c = CFG[r];
        label.textContent = c.label;
        instansi.placeholder = c.ph;
        instansi.required = c.req;
        kodeWrap.classList.toggle('hidden', !c.kode);
        kode.required = c.kode;
        kode.disabled = !c.kode;
    }
    tabs.forEach(t => t.addEventListener('click', () => setRole(t.dataset.role)));
    setRole(roleInput.value);
</script>
</body>
</html>
