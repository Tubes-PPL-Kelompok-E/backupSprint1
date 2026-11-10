{{--
    Bagian dashboard stok yang dipakai Petugas Bank Darah dan Admin RS.
    Variabel: $data (StokService::dashboard), $bisaKelola (bool: tampilkan tautan ke halaman stok).
    Server menampilkan keadaan awal; JavaScript memperbarui isi yang sama tiap 30 detik.
--}}
@php
    $r = $data['ringkasan'];
    $kartu = [
        ['id' => 'k-siap', 'label' => 'TOTAL KANTONG READY', 'nilai' => $r['siap_pakai'], 'badge' => 'Tersedia', 'tone' => 'border-blue-500 bg-blue-50 text-blue-700', 'bg' => 'bg-blue-100'],
        ['id' => 'k-exp', 'label' => 'EXPIRED < ' . $data['ambang_hari'] . ' HARI (FEFO ALERT)', 'nilai' => $r['segera_kedaluwarsa'], 'badge' => 'Prioritas Keluar', 'tone' => 'border-red-500 bg-red-50 text-red-600', 'bg' => 'bg-red-100'],
    ];
    $komponenLabel = ['prc' => 'PRC', 'wb' => 'WB', 'tc' => 'TC', 'ffp' => 'FFP'];
@endphp

<div class="grid gap-4 md:grid-cols-2 mb-6">
    @foreach ($kartu as $k)
        <div class="rounded-xl border-l-4 p-5 shadow-sm {{ $k['tone'] }}">
            <p class="text-[11px] font-bold tracking-wide">{{ $k['label'] }}</p>
            <div class="mt-3 flex items-end justify-between">
                <span id="{{ $k['id'] }}" class="text-3xl font-bold">{{ number_format($k['nilai'], 0, ',', '.') }}</span>
                <span class="rounded px-2 py-1 text-[11px] font-medium {{ $k['bg'] }}">{{ $k['badge'] }}</span>
            </div>
        </div>
    @endforeach
</div>

<div class="grid gap-4 lg:grid-cols-3">
    <section class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
            <h2 class="font-bold text-gray-800">Ketersediaan Stok Per Golongan Darah</h2>
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-1 text-gray-600">Komponen:
                    <select id="filter-komponen" class="rounded border border-gray-200 bg-white px-2 py-1 text-xs">
                        <option value="">Semua</option>
                        @foreach ($komponenLabel as $v => $l)
                            <option value="{{ $v }}" @selected($data['komponen'] === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </label>
                <span class="text-gray-400" id="diperbarui" aria-live="polite">Diperbarui {{ $data['diperbarui'] }}</span>
            </div>
        </div>

        <p id="kosong" class="{{ $data['ada_data'] ? 'hidden' : '' }} mt-4 rounded-lg bg-gray-50 p-3 text-xs text-gray-500">Belum ada data kantong darah. Angka ditampilkan sebagai nol sampai petugas mencatat kantong masuk.</p>

        <div class="mt-4 h-64"><canvas id="grafik-stok" role="img" aria-label="Grafik batang stok darah siap pakai per golongan dan rhesus dibandingkan batas minimum"></canvas></div>

        {{-- Tabel yang sama dengan grafik: label teks untuk pembaca layar dan tidak bergantung pada warna --}}
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="text-left text-[10px] uppercase tracking-wide text-gray-500 border-y">
                    <tr><th class="p-2">Golongan</th><th class="p-2">Stok Siap</th><th class="p-2">Batas Minimum</th><th class="p-2">Status</th></tr>
                </thead>
                <tbody id="tabel-stok">
                @foreach ($data['stok'] as $b)
                    <tr class="border-b {{ $b['kritis'] ? 'bg-amber-50' : '' }}">
                        <td class="p-2 font-semibold">Gol. {{ $b['label'] }}</td>
                        <td class="p-2">{{ $b['jumlah'] }}</td>
                        <td class="p-2 text-gray-500">{{ $b['minimum'] }}</td>
                        <td class="p-2">
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $b['kritis'] ? 'bg-amber-200 text-amber-900' : 'bg-green-100 text-green-700' }}">{{ $b['kritis'] ? '⚠ Kritis' : 'Aman' }}</span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div id="peringatan-kritis">
        @foreach ($data['kritis'] as $b)
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                <span>&#9888; Stok golongan <b>{{ $b['label'] }}</b> di bawah batas minimum ({{ $b['jumlah'] }} dari {{ $b['minimum'] }} kantong).</span>
            </div>
        @endforeach
        </div>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="text-sm font-bold text-red-600">&#9888; FEFO Alert System</h2>
        <div id="daftar-alert" class="mt-4 space-y-3">
        @forelse ($data['alert'] as $a)
            <div class="rounded-lg border p-3 {{ $a['sisa_hari'] <= 3 ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50' }}">
                <div class="flex items-start justify-between">
                    <p class="text-xs font-bold text-gray-800">{{ $a['kode'] }}</p>
                    <span class="rounded px-2 py-0.5 text-[10px] font-bold text-white {{ $a['sisa_hari'] <= 3 ? 'bg-red-600' : 'bg-amber-500' }}">Exp: {{ $a['sisa_hari'] }} Hari</span>
                </div>
                <p class="mt-2 text-xs text-gray-600">Gol. {{ $a['golongan'] }} • {{ $a['komponen'] }}</p>
                <p class="mt-1 text-[10px] text-gray-500">Tanggal Expired: {{ $a['kedaluwarsa'] }}</p>
            </div>
        @empty
            <p class="rounded-lg bg-green-50 p-3 text-xs text-green-700">Tidak ada kantong yang mendekati kedaluwarsa.</p>
        @endforelse
        </div>
        @if ($bisaKelola)
            <a href="{{ route('admin.stok', ['status' => 'ready']) }}" class="mt-4 block rounded-lg bg-red-50 border border-red-200 py-2 text-center text-xs font-semibold text-red-600">Lihat Stok (urutan FEFO)</a>
        @endif
    </section>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
