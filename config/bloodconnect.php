<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kode Registrasi Admin PMI
    |--------------------------------------------------------------------------

    'admin_register_code' => env(
        'ADMIN_REGISTER_CODE',
        env('APP_ENV') === 'local' ? 'PMI-ADMIN-2026' : null
    )

    /*
    |--------------------------------------------------------------------------
    | Parameter stok & peringatan
    |--------------------------------------------------------------------------
    */



    'ambang_kedaluwarsa_hari' => (int) env('AMBANG_KEDALUWARSA_HARI', 7),

    'interval_donor_hari' => (int) env('INTERVAL_DONOR_HARI', 60),

    'pemeriksaan' => [
        'hb_min' => 12.5, 'hb_maks' => 17.0,
        'sistolik_min' => 90, 'sistolik_maks' => 160,
        'diastolik_min' => 60, 'diastolik_maks' => 100,
        'berat_min' => 45.0,
        'suhu_maks' => 37.5,
        'nadi_min' => 50, 'nadi_maks' => 100,
        'umur_min' => 17, 'umur_maks' => 65,
    ],

    'polling_detik' => 30,
];
