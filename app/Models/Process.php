<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    protected $table = 'dcs_progoti.processes';
    protected $fillable = [
        'process',
    ];
}
