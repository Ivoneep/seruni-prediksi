<?php

use App\Http\Controllers\BerandaController;
use App\Http\Controllers\EksplorasiController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\ModelController;
use App\Http\Controllers\PrediksiController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BerandaController::class, 'index'])->name('beranda');
Route::get('/eksplorasi', [EksplorasiController::class, 'index'])->name('eksplorasi');
Route::get('/prediksi', [PrediksiController::class, 'index'])->name('prediksi');
Route::get('/prediksi/data', [PrediksiController::class, 'data'])->name('prediksi.data');
Route::get('/model', [ModelController::class, 'index'])->name('model');
Route::get('/insight', [InsightController::class, 'index'])->name('insight');
