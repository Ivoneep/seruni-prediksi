<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesRecord extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['tanggal' => 'date:Y-m-d'];
}
