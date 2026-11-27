<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Data penjualan bersih (1 baris = 1 menu, 1 lokasi, 1 tanggal)
        Schema::create('sales_records', function (Blueprint $t) {
            $t->id();
            $t->date('tanggal')->index();
            $t->string('lokasi')->index();
            $t->string('menu')->index();
            $t->string('hari', 10);
            $t->unsignedInteger('stok');
            $t->unsignedInteger('harga');
            $t->unsignedInteger('terjual');
            $t->unsignedInteger('sisa');
            $t->unsignedBigInteger('total_penjualan');
            $t->unique(['tanggal', 'lokasi', 'menu']);
        });

        // Hasil model Random Forest: aktual vs prediksi, plus proyeksi ke depan
        Schema::create('predictions', function (Blueprint $t) {
            $t->id();
            $t->date('tanggal');
            $t->string('lokasi');
            $t->string('menu');
            $t->string('dataset', 12);            // Training | Validation | Testing | Proyeksi
            $t->unsignedInteger('aktual')->nullable();
            $t->decimal('prediksi', 8, 3);
            $t->decimal('baseline', 8, 3)->nullable();
            $t->decimal('stok', 8, 1)->nullable();
            $t->unsignedInteger('harga')->nullable();
            $t->index(['lokasi', 'menu', 'tanggal']);
        });

        // Metrik model disimpan sebagai JSON per kategori
        Schema::create('model_metrics', function (Blueprint $t) {
            $t->id();
            $t->string('kategori')->unique();
            $t->json('payload');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_metrics');
        Schema::dropIfExists('predictions');
        Schema::dropIfExists('sales_records');
    }
};
