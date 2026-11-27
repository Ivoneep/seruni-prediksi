<?php

namespace App\Services;

use App\Models\Prediction;
use App\Models\SalesRecord;

/**
 * Semua perhitungan untuk halaman web. Bekerja di atas array biasa
 * (bukan query per halaman) karena datanya kecil: ~1.200 baris penjualan
 * dan ~3.000 baris prediksi.
 */
class SeruniData
{
    public const MENU = [
        'Kopi Susu'         => ['label' => 'Kopi Susu',         'img' => 'kopi-susu',         'kat' => 'Coffee'],
        'Kopi Aren'         => ['label' => 'Kopi Aren',         'img' => 'kopi-aren',         'kat' => 'Coffee'],
        'Kopi Butterscotch' => ['label' => 'Kopi Butterscotch', 'img' => 'kopi-butterscotch', 'kat' => 'Coffee'],
        'Kopi Caramel'      => ['label' => 'Kopi Caramel',      'img' => 'kopi-caramel',      'kat' => 'Coffee'],
        'Americano'         => ['label' => 'Americano',         'img' => 'americano',         'kat' => 'Coffee'],
        'A. Americano'      => ['label' => 'Apple Americano',   'img' => 'a-americano',       'kat' => 'Coffee'],
        'Chocolate'         => ['label' => 'Coklat',            'img' => 'chocolate',         'kat' => 'Non Coffee'],
        'Lychee tea'        => ['label' => 'Lychee Tea',        'img' => 'lychee-tea',        'kat' => 'Non Coffee'],
        'Matcha'            => ['label' => 'Matcha',            'img' => 'matcha',            'kat' => 'Non Coffee'],
    ];

    public const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    private const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /** Ambang arah: perubahan harus >= 10% DAN >= 0,3 unit agar tidak dianggap noise. */
    private const AMBANG_PERSEN = 10;
    private const AMBANG_UNIT = 0.3;

    private array $sales;
    private array $preds;
    private array $indexSales = [];
    private array $indexPreds = [];
    private string $akhir = '';

    public function __construct(array $sales, array $preds)
    {
        $this->sales = $sales;
        $this->preds = $preds;
        foreach ($sales as $r) {
            $this->indexSales[$r['lokasi']][$r['menu']][] = $r;
            $this->akhir = max($this->akhir, $r['tanggal']);
        }
        foreach ($preds as $r) {
            $this->indexPreds[$r['lokasi']][$r['menu']][] = $r;
        }
        foreach ($this->indexPreds as $l => $menus) {
            foreach ($menus as $m => $rows) {
                usort($rows, fn ($a, $b) => strcmp($a['tanggal'], $b['tanggal']));
                $this->indexPreds[$l][$m] = $rows;
            }
        }
    }

    public static function dariDatabase(): self
    {
        $ke_array = fn ($m) => $m::query()->get()->map(function ($r) {
            $a = $r->getAttributes();
            $a['tanggal'] = substr((string) $a['tanggal'], 0, 10);
            return $a;
        })->all();

        return new self($ke_array(SalesRecord::class), $ke_array(Prediction::class));
    }

    public function kosong(): bool
    {
        return count($this->sales) === 0;
    }

    // ---------- label & format ----------
    public static function label(string $menu): string
    {
        return self::MENU[$menu]['label'] ?? $menu;
    }

    public static function gambar(string $menu): string
    {
        return 'img/menu/' . (self::MENU[$menu]['img'] ?? 'kopi-susu') . '.png';
    }

    public static function hari(string $tgl): string
    {
        return self::HARI[(int) date('N', strtotime($tgl)) - 1];
    }

    public static function tglPendek(string $tgl): string
    {
        $t = strtotime($tgl);
        return (int) date('j', $t) . ' ' . self::BULAN[(int) date('n', $t) - 1];
    }

    public static function tglPanjang(string $tgl): string
    {
        return self::hari($tgl) . ', ' . self::tglPendek($tgl) . ' ' . date('Y', strtotime($tgl));
    }

    public static function rp(float $n): string
    {
        return 'Rp ' . number_format($n, 0, ',', '.');
    }

    // ---------- daftar pilihan ----------
    public function lokasiList(): array
    {
        $l = array_keys($this->indexSales);
        sort($l);
        return $l;
    }