(() => {
    const URL_DATA = @json(route('dashboard.data'));
    const POLLING_MS = {{ (int) config('bloodconnect.polling_detik') * 1000 }};
    const awal = @json($data);
    const el = (id) => document.getElementById(id);
    const esc = (t) => String(t).replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const fmt = (n) => Number(n).toLocaleString('id-ID');
    let grafik = null;

    function gambarGrafik(d) {
        const labels = d.stok.map(b => (b.kritis ? '⚠ ' : '') + b.label);
        const stok = d.stok.map(b => b.jumlah);
        const minimum = d.stok.map(b => b.minimum);
        const warna = d.stok.map(b => b.kritis ? '#d97706' : '#dc2626');
        if (typeof Chart === 'undefined') return;           // CDN tidak terjangkau: tabel di bawah tetap menampilkan data
        if (!grafik) {
            grafik = new Chart(el('grafik-stok'), {
                type: 'bar',
                data: { labels, datasets: [
                    { label: 'Stok siap pakai', data: stok, backgroundColor: warna, borderRadius: 4 },
                    { label: 'Batas minimum', data: minimum, type: 'line', borderColor: '#1f2937', borderDash: [6, 4], pointRadius: 3, fill: false },
                ]},
                options: { maintainAspectRatio: false, animation: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, plugins: { legend: { position: 'bottom' } } },
            });
        } else {
            grafik.data.labels = labels;
            grafik.data.datasets[0].data = stok;
            grafik.data.datasets[0].backgroundColor = warna;
            grafik.data.datasets[1].data = minimum;
            grafik.update();
        }
    }

    function isi(d) {
        el('k-siap').textContent = fmt(d.ringkasan.siap_pakai);
        el('k-exp').textContent = fmt(d.ringkasan.segera_kedaluwarsa);
        el('diperbarui').textContent = 'Diperbarui ' + d.diperbarui;
        el('kosong').classList.toggle('hidden', d.ada_data);

        el('tabel-stok').innerHTML = d.stok.map(b => `
            <tr class="border-b ${b.kritis ? 'bg-amber-50' : ''}">
                <td class="p-2 font-semibold">Gol. ${esc(b.label)}</td><td class="p-2">${b.jumlah}</td><td class="p-2 text-gray-500">${b.minimum}</td>
                <td class="p-2"><span class="rounded-full px-2 py-0.5 text-[10px] font-semibold ${b.kritis ? 'bg-amber-200 text-amber-900' : 'bg-green-100 text-green-700'}">${b.kritis ? '⚠ Kritis' : 'Aman'}</span></td>
            </tr>`).join('');

        el('peringatan-kritis').innerHTML = d.kritis.map(b => `
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                <span>&#9888; Stok golongan <b>${esc(b.label)}</b> di bawah batas minimum (${b.jumlah} dari ${b.minimum} kantong).</span>
            </div>`).join('');

        el('daftar-alert').innerHTML = d.alert.length ? d.alert.map(a => `
            <div class="rounded-lg border p-3 ${a.sisa_hari <= 3 ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50'}">
                <div class="flex items-start justify-between"><p class="text-xs font-bold text-gray-800">${esc(a.kode)}</p>
                <span class="rounded px-2 py-0.5 text-[10px] font-bold text-white ${a.sisa_hari <= 3 ? 'bg-red-600' : 'bg-amber-500'}">Exp: ${a.sisa_hari} Hari</span></div>
                <p class="mt-2 text-xs text-gray-600">Gol. ${esc(a.golongan)} • ${esc(a.komponen)}</p>
                <p class="mt-1 text-[10px] text-gray-500">Tanggal Expired: ${esc(a.kedaluwarsa)}</p>
            </div>`).join('') : '<p class="rounded-lg bg-green-50 p-3 text-xs text-green-700">Tidak ada kantong yang mendekati kedaluwarsa.</p>';

        gambarGrafik(d);
    }

    async function muat() {
        const komponen = el('filter-komponen').value;
        try {
            const res = await fetch(URL_DATA + (komponen ? '?komponen=' + komponen : ''), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (res.status === 401 || res.status === 419) { location.href = '/login'; return; }   // sesi berakhir
            if (!res.ok) throw new Error(res.status);
            isi(await res.json());
        } catch (e) {
            el('diperbarui').textContent = 'Gagal memperbarui - mencoba lagi';
        }
    }

    isi(awal);
    el('filter-komponen').addEventListener('change', muat);
    el('btn-refresh')?.addEventListener('click', muat);
    setInterval(() => { if (!document.hidden) muat(); }, POLLING_MS);
})();
</script>
@endpush
