<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PopupModel extends Model
{
    protected $table = 'dcs_progoti.popup_models';

    protected $fillable = [
        'label', 'datatype',
    ];

    protected $casts = [
        'captions' => 'array',
        'values' => 'array'
    ];
}
