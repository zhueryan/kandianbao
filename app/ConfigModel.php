<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ConfigModel extends Model
{
    protected $table='t_config';

    protected $fillable=[
        'keyword',
        'state',

    ];

    protected $guarded=[];
}
