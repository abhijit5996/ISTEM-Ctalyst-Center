<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Instrument;

class Booking extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'instrument_id',
        'user_type',
        'name',
        'identifier',
        'department',
        'program_or_school',
        'project_title',
        'confidential_project',
        'start_date',
        'end_date',
        'user_email',
        'status'
    ];

    public function instrument()
    {
        return $this->belongsTo(Instrument::class, 'instrument_id');
    }
}