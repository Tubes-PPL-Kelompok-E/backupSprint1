<?php

use App\Http\Controllers\Admin\BatasMinimumController;
use App\Http\Controllers\Admin\KegiatanDonorController;
use App\Http\Controllers\Admin\LokasiPenyimpananController;
use App\Http\Controllers\Pendonor\ProfilController as PendonorProfilController;
use App\Http\Controllers\Admin\PendonorController;
use App\Http\Controllers\Admin\InstansiController;
use App\Http\Controllers\Admin\KantongDarahController;
use App\Http\Controllers\Admin\UjiSaringController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Pendonor\DashboardController as PendonorDashboardController;
use App\Http\Controllers\Pendonor\KegiatanController as PendonorKegiatanController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Auth::check()
    ? redirect()->route(Auth::user()->homeRoute())
    : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');

    // Registrasi pendonor (dibatasi 5 percobaan per menit per IP)
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1')->name('register.store');

    // Lupa kata sandi (nama route 'password.reset' wajib untuk email bawaan Laravel)
    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'send'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    // Data dashboard untuk polling 30 detik (Petugas Bank Darah dan Admin RS)
    Route::get('/dashboard/data', [DashboardController::class, 'data'])
        ->middleware('role:admin_pmi|petugas_rs')->name('dashboard.data');
});

// Portal Admin RS
Route::get('/petugas/dashboard', [DashboardController::class, 'rs'])
    ->middleware(['auth', 'role:petugas_rs'])->name('petugas.dashboard');

// Portal Pendonor
Route::middleware(['auth', 'role:pendonor'])->prefix('pendonor')->group(function () {
    Route::get('dashboard', [PendonorDashboardController::class, 'index'])->name('pendonor.dashboard');
    Route::get('riwayat', [PendonorDashboardController::class, 'riwayat'])->name('pendonor.riwayat');
    Route::get('kegiatan', [PendonorKegiatanController::class, 'index'])->name('pendonor.kegiatan');
    Route::post('kegiatan/{kegiatan}/daftar', [PendonorKegiatanController::class, 'daftar'])->name('pendonor.kegiatan.daftar');
    Route::delete('kegiatan/{kegiatan}/daftar', [PendonorKegiatanController::class, 'batal'])->name('pendonor.kegiatan.batal');
    Route::get('profil', [PendonorProfilController::class, 'show'])->name('pendonor.profil');
    Route::put('profil', [PendonorProfilController::class, 'update'])->name('pendonor.profil.update');
});