    /** Lokasi dengan catatan terbanyak: pilihan bawaan yang datanya paling kaya. */
    public function lokasiUtama(): string
    {
        $n = [];
        foreach ($this->indexSales as $l => $menus) {
            $n[$l] = array_sum(array_map('count', $menus));
        }
        arsort($n);
        return (string) array_key_first($n);
    }

    /** Menu diurutkan dari yang paling laku. */
    public function menuList(): array
    {
        $tot = [];
        foreach ($this->sales as $r) {
            $tot[$r['menu']] = ($tot[$r['menu']] ?? 0) + $r['terjual'];
        }
        arsort($tot);
        return array_keys($tot);
    }

    // ---------- eksplorasi ----------
    public function ringkasan(): array
    {
        $tgl = array_unique(array_column($this->sales, 'tanggal'));
        sort($tgl);
        return [
            'baris'   => count($this->sales),
            'hari'    => count($tgl),
            'lokasi'  => count($this->indexSales),
            'menu'    => count(array_unique(array_column($this->sales, 'menu'))),
            'terjual' => array_sum(array_column($this->sales, 'terjual')),
            'omzet'   => array_sum(array_column($this->sales, 'total_penjualan')),
            'awal'    => $tgl[0] ?? '',
            'akhir'   => $tgl ? end($tgl) : '',
        ];
    }

    /** Penjualan harian tiap titik (lokasi x menu): [lokasi][menu] = [tanggal => terjual]. */
    public function titikHarian(): array
    {
        $o = [];
        foreach ($this->indexSales as $l => $menus) {
            foreach ($menus as $m => $rows) {
                foreach ($rows as $r) {
                    $o[$l][$m][$r['tanggal']] = (int) $r['terjual'];
                }
            }
        }
        return $o;
    }

    public function perMenu(): array
    {
        $o = [];
        foreach ($this->sales as $r) {
            $m = $r['menu'];
            $o[$m] ??= ['menu' => $m, 'label' => self::label($m), 'total' => 0, 'n' => 0, 'habis' => 0, 'stok' => 0, 'omzet' => 0];
            $o[$m]['total'] += $r['terjual'];
            $o[$m]['n']++;
            $o[$m]['habis'] += $r['sisa'] == 0 ? 1 : 0;
            $o[$m]['stok'] += $r['stok'];
            $o[$m]['omzet'] += $r['total_penjualan'];
        }
        foreach ($o as &$v) {
            $v['rata'] = $v['n'] ? $v['total'] / $v['n'] : 0;
            $v['rata_stok'] = $v['n'] ? $v['stok'] / $v['n'] : 0;
            $v['pct_habis'] = $v['n'] ? $v['habis'] / $v['n'] * 100 : 0;
        }
        unset($v);
        uasort($o, fn ($a, $b) => $b['total'] <=> $a['total']);
        return $o;
    }

    public function perLokasi(): array
    {
        $o = [];
        foreach ($this->sales as $r) {
            $l = $r['lokasi'];
            $o[$l] ??= ['lokasi' => $l, 'total' => 0, 'omzet' => 0, 'hari' => []];
            $o[$l]['total'] += $r['terjual'];
            $o[$l]['omzet'] += $r['total_penjualan'];
            $o[$l]['hari'][$r['tanggal']] = true;
        }
        foreach ($o as &$v) {
            $v['n_hari'] = count($v['hari']);
            $v['rata_hari'] = $v['n_hari'] ? $v['total'] / $v['n_hari'] : 0;
            unset($v['hari']);
        }
        unset($v);
        uasort($o, fn ($a, $b) => $b['total'] <=> $a['total']);
        return $o;
    }

    public function harian(): array
    {
        $o = [];
        foreach ($this->sales as $r) {
            $o[$r['tanggal']] = ($o[$r['tanggal']] ?? 0) + $r['terjual'];
        }
        ksort($o);
        $out = [];
        foreach ($o as $t => $v) {
            $out[] = ['tanggal' => $t, 'hari' => self::hari($t), 'total' => $v];
        }
        return $out;
    }

    public function perHari(): array
    {
        $per = [];
        foreach ($this->harian() as $h) {
            $per[$h['hari']][] = $h['total'];
        }
        $o = [];
        foreach (self::HARI as $h) {
            $v = $per[$h] ?? [];
            $o[$h] = ['n' => count($v), 'rata' => $v ? array_sum($v) / count($v) : 0, 'total' => array_sum($v)];
        }
        return $o;
    }

    public function heatmap(): array
    {
        $o = [];
        foreach ($this->sales as $r) {
            $o[$r['menu']][$r['lokasi']] = ($o[$r['menu']][$r['lokasi']] ?? 0) + $r['terjual'];
        }
        $out = [];
        foreach ($this->menuList() as $m) {
            foreach ($this->lokasiList() as $l) {
                $out[$m][$l] = $o[$m][$l] ?? 0;
            }
        }
        return $out;
    }

    public function korelasiStokTerjual(): float
    {
        $x = array_column($this->sales, 'stok');
        $y = array_column($this->sales, 'terjual');
        $n = count($x);
        if ($n < 2) {
            return 0.0;
        }
        $mx = array_sum($x) / $n;
        $my = array_sum($y) / $n;
        $sxy = $sxx = $syy = 0;
        for ($i = 0; $i < $n; $i++) {
            $sxy += ($x[$i] - $mx) * ($y[$i] - $my);
            $sxx += ($x[$i] - $mx) ** 2;
            $syy += ($y[$i] - $my) ** 2;
        }
        return ($sxx > 0 && $syy > 0) ? $sxy / sqrt($sxx * $syy) : 0.0;
    }

    // ---------- prediksi per lokasi + menu ----------
    private function salesRows(string $lokasi, string $menu): array
    {
        $rows = $this->indexSales[$lokasi][$menu] ?? [];
        usort($rows, fn ($a, $b) => strcmp($a['tanggal'], $b['tanggal']));
        return $rows;
    }

    /** Rata-rata terjual per hari tercatat pada 7 hari terakhir data. */
    private function acuan7(string $lokasi, string $menu): float
    {
        $batas = date('Y-m-d', strtotime($this->akhir . ' -6 days'));
        $rows = array_filter($this->salesRows($lokasi, $menu), fn ($r) => $r['tanggal'] >= $batas);
        if (!$rows) {
            $rows = $this->salesRows($lokasi, $menu);
        }
        return $rows ? array_sum(array_column($rows, 'terjual')) / count($rows) : 0.0;
    }

    private function proyeksi(string $lokasi, string $menu): array
    {
        return array_values(array_filter($this->indexPreds[$lokasi][$menu] ?? [], fn ($r) => $r['dataset'] === 'Proyeksi'));
    }

    public static function arahTren(float $acuan, float $proyeksi): array
    {
        $selisih = $proyeksi - $acuan;
        $persen = $acuan > 0 ? $selisih / $acuan * 100 : 0;
        $arah = 'Stabil';
        if (abs($selisih) >= self::AMBANG_UNIT && abs($persen) >= self::AMBANG_PERSEN) {
            $arah = $selisih > 0 ? 'Naik' : 'Turun';
        }
        return ['arah' => $arah, 'persen' => round($persen, 1), 'selisih' => round($selisih, 2),
                'acuan' => round($acuan, 2), 'proyeksi' => round($proyeksi, 2)];
    }

    /** Seri untuk grafik: aktual (data asli) + prediksi model + proyeksi. */
    public function seri(string $lokasi, string $menu): array
    {
        $titik = [];
        foreach ($this->salesRows($lokasi, $menu) as $r) {
            $titik[$r['tanggal']] = ['tanggal' => $r['tanggal'], 'dataset' => 'Awal', 'aktual' => (int) $r['terjual'],
                'prediksi' => null, 'baseline' => null, 'stok' => (int) $r['stok'], 'sisa' => (int) $r['sisa'], 'harga' => (int) $r['harga']];
        }
        foreach ($this->indexPreds[$lokasi][$menu] ?? [] as $p) {
            $t = $p['tanggal'];
            $titik[$t] ??= ['tanggal' => $t, 'aktual' => null, 'stok' => null, 'sisa' => null, 'harga' => $p['harga']];
            $titik[$t]['dataset'] = $p['dataset'];
            $titik[$t]['prediksi'] = round((float) $p['prediksi'], 2);
            $titik[$t]['baseline'] = $p['baseline'] === null ? null : (float) $p['baseline'];
            if ($p['dataset'] === 'Proyeksi') {
                $titik[$t]['stok'] = $p['stok'] === null ? null : (float) $p['stok'];
            }
        }
        ksort($titik);
        foreach ($titik as $t => &$v) {
            $v['hari'] = self::hari($t);
            $v['label'] = self::tglPendek($t);
        }
        unset($v);
        return array_values($titik);
    }

    public function ringkasanKombinasi(string $lokasi, string $menu): array
    {
        $proy = $this->proyeksi($lokasi, $menu);
        $acuan = $this->acuan7($lokasi, $menu);
        $rataProy = $proy ? array_sum(array_column($proy, 'prediksi')) / count($proy) : 0.0;
        $tren = self::arahTren($acuan, $rataProy);

        // rekomendasi stok = prediksi dibulatkan ke atas (seperti notebook)
        $stokBiasa = 0.0;
        $rows = array_slice($this->salesRows($lokasi, $menu), -7);
        if ($rows) {
            $stokBiasa = array_sum(array_column($rows, 'stok')) / count($rows);
        }
        $kebutuhan = array_map(fn ($p) => (int) ceil((float) $p['prediksi']), $proy);
        $puncak = $kebutuhan ? max($kebutuhan) : 0;
        $hariPuncak = '';
        foreach ($proy as $i => $p) {
            if ($kebutuhan[$i] === $puncak) {
                $hariPuncak = self::hari($p['tanggal']);
                break;
            }
        }
        $stok = [
            'biasa'       => round($stokBiasa, 1),
            'puncak'      => $puncak,
            'hari_puncak' => $hariPuncak,
            'status'      => $puncak > ceil($stokBiasa) ? 'Tambah stok' : 'Stok cukup',
        ];

        // akurasi model pada kombinasi ini
        $nAcuan = count(array_filter($this->salesRows($lokasi, $menu), fn ($r) => $r['tanggal'] >= date('Y-m-d', strtotime($this->akhir . ' -6 days'))));
        $tren['n_acuan'] = $nAcuan;

        $akurasi = [];
        foreach (['Training', 'Validation', 'Testing'] as $ds) {
            $e = [];
            foreach ($this->indexPreds[$lokasi][$menu] ?? [] as $p) {
                if ($p['dataset'] === $ds && $p['aktual'] !== null) {
                    $e[] = abs($p['aktual'] - $p['prediksi']);
                }
            }
            $akurasi[$ds] = ['n' => count($e), 'mae' => $e ? round(array_sum($e) / count($e), 2) : null];
        }

        // kesesuaian arah (Validation + Testing): arah aktual vs penjualan sebelumnya,
        // dibandingkan dengan arah prediksi (dibulatkan) vs penjualan sebelumnya
        $naikTurun = 0;
        $benar = 0;
        foreach ($this->indexPreds[$lokasi][$menu] ?? [] as $p) {
            if (!in_array($p['dataset'], ['Validation', 'Testing'], true) || $p['aktual'] === null || $p['baseline'] === null) {
                continue;
            }
            $aktual = $p['aktual'] <=> $p['baseline'];
            if ($aktual === 0) {
                continue;
            }
            $naikTurun++;
            if (((int) round($p['prediksi']) <=> $p['baseline']) === $aktual) {
                $benar++;
            }
        }
        $arah = ['n' => $naikTurun, 'benar' => $benar, 'pct' => $naikTurun ? round($benar / $naikTurun * 100) : null,
                 'andal' => $naikTurun >= 8];

        return compact('tren', 'stok', 'akurasi', 'arah') + ['hari_proyeksi' => count($proy)];
    }

    /** Tren per menu, dijumlahkan dari semua lokasi (asumsi buka setiap hari di kedua sisi). */
    public function trenPerMenu(): array
    {
        $out = [];
        foreach ($this->menuList() as $m) {
            $acuan = $proy = 0.0;
            foreach ($this->lokasiList() as $l) {
                $p = $this->proyeksi($l, $m);
                if (!$p) {
                    continue;
                }
                $acuan += $this->acuan7($l, $m);
                $proy += array_sum(array_column($p, 'prediksi')) / count($p);
            }
            $out[$m] = self::arahTren($acuan, $proy) + ['menu' => $m, 'label' => self::label($m)];
        }
        return $out;
    }

    public function akhirData(): string
    {
        return $this->akhir;
    }
}
