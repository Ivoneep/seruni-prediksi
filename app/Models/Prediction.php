<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prediction extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'tanggal'  => 'date:Y-m-d',
        'prediksi' => 'float',
        'baseline' => 'float',
        'stok'     => 'float',
    ];
}
