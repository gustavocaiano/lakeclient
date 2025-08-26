<?php

namespace GustavoCaiano\Windclient\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $payload
 */
class WindclientState extends Model
{
    protected $table = 'windclient_states';

    protected $fillable = ['payload'];
}
