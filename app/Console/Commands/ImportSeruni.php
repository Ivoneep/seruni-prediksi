<?php

namespace App\Console\Commands;

use App\Models\ModelMetric;
use App\Models\Prediction;
use App\Models\SalesRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSeruni extends Command
{
    protected $signature = 'seruni:import {folder=storage/app/seruni : Folder hasil export_model.py}';

    protected $description = 'Impor data penjualan, hasil prediksi, dan metrik model dari export_model.py ke database';

    public function handle(): int
    {
        $dir = base_path($this->argument('folder'));
        foreach (['data_penjualan.csv', 'hasil_prediksi.csv', 'metrik_model.json'] as $f) {
            if (!is_file("$dir/$f")) {
                $this->error("File tidak ditemukan: $dir/$f");
                $this->line('Jalankan dulu: python python/export_model.py <file.xlsx> storage/app/seruni');
                return self::FAILURE;
            }
        }

        DB::transaction(function () use ($dir) {
            SalesRecord::query()->delete();
            Prediction::query()->delete();

            $n = 0;
            foreach (array_chunk($this->baca("$dir/data_penjualan.csv"), 300) as $chunk) {
                SalesRecord::insert(array_map(fn ($r) => [
                    'tanggal'         => $r['Tanggal'],
                    'lokasi'          => $r['Lokasi'],
                    'menu'            => $r['Menu Minuman'],
                    'hari'            => $r['Hari'],
                    'stok'            => (int) $r['Stok'],
                    'harga'           => (int) $r['Harga Satuan'],
                    'terjual'         => (int) $r['Terjual'],
                    'sisa'            => (int) $r['Sisa'],
                    'total_penjualan' => (int) $r['Total Penjualan'],
                ], $chunk));
                $n += count($chunk);
            }
            $this->info("Data penjualan: $n baris");

            $m = 0;
            foreach (array_chunk($this->baca("$dir/hasil_prediksi.csv"), 300) as $chunk) {
                Prediction::insert(array_map(fn ($r) => [
                    'tanggal'  => $r['Tanggal'],
                    'lokasi'   => $r['Lokasi'],
                    'menu'     => $r['Menu'],
                    'dataset'  => $r['Dataset'],
                    'aktual'   => $r['Aktual'] === '' ? null : (int) round((float) $r['Aktual']),
                    'prediksi' => (float) $r['Prediksi'],
                    'baseline' => $r['Baseline'] === '' ? null : (float) $r['Baseline'],
                    'stok'     => $r['Stok'] === '' ? null : (float) $r['Stok'],
                    'harga'    => $r['Harga'] === '' ? null : (int) $r['Harga'],
                ], $chunk));
                $m += count($chunk);
            }
            $this->info("Hasil prediksi: $m baris");

            $json = json_decode(file_get_contents("$dir/metrik_model.json"), true);
            foreach ($json as $kategori => $payload) {
                ModelMetric::updateOrCreate(['kategori' => $kategori], ['payload' => is_array($payload) ? $payload : ['nilai' => $payload]]);
            }
            $this->info('Metrik model: ' . count($json) . ' kategori');
        });

        return self::SUCCESS;
    }

    /** Baca CSV berkepala menjadi array asosiatif. */
    private function baca(string $path): array
    {
        $fh = fopen($path, 'r');
        $head = fgetcsv($fh);
        $rows = [];
        while (($line = fgetcsv($fh)) !== false) {
            if (count($line) === count($head)) {
                $rows[] = array_combine($head, $line);
            }
        }
        fclose($fh);
        return $rows;
    }
}
