<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Booking;

class Instrument extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'category',
        'description',
        'location',
        'image',
        'usage_cost',
        'status'
    ];

    protected $appends = ['image_url', 'is_available'];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'instrument_id');
    }

    public function queues()
    {
        return $this->hasMany(\App\Models\Queue::class, 'instrument_id');
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        return asset('storage/' . $this->image);
    }

    public function getIsAvailableAttribute()
    {
        return !$this->bookings()
            ->where('status', 'approved')
            ->whereDate('end_date', '>=', now())
            ->exists();
    }
}