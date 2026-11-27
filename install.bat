@echo off
rem Membuat proyek Laravel di folder SEBELAH folder ini (bukan di dalamnya), lalu menyalin file website.
set HERE=%~dp0
set APP=%HERE%..\seruni-app
call composer create-project laravel/laravel "%APP%"
xcopy /E /Y /I "%HERE%app" "%APP%\app"
xcopy /E /Y /I "%HERE%data" "%APP%\data"
xcopy /E /Y /I "%HERE%database" "%APP%\database"
xcopy /E /Y /I "%HERE%public" "%APP%\public"
xcopy /E /Y /I "%HERE%python" "%APP%\python"
xcopy /E /Y /I "%HERE%resources" "%APP%\resources"
xcopy /E /Y /I "%HERE%routes" "%APP%\routes"
xcopy /E /Y /I "%HERE%storage" "%APP%\storage"
copy /Y "%HERE%README.md" "%APP%\README.md"
cd /d "%APP%"
php artisan migrate --force
php artisan seruni:import
echo Selesai. Jalankan: cd /d "%APP%" ^&^& php artisan serve
