<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kode Registrasi Admin PMI
    |--------------------------------------------------------------------------
    | Kode rahasia yang diminta saat mendaftar lewat tab "Admin PMI".
    |
    | - Isi ADMIN_REGISTER_CODE di file .env untuk memakai kode sendiri (disarankan).
    | - Jika .env kosong dan APP_ENV=local, dipakai kode bawaan "PMI-ADMIN-2026"
    |   supaya mudah diuji di komputer sendiri.
    | - Di luar lokal (production) tanpa ADMIN_REGISTER_CODE, registrasi admin dinonaktifkan.
    */
    'admin_register_code' => env(
        'ADMIN_REGISTER_CODE',
        env('APP_ENV') === 'local' ? 'PMI-ADMIN-2026' : null
    ),

    /*
    |--------------------------------------------------------------------------
    | Parameter stok & peringatan (dapat diubah tanpa mengubah kode)
    |--------------------------------------------------------------------------
    */

    // Batas minimum stok bawaan per komponen untuk SETIAP kombinasi golongan + rhesus.
    // Jumlah keempat komponen = 50 kantong (di bawah 50 kantong = kritis).
    // Dapat ditimpa per golongan/rhesus/komponen lewat menu Batas Minimum Stok.
    'batas_minimum_default' => [
        'prc' => 30,
        'wb'  => 10,
        'tc'  => 5,
        'ffp' => 5,
    ],

    // Kantong Ready dengan sisa masa simpan KURANG dari ambang ini masuk FEFO Alert.
    'ambang_kedaluwarsa_hari' => (int) env('AMBANG_KEDALUWARSA_HARI', 7),

    // Jeda minimal antar donasi (hari) untuk menentukan kelayakan pendonor.
    'interval_donor_hari' => (int) env('INTERVAL_DONOR_HARI', 60),

    // Ambang pemeriksaan pra-donor. Nilai bawaan mengikuti praktik umum donor darah
    // dan DAPAT DISESUAIKAN dengan SOP UTD setempat; proposal hanya menyebut hemoglobin dan tekanan darah.
    'pemeriksaan' => [
        'hb_min' => 12.5, 'hb_maks' => 17.0,
        'sistolik_min' => 90, 'sistolik_maks' => 160,
        'diastolik_min' => 60, 'diastolik_maks' => 100,
        'berat_min' => 45.0,
        'suhu_maks' => 37.5,
        'nadi_min' => 50, 'nadi_maks' => 100,
        'umur_min' => 17, 'umur_maks' => 65,
    ],

    // Interval polling otomatis dashboard (detik).
    'polling_detik' => 30,
];
