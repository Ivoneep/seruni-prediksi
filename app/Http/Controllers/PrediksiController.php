<?php

namespace App\Http\Controllers;

use App\Models\ModelMetric;
use App\Services\SeruniData;
use Illuminate\Http\Request;

class PrediksiController extends Controller
{
    public function index(Request $request)
    {
        $d = SeruniData::dariDatabase();
        if ($d->kosong()) {
            return view('kosong');
        }

        $lokasi = $d->lokasiList();
        $menu = $d->menuList();

        return view('prediksi', [
            'lokasi'     => $lokasi,
            'menu'       => $menu,
            'pilihLokasi' => in_array($request->query('lokasi'), $lokasi, true) ? $request->query('lokasi') : $d->lokasiUtama(),
            'pilihMenu'  => in_array($request->query('menu'), $menu, true) ? $request->query('menu') : ($menu[0] ?? ''),
            'split'      => ModelMetric::ambil('split'),
            'proyeksi'   => ModelMetric::ambil('proyeksi'),
        ]);
    }

    /** JSON untuk satu kombinasi lokasi + menu; dipanggil JavaScript saat pilihan berubah. */
    public function data(Request $request)
    {
        $d = SeruniData::dariDatabase();
        $lokasi = (string) $request->query('lokasi');
        $menu = (string) $request->query('menu');

        if (!in_array($lokasi, $d->lokasiList(), true) || !in_array($menu, $d->menuList(), true)) {
            return response()->json(['pesan' => 'Lokasi atau menu tidak dikenal.'], 422);
        }

        $seri = $d->seri($lokasi, $menu);
        $pernahJual = array_sum(array_map(fn ($t) => $t['aktual'] ?? 0, $seri)) > 0;

        return response()->json([
            'lokasi' => $lokasi,
            'menu'   => $menu,
            'label'  => SeruniData::label($menu),
            'gambar' => asset(SeruniData::gambar($menu)),
            'seri'   => $seri,
            'split'  => ModelMetric::ambil('split'),
            'jarang' => !$pernahJual,
        ] + $d->ringkasanKombinasi($lokasi, $menu));
    }
}
