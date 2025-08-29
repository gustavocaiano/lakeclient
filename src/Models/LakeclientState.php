<?php

namespace GustavoCaiano\Lakeclient\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $payload
 */
class LakeclientState extends Model
{
    protected $table = 'lakeclient_states';

    protected $fillable = ['payload'];
}
