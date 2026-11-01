<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Donasi;
use App\Models\KantongDarah;
use App\Models\KegiatanDonor;
use App\Models\KomponenDarah;
use App\Models\Notifikasi;
use App\Models\PanggilanPendonor;
use App\Models\Pendonor;
use App\Models\Pengiriman;
use App\Models\PermintaanDarah;
use App\Models\RumahSakit;
use App\Models\UjiSaring;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint1DatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_peran_dan_akun_default_tersedia(): void
    {
        $admin = User::where('email', 'admin.pmi@bloodconnect.id')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin_pmi'));
        $this->assertTrue($admin->hasRole('Petugas Bank Darah'));

        $adminRS = User::where('email', 'petugas.rs@bloodconnect.id')->first();
        $this->assertNotNull($adminRS);
        $this->assertTrue($adminRS->hasRole('petugas_rs'));
        $this->assertTrue($adminRS->hasRole('Admin Rumah Sakit'));
        $this->assertNotNull($adminRS->institution_id);
        $this->assertEquals('RS Siloam Semanggi', $adminRS->rumahSakit->nama_rs);

        $pendonorUser = User::where('email', 'pendonor@bloodconnect.id')->first();
        $this->assertNotNull($pendonorUser);
        $this->assertTrue($pendonorUser->hasRole('pendonor'));
        $this->assertTrue($pendonorUser->hasRole('Pendonor'));
    }

    public function test_master_komponen_darah_tersedia(): void
    {
        $prc = KomponenDarah::where('kode_komponen', 'PRC')->first();
        $this->assertNotNull($prc);
        $this->assertEquals(42, $prc->masa_simpan_hari);

        $tc = KomponenDarah::where('kode_komponen', 'TC')->first();
        $this->assertNotNull($tc);
        $this->assertEquals(5, $tc->masa_simpan_hari);

        $this->assertGreaterThanOrEqual(4, KomponenDarah::count());
    }

    public function test_relasi_rantai_pasok_donor_ke_uji_saring(): void
    {
        $pendonor = Pendonor::where('kode_pendonor', '#D-000001')->first();
        $this->assertNotNull($pendonor);
        $this->assertTrue($pendonor->donasi()->exists());

        $donasi = $pendonor->donasi()->first();
        $this->assertTrue($donasi->kantongDarah()->exists());

        $kantong = $donasi->kantongDarah()->first();
        $this->assertNotNull($kantong->komponenDarah);
        $this->assertNotNull($kantong->ujiSaring);
        $this->assertEquals('Lolos', $kantong->ujiSaring->kesimpulan);
        $this->assertTrue($kantong->ujiSaring->isAman());
    }

    public function test_fitur_fefo_alert_mendeteksi_kantong_mendekati_kedaluwarsa(): void
    {
        $kantongMendekati = KantongDarah::fefo()->mendekatiKedaluwarsa(7)->get();

        $this->assertNotEmpty($kantongMendekati);
        // Pastikan terurut tanggal kedaluwarsa paling awal dahulu (FEFO)
        $previousExp = null;
        foreach ($kantongMendekati as $kantong) {
            $this->assertEquals('Ready', $kantong->status_kantong);
            $this->assertLessThanOrEqual(7, $kantong->sisa_hari_kedaluwarsa);
            if ($previousExp) {
                $this->assertGreaterThanOrEqual($previousExp, $kantong->tanggal_kedaluwarsa->timestamp);
            }
            $previousExp = $kantong->tanggal_kedaluwarsa->timestamp;
        }
    }

    public function test_permintaan_cito_dan_alokasi_cold_chain(): void
    {
        $permintaan = PermintaanDarah::where('nomor_permintaan', '#REQ-202610-0001')->first();
        $this->assertNotNull($permintaan);
        $this->assertEquals('Cito', $permintaan->urgensi);
        $this->assertEquals('Diproses', $permintaan->status_permintaan);

        // Cek detail & alokasi
        $detail = $permintaan->detailPermintaan()->first();
        $this->assertNotNull($detail);
        $this->assertEquals(1, $detail->jumlah_dialokasikan);
        $this->assertTrue($detail->alokasiKantong()->exists());

        // Cek pengiriman
        $pengiriman = $permintaan->pengiriman;
        $this->assertNotNull($pengiriman);
        $this->assertEquals('Dalam Perjalanan', $pengiriman->status_pengiriman);
        $this->assertNotNull($pengiriman->suhu_kotak_pendingin);
        $this->assertLessThanOrEqual(6.0, $pengiriman->suhu_kotak_pendingin);
    }

    public function test_audit_log_append_only_dan_notifikasi(): void
    {
        $logs = AuditLog::all();
        $this->assertNotEmpty($logs);

        // Audit log immutable (tanpa updated_at)
        $firstLog = $logs->first();
        $this->assertNotNull($firstLog->created_at);
        $this->assertFalse(isset($firstLog->updated_at));

        $notifikasi = Notifikasi::unread()->get();
        $this->assertNotEmpty($notifikasi);

        $panggilan = PanggilanPendonor::all();
        $this->assertNotEmpty($panggilan);
    }
}

