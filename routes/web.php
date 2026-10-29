<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Auth::check()
    ? redirect()->route(Auth::user()->homeRoute())
    : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');

    // Registrasi (dibatasi 5 percobaan per menit per IP)
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:5,1')->name('register.store');

    // Lupa kata sandi
    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'send'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'update'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Pengalihan halaman sesuai peran
Route::middleware('auth')->group(function () {
    Route::view('/admin/dashboard', 'dashboard', ['title' => 'Dashboard Admin PMI'])
        ->middleware('role:admin_pmi')->name('admin.dashboard');

    Route::view('/petugas/dashboard', 'dashboard', ['title' => 'Dashboard Petugas RS'])
        ->middleware('role:petugas_rs')->name('petugas.dashboard');

    Route::view('/pendonor/dashboard', 'dashboard', ['title' => 'Dashboard Pendonor'])
        ->middleware('role:pendonor')->name('pendonor.dashboard');
});

// Manajemen akun (hanya Admin PMI)
Route::middleware(['auth', 'role:admin_pmi'])->prefix('admin')->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
    Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::patch('users/{id}/restore', [UserController::class, 'restore'])->name('users.restore');
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