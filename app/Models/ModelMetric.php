<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelMetric extends Model
{
    protected $guarded = [];

    protected $casts = ['payload' => 'array'];

    /** Ambil payload satu kategori, atau nilai bawaan bila belum diimpor. */
    public static function ambil(string $kategori, $default = [])
    {
        return optional(static::where('kategori', $kategori)->first())->payload ?? $default;
    }
}
