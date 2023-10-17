<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auth extends Model
{
    protected $table = 'dcs_progoti.auths';
    protected $fillable = [
        'roleId', 'projectcode', 'processId', 'createdBy',
    ];
    protected $casts = [
        'isAuthorized' => 'boolean'
    ];
}
