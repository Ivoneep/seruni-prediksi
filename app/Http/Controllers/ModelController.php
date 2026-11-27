<?php

namespace App\Http\Controllers;

use App\Models\ModelMetric;

class ModelController extends Controller
{
    public function index()
    {
        $akhir = ModelMetric::ambil('evaluasi_akhir');
        if (!$akhir) {
            return view('kosong');
        }

        return view('model', [
            'akhir'      => $akhir,
            'pembanding' => ModelMetric::ambil('pembanding'),
            'split'      => ModelMetric::ambil('split'),
            'periode'    => ModelMetric::ambil('periode'),
            'fitur'      => ModelMetric::ambil('fitur'),
            'model'      => ModelMetric::ambil('model')['nilai'] ?? '',
        ]);
    }
}
