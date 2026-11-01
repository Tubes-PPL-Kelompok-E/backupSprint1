# Lupa Password - cara pasang
1. Salin folder app/, resources/, tests/ ke project (file baru, tidak menimpa apa pun).
2. Edit routes/web.php: tambahkan 2 baris `use` dan 4 route (lihat balasan chat).
3. Edit resources/views/auth/login.blade.php: ganti href="#" pada "Lupa Password?".
4. php artisan route:list --name=password
5. php artisan test --filter=ForgotPasswordTest
