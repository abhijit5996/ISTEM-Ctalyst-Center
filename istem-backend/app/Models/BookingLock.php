<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingLock extends Model
{
    protected $fillable = [
        'instrument_id',
        'start_date',
        'end_date',
        'email',
        'expires_at',
    ];

    protected $dates = ['expires_at'];
}
