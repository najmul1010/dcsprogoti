<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loans extends Model
{
    protected $table = 'dcs_progoti.loans';
    protected $guarded = ['updated_at',];

    protected $casts = [
        'DynamicFieldValue' => 'array'
    ];
}
