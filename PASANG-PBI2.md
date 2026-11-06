# PBI-02 Kelola Instansi - cara pasang
1. Salin app/, database/, resources/, tests/ ke project (semua file baru, kecuali DatabaseSeeder.php yang diperbarui).
2. routes/web.php: tambah `use App\Http\Controllers\Admin\InstansiController;` dan di grup admin:
   Route::resource('instansi', InstansiController::class)->except(['show']);
3. layouts/app.blade.php: tambah tautan "Data Instansi" di dropdown akun (lihat balasan chat).
4. php artisan migrate && php artisan db:seed
5. php artisan test --filter=InstansiTest
