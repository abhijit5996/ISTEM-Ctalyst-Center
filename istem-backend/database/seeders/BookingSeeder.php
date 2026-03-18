<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    public function run()
    {
        if (Booking::count() > 0) {
            return;
        }

        Booking::create([
            'id' => 'B0001',
            'instrument_id' => '1',
            'user_type' => 'student',
            'name' => 'Test User',
            'identifier' => 'TU001',
            'department' => 'Physics',
            'program_or_school' => 'Science',
            'project_title' => 'Sample Booking',
            'confidential_project' => false,
            'start_date' => Carbon::now()->toDateString(),
            'end_date' => Carbon::now()->addDay()->toDateString(),
            'status' => 'pending',
        ]);
    }
}
