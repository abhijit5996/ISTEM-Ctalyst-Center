<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Queue;

class QueueController extends Controller
{
    // Add user to queue
    public function store(Request $request)
    {
        $last = Queue::where('instrument_id', $request->instrument_id)
            ->orderBy('position', 'desc')
            ->first();

        $position = $last ? $last->position + 1 : 1;

        $queue = Queue::create([
            'instrument_id' => $request->instrument_id,
            'user_name' => $request->user_name,
            'position' => $position
        ]);

        return response()->json([
            'success' => true,
            'data' => $queue
        ]);
    }

    // Get queue list
    public function index($instrumentId)
    {
        $queue = Queue::where('instrument_id', $instrumentId)
            ->orderBy('position')
            ->get();

        return response()->json($queue);
    }
}