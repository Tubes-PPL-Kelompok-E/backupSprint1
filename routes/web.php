<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KegiatanDonorController;

Route::get('/', function () {
    return view('welcome');
});
