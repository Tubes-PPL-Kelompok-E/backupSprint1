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
    /*| Kode rahasia untuk mendaftar sebagai Admin PMI lewat halaman registrasi.
    | Isi ADMIN_REGISTER_CODE di file .env. Jika kosong, registrasi Admin PMI dinonaktifkan.
    */
    'admin_register_code' => env('ADMIN_REGISTER_CODE'),
];
