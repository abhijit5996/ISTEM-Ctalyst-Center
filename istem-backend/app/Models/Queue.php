<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
    'id', 'instrument_id', 'booking_id', 'user_id', 'user_name', 'email', 'queue_position', 'date', 'time_slot', 'status'
    ];

  public function instrument()
  {
    return $this->belongsTo(\App\Models\Instrument::class, 'instrument_id');
  }
}