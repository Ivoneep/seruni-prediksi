<?php

namespace App\Http\Controllers;

use App\Services\SeruniData;

class BerandaController extends Controller
{
    public function index()
    {
        $d = SeruniData::dariDatabase();
        if ($d->kosong()) {
            return view('kosong');
        }

        $tren = $d->trenPerMenu();
        $naik = array_filter($tren, fn ($t) => $t['arah'] === 'Naik');
        $turun = array_filter($tren, fn ($t) => $t['arah'] === 'Turun');
        uasort($naik, fn ($a, $b) => $b['persen'] <=> $a['persen']);
        uasort($turun, fn ($a, $b) => $a['persen'] <=> $b['persen']);

        return view('beranda', [
            'ringkas'  => $d->ringkasan(),
            'perMenu'  => $d->perMenu(),
            'tren'     => $tren,
            'naik'     => array_slice($naik, 0, 3, true),
            'turun'    => array_slice($turun, 0, 3, true),
            'akhir'    => $d->akhirData(),
            'proyeksi' => \App\Models\ModelMetric::ambil('proyeksi'),
        ]);
    }
}