// Portal Admin PMI (Petugas Bank Darah)
Route::middleware(['auth', 'role:admin_pmi'])->prefix('admin')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');
    Route::get('batas-minimum', [BatasMinimumController::class, 'index'])->name('admin.batas-minimum');
    Route::put('batas-minimum', [BatasMinimumController::class, 'update'])->name('admin.batas-minimum.update');
    Route::delete('batas-minimum', [BatasMinimumController::class, 'reset'])->name('admin.batas-minimum.reset');
    Route::get('stok-darah', [KantongDarahController::class, 'index'])->name('admin.stok');
    Route::get('kantong-darah/create', [KantongDarahController::class, 'create'])->name('admin.kantong.create');
    Route::get('kantong-darah/cek-batch', [KantongDarahController::class, 'cekBatch'])->name('admin.kantong.cek');
    Route::post('kantong-darah', [KantongDarahController::class, 'store'])->name('admin.kantong.store');
    Route::get('kantong-darah/{kantong}/ubah', [KantongDarahController::class, 'edit'])->name('admin.kantong.edit');
    Route::put('kantong-darah/{kantong}', [KantongDarahController::class, 'update'])->name('admin.kantong.update');
    Route::delete('kantong-darah/{kantong}', [KantongDarahController::class, 'destroy'])->name('admin.kantong.destroy');
    Route::patch('kantong-darah/{kantong}/musnahkan', [KantongDarahController::class, 'musnahkan'])->name('admin.kantong.musnahkan');

    Route::get('uji-saring', [UjiSaringController::class, 'index'])->name('admin.uji-saring');
    Route::post('uji-saring/{kantong}', [UjiSaringController::class, 'store'])->name('admin.uji-saring.store');
    Route::get('uji-saring/{kantong}/berkas', [UjiSaringController::class, 'berkas'])->name('admin.uji-saring.berkas');

    // Lokasi penyimpanan kantong
    Route::get('lokasi-penyimpanan', [LokasiPenyimpananController::class, 'index'])->name('admin.lokasi');
    Route::post('lokasi-penyimpanan', [LokasiPenyimpananController::class, 'store'])->name('admin.lokasi.store');
    Route::put('lokasi-penyimpanan/{lokasi}', [LokasiPenyimpananController::class, 'update'])->name('admin.lokasi.update');
    Route::delete('lokasi-penyimpanan/{lokasi}', [LokasiPenyimpananController::class, 'destroy'])->name('admin.lokasi.destroy');

    // Kegiatan donor dan data pendonor
    Route::get('kegiatan-donor', [KegiatanDonorController::class, 'index'])->name('admin.kegiatan');
    Route::get('kegiatan-donor/buat', [KegiatanDonorController::class, 'create'])->name('admin.kegiatan.create');
    Route::post('kegiatan-donor', [KegiatanDonorController::class, 'store'])->name('admin.kegiatan.store');
    Route::get('kegiatan-donor/{kegiatan}', [KegiatanDonorController::class, 'show'])->name('admin.kegiatan.show');
    Route::get('kegiatan-donor/{kegiatan}/ubah', [KegiatanDonorController::class, 'edit'])->name('admin.kegiatan.edit');
    Route::put('kegiatan-donor/{kegiatan}', [KegiatanDonorController::class, 'update'])->name('admin.kegiatan.update');
    Route::patch('kegiatan-donor/{kegiatan}/tutup', [KegiatanDonorController::class, 'tutup'])->name('admin.kegiatan.tutup');
    Route::patch('kegiatan-donor/{kegiatan}/batalkan', [KegiatanDonorController::class, 'batalkan'])->name('admin.kegiatan.batalkan');
    Route::patch('kegiatan-donor/{kegiatan}/kehadiran/{pendaftaran}', [KegiatanDonorController::class, 'kehadiran'])->name('admin.kegiatan.kehadiran');
    Route::delete('kegiatan-donor/{kegiatan}', [KegiatanDonorController::class, 'destroy'])->name('admin.kegiatan.destroy');

    Route::get('pendonor', [PendonorController::class, 'index'])->name('admin.pendonor');
    Route::get('pendonor/buat', [PendonorController::class, 'create'])->name('admin.pendonor.create');
    Route::post('pendonor', [PendonorController::class, 'store'])->name('admin.pendonor.store');
    Route::get('pendonor/{pendonor}', [PendonorController::class, 'show'])->name('admin.pendonor.show');
    Route::get('pendonor/{pendonor}/ubah', [PendonorController::class, 'edit'])->name('admin.pendonor.edit');
    Route::put('pendonor/{pendonor}', [PendonorController::class, 'update'])->name('admin.pendonor.update');
    Route::delete('pendonor/{pendonor}', [PendonorController::class, 'destroy'])->name('admin.pendonor.destroy');
    Route::post('pendonor/{pendonor}/donasi', [PendonorController::class, 'catatDonasi'])->name('admin.pendonor.donasi');
    Route::put('pendonor/{pendonor}/donasi/{donasi}', [PendonorController::class, 'ubahDonasi'])->name('admin.pendonor.donasi.update');
    Route::delete('pendonor/{pendonor}/donasi/{donasi}', [PendonorController::class, 'hapusDonasi'])->name('admin.pendonor.donasi.destroy');
    Route::post('pendonor/{pendonor}/pemeriksaan', [PendonorController::class, 'periksa'])->name('admin.pendonor.pemeriksaan');

    // Instansi (PMI dan rumah sakit), akun pengguna
    Route::resource('instansi', InstansiController::class)->except(['show']);

    Route::resource('users', UserController::class)->except(['show']);
    Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::patch('users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
});
