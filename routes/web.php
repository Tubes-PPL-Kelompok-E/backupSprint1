<?php

use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Admin\InstansiController;
use App\Http\Controllers\Admin\KantongDarahController;
use App\Http\Controllers\Admin\PermintaanController;
use App\Http\Controllers\Admin\UjiSaringController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KegiatanDonorController;

Route::get('/', fn () => Auth::check()
    ? redirect()->route(Auth::user()->homeRoute())
    : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');

    // Registrasi (dibatasi 5 percobaan per menit per IP)
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1')->name('register.store');

    // Lupa kata sandi (nama route 'password.reset' wajib untuk email bawaan Laravel)
    // Lupa kata sandi
    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'send'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'update'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Pengalihan halaman sesuai peran
Route::middleware('auth')->group(function () {
    Route::view('/petugas/dashboard', 'dashboard', ['title' => 'Dashboard Petugas RS'])
        ->middleware('role:petugas_rs')->name('petugas.dashboard');

    Route::view('/pendonor/dashboard', 'dashboard', ['title' => 'Dashboard Pendonor'])
        ->middleware('role:pendonor')->name('pendonor.dashboard');
});

// Manajemen akun (hanya Admin PMI)
Route::middleware(['auth', 'role:admin_pmi'])->prefix('admin')->group(function () {
    Route::get('dashboard', [AdminPageController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('stok-darah', [KantongDarahController::class, 'index'])->name('admin.stok');
    Route::get('kantong-darah/create', [KantongDarahController::class, 'create'])->name('admin.kantong.create');
    Route::get('kantong-darah/cek-batch', [KantongDarahController::class, 'cekBatch'])->name('admin.kantong.cek');
    Route::post('kantong-darah', [KantongDarahController::class, 'store'])->name('admin.kantong.store');
    Route::patch('kantong-darah/{kantong}/musnahkan', [KantongDarahController::class, 'musnahkan'])->name('admin.kantong.musnahkan');

    Route::get('uji-saring', [UjiSaringController::class, 'index'])->name('admin.uji-saring');
    Route::post('uji-saring/{kantong}', [UjiSaringController::class, 'store'])->name('admin.uji-saring.store');
    Route::get('uji-saring/{kantong}/berkas', [UjiSaringController::class, 'berkas'])->name('admin.uji-saring.berkas');

    // Permintaan darah rumah sakit: validasi, alokasi FEFO, dan pengiriman
    Route::get('permintaan-rs', [PermintaanController::class, 'index'])->name('admin.permintaan');
    Route::get('permintaan-rs/create', [PermintaanController::class, 'create'])->name('admin.permintaan.create');
    Route::post('permintaan-rs', [PermintaanController::class, 'store'])->name('admin.permintaan.store');
    Route::post('permintaan-rs/{permintaan}/validasi', [PermintaanController::class, 'validasi'])->name('admin.permintaan.validasi');
    Route::post('permintaan-rs/{permintaan}/tolak', [PermintaanController::class, 'tolak'])->name('admin.permintaan.tolak');
    Route::patch('permintaan-rs/{permintaan}/lacak', [PermintaanController::class, 'lacak'])->name('admin.permintaan.lacak');
    Route::post('permintaan-rs/{permintaan}/selesai', [PermintaanController::class, 'selesai'])->name('admin.permintaan.selesai');

    Route::get('laporan', [AdminPageController::class, 'laporan'])->name('admin.laporan');
    Route::get('kegiatan-donor', [AdminPageController::class, 'kegiatan'])->name('admin.kegiatan');

    // PBI-02: kelola instansi (PMI dan rumah sakit)
    Route::resource('instansi', InstansiController::class)->except(['show']);

    Route::resource('users', UserController::class)->except(['show']);
    Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::patch('users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
});

Route::middleware(['auth', 'role:admin_pmi'])->prefix('admin')->group(function () {
    Route::patch('kegiatan-donor/{kegiatan}/tutup', [KegiatanDonorController::class, 'tutup'])
        ->name('kegiatan-donor.tutup');
    Route::patch('kegiatan-donor/{kegiatan}/buka', [KegiatanDonorController::class, 'buka'])
    ->name('kegiatan-donor.buka');

    Route::resource('kegiatan-donor', KegiatanDonorController::class)
        ->parameters(['kegiatan-donor' => 'kegiatan']);
});
// PBI-09: Pencatatan donasi oleh petugas bank darah
Route::middleware(['auth', 'role:'.implode('|', config('donor.officer_roles'))])
    ->prefix('petugas')
    ->group(function () {
        Route::resource('donasi', \App\Http\Controllers\Petugas\DonationController::class)
            ->parameters(['donasi' => 'donation'])
            ->except(['show'])
            ->names('petugas.donasi');
    });

// PBI-09: Riwayat donasi & tanggal boleh donor berikutnya (portal pendonor)
Route::middleware(['auth', 'role:'.config('donor.donor_role')])
    ->get('/pendonor/riwayat', [\App\Http\Controllers\Pendonor\DonorHistoryController::class, 'index'])
    ->name('pendonor.riwayat');
