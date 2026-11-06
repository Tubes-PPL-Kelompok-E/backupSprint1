# Input Kantong Darah + Uji Saring - cara pasang
1. Salin app/, database/, resources/, tests/ ke project.
   resources/views/admin/stok.blade.php MENGGANTI versi lama (kini membaca database).
2. routes/web.php: lihat routes-snippet.txt (ganti route admin.stok, tambah route baru).
3. layouts/app.blade.php: menu "Stok Darah" tetap aktif di halaman kantong/uji saring (lihat balasan chat).
4. php artisan migrate
5. php artisan test --filter="KantongDarahTest|UjiSaringTest"
