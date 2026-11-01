# Permintaan RS - cara pasang
Salin isi folder ini ke project (Replace). Lalu:
  php artisan migrate
  php artisan route:clear
  php artisan test --filter=PermintaanRsTest
File yang menimpa file lama: AdminPageController.php, admin/permintaan.blade.php, admin/dashboard.blade.php,
layouts/app.blade.php, routes/web.php. Commit dulu sebelum Replace.
