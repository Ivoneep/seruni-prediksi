<?php

namespace App\Http\Controllers;

use App\Models\ModelMetric;
use App\Services\SeruniData as D;

class InsightController extends Controller
{
    public function index()
    {
        $d = D::dariDatabase();
        $akhir = ModelMetric::ambil('evaluasi_akhir');
        if ($d->kosong() || !$akhir) {
            return view('kosong');
        }

        $r = $d->ringkasan();
        $perMenu = $d->perMenu();
        $perHari = $d->perHari();
        $korelasi = $d->korelasiStokTerjual();
        $tren = $d->trenPerMenu();
        $pembanding = ModelMetric::ambil('pembanding');

        $f1 = fn ($v) => number_format($v, 1, ',', '.');
        $f2 = fn ($v) => number_format($v, 2, ',', '.');
        $f0 = fn ($v) => number_format($v, 0, ',', '.');

        // ---- temuan utama ----
        $teratas = reset($perMenu);
        $rataHari = array_map(fn ($h) => $h['rata'], $perHari);
        $ada = array_filter($perHari, fn ($h) => $h['n'] > 0);
        $rataAda = array_map(fn ($h) => $h['rata'], $ada);
        $hariTinggi = array_search(max($rataHari), $rataHari);
        $hariRendah = array_search(min($rataAda), $rataAda);
        $sering = $perMenu;
        uasort($sering, fn ($a, $b) => $b['pct_habis'] <=> $a['pct_habis']);
        $palingHabis = reset($sering);

        $uji = array_values(array_filter($pembanding, fn ($p) => $p['Dataset'] === 'Testing'));
        $rf = null;
        $lain = [];
        foreach ($uji as $p) {
            if ($p['Model'] === 'Random Forest') {
                $rf = $p;
            } else {
                $lain[] = $p;
            }
        }
        $terbaikLain = $lain ? array_reduce($lain, fn ($c, $p) => $c === null || $p['MAE'] < $c['MAE'] ? $p : $c) : null;
        if ($rf && $terbaikLain) {
            $unggul = $rf['MAE'] < $terbaikLain['MAE'];
            $tipis = $unggul && (($terbaikLain['MAE'] - $rf['MAE']) / max($terbaikLain['MAE'], 0.0001)) < 0.05;
            $kalimatModel = $tipis
                ? 'Pada data uji, Random Forest (' . $f2($rf['MAE']) . ' cup) nyaris sama dengan pembanding sederhana "' . $terbaikLain['Model'] . '" (' . $f2($terbaikLain['MAE']) . ' cup). Selisihnya terlalu kecil untuk disimpulkan lebih baik.'
                : ($unggul
                ? 'Pada data uji, Random Forest punya selisih rata-rata ' . $f2($rf['MAE']) . ' cup, lebih kecil dari pembanding terbaik ("' . $terbaikLain['Model'] . '", ' . $f2($terbaikLain['MAE']) . ' cup).'
                : 'Pada data uji, Random Forest (' . $f2($rf['MAE']) . ' cup) belum lebih baik dari pembanding sederhana "' . $terbaikLain['Model'] . '" (' . $f2($terbaikLain['MAE']) . ' cup). Dengan data sependek ini, tebakan rata-rata sudah sulit dikalahkan.');
        } else {
            $kalimatModel = 'Perbandingan dengan pembanding belum tersedia.';
        }

        $naik = count(array_filter($tren, fn ($t) => $t['arah'] === 'Naik'));
        $turun = count(array_filter($tren, fn ($t) => $t['arah'] === 'Turun'));

        $temuan = [
            ['Satu menu menopang penjualan',
             $teratas['label'] . ' menyumbang ' . $f1($teratas['total'] / max($r['terjual'], 1) * 100) . '% dari seluruh cup terjual. Model paling banyak belajar dari menu ini; menu lain jauh lebih jarang terjual, sehingga selisih satu cup sudah besar secara persentase.'],
            ['Ada pola mingguan',
             "$hariTinggi paling ramai (rata-rata " . $f0($perHari[$hariTinggi]['rata']) . " cup per hari, semua lokasi) dan $hariRendah paling sepi (" . $f0($perHari[$hariRendah]['rata']) . ' cup). Hari dalam minggu karena itu menjadi fitur model.'],
            ['Stok menyensor penjualan',
             'Korelasi stok dan terjual ' . $f2($korelasi) . '. ' . $palingHabis['label'] . ' paling sering kehabisan (' . $f0($palingHabis['pct_habis']) . '% catatan), artinya permintaan aslinya mungkin lebih tinggi dari yang tercatat.'],
            ['Model dibandingkan dengan tebakan sederhana', $kalimatModel],
            ['Proyeksi minggu ini',
             "$naik menu diproyeksi naik, $turun turun, dan " . (count($tren) - $naik - $turun) . ' stabil (semua lokasi digabung, 7 hari ke depan).'],
        ];

        // ---- tanya jawab ----
        $ujiAkhir = null;
        foreach ($akhir as $a) {
            if ($a['Dataset'] === 'Testing') {
                $ujiAkhir = $a;
            }
        }

        $tanya = [
            ['Apakah penjualan harian bisa diprediksi?',
             $ujiAkhir
                ? 'Sebagian. Pada data uji, R² model ' . $f2($ujiAkhir['R2']) . ' dan selisih rata-rata ' . $f2($ujiAkhir['MAE']) . ' cup per baris. Total harian cukup terbaca, tetapi menu yang hanya terjual 0 sampai 3 cup per hari sulit ditebak tepat.'
                : 'Belum ada hasil uji.'],
            ['Bagaimana memakai hasilnya untuk menyiapkan stok?',
             'Lihat kartu "Rekomendasi stok" di halaman Prediksi. Kebutuhan dihitung dari prediksi dibulatkan ke atas, lalu dibandingkan dengan stok biasa. Anggap sebagai patokan awal, lalu sesuaikan dengan keadaan di lapangan seperti cuaca atau acara di sekitar titik jualan.'],
            ['Apakah tanda naik atau turun bisa diandalkan?',
             'Gunakan sebagai arah kasar. Penentuannya memakai ambang 10% dan 0,3 cup per hari, dan proyeksi 7 hari disusun berurutan sehingga makin jauh makin tidak pasti. Untuk kombinasi dengan sedikit perubahan, halaman Prediksi menandai tingkat ketepatan arahnya sebagai indikatif.'],
        ];

        $batas = [
            'Data hanya mencakup ' . $r['hari'] . ' hari (' . D::tglPendek($r['awal']) . ' sampai ' . D::tglPendek($r['akhir']) . '), jadi pola bulanan, musim, dan hari libur belum terlihat.',
            'Tidak semua lokasi tercatat setiap hari. Tanggal yang tidak tercatat bukan penjualan nol, dan proyeksi mengandaikan lokasi buka setiap hari.',
            'Angka terjual dibatasi stok. Saat stok habis, permintaan sebenarnya tidak terlihat dan cenderung ditaksir terlalu rendah.',
            'Fitur yang dipakai hanya hari, harga, penjualan sebelumnya, lokasi, dan menu. Cuaca, promosi, dan kegiatan di sekitar lokasi belum dimasukkan.',
            'Proyeksi ke depan memakai prediksi hari sebelumnya sebagai masukan, sehingga kesalahan menumpuk pada hari-hari akhir.',
        ];

        return view('insight', compact('temuan', 'tanya', 'batas'));
    }
}
