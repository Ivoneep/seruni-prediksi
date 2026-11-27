<?php

namespace App\Http\Controllers;

use App\Services\SeruniData;

class EksplorasiController extends Controller
{
    public function index()
    {
        $d = SeruniData::dariDatabase();
        if ($d->kosong()) {
            return view('kosong');
        }

        $ringkas = $d->ringkasan();

        return view('eksplorasi', [
            'ringkas'   => $ringkas,
            'perMenu'   => $d->perMenu(),
            'perLokasi' => $d->perLokasi(),
            'perHari'   => $d->perHari(),
            'lokasi'    => $d->lokasiList(),
            'menu'      => $d->menuList(),
            'korelasi'  => $d->korelasiStokTerjual(),
            'titik'     => $d->titikHarian(),
            'rentang'   => [$ringkas['awal'], $ringkas['akhir']],
            'labelMenu' => array_map(fn ($m) => SeruniData::label($m), array_combine($d->menuList(), $d->menuList())),
        ]);
    }
}
