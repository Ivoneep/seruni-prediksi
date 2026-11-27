# SERU.NI Prediksi Penjualan (Laravel)

Website prediksi penjualan Seru.ni: desain dari `Website-Seru_ni`, susunan halaman dari `bangkalan-health-analytics`, isi dari notebook `DATA_MINING`.

| Halaman | Isi |
|---|---|
| Beranda | Ringkasan data, menu terlaris, menu yang diproyeksi naik/turun |
| Eksplorasi Data | Ringkasan, **diagram batang penjualan harian per titik** (pilih lokasi + menu), per menu, per lokasi, pola mingguan, stok habis |
| **Prediksi Penjualan** | Pilih lokasi + menu: grafik aktual vs prediksi + proyeksi 7 hari, stempel **Naik/Turun/Stabil**, **cek tanggal tertentu** (aktual naik/turun, prediksi tepat/meleset), rekomendasi stok |
| Perbandingan Model | Pembagian data, evaluasi latih/validasi/uji, lawan pembanding sederhana |
| Insight & Kesimpulan | Temuan (dihitung otomatis dari database), tanya jawab, keterbatasan |

Teknologi: Laravel 11/12 (route, controller, model, migration, command), Blade, HTML/CSS/JS (grafik SVG buatan sendiri, tanpa library), SQLite (bawaan) atau MySQL.

## Pasang (butuh PHP 8.2+, Composer, Node tidak perlu)

Folder ini adalah **lapisan di atas proyek Laravel baru**.

```bash
composer create-project laravel/laravel seruni-prediksi
cp -r <folder-ini>/* seruni-prediksi/      # Windows: xcopy /E /Y <folder-ini>\* seruni-prediksi\
cd seruni-prediksi
php artisan migrate
php artisan seruni:import
php artisan serve                          # buka http://127.0.0.1:8000
```

Atau jalankan `install.sh` / `install.bat`: proyek Laravel dibuat di folder `seruni-app` di sebelah folder ini.

Untuk MySQL, ubah `DB_*` di `.env`, buat databasenya, lalu jalankan `php artisan migrate` dan `php artisan seruni:import`.

## Data

`data/datapenjualan_CLEAN_FIX.xlsx` adalah data asli. Hasilnya sudah diekspor ke `storage/app/seruni`. Untuk mengulang (misalnya data berubah):

```bash
pip install pandas numpy scikit-learn openpyxl
python python/export_model.py data/datapenjualan_CLEAN_FIX.xlsx storage/app/seruni
php artisan seruni:import
```

`export_model.py` mengulang pipeline notebook: fitur `Nomor_Hari`, `Harga Satuan`, `Terjual_Hari_Sebelumnya`, `Lokasi`, `Menu`; split kronologis 70:15:15; empat konfigurasi Random Forest dibandingkan di validasi dan yang MAE-nya terkecil dipakai; pembanding lag/rata-rata; proyeksi 7 hari secara rekursif.

## Cara tren naik/turun ditentukan

Rata-rata proyeksi 7 hari dibanding rata-rata 7 hari terakhir data (per catatan). Dianggap naik/turun bila selisihnya >= 10% **dan** >= 0,3 cup/hari; selain itu stabil. Konstanta ada di `app/Services/SeruniData.php`. Rekomendasi stok = prediksi dibulatkan ke atas (seperti notebook).

## Struktur

```
routes/web.php                      5 halaman + /prediksi/data (JSON)
app/Http/Controllers/               Beranda, Eksplorasi, Prediksi, Model, Insight
app/Models/                         SalesRecord, Prediction, ModelMetric
app/Services/SeruniData.php         semua perhitungan (tren, stok, akurasi arah)
app/Console/Commands/ImportSeruni   php artisan seruni:import
database/migrations/                sales_records, predictions, model_metrics
resources/views/                    layout + 5 halaman + kosong (Blade)
public/css|js|img                   desain Seru.ni, grafik SVG, foto menu
python/export_model.py              pipeline notebook -> CSV/JSON
data/                               data penjualan asli
storage/app/seruni/                 hasil export (dibaca seruni:import)
```
