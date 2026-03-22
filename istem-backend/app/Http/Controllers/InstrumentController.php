<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Instrument;

class InstrumentController extends Controller
{
    public function index()
    {
        $instruments = Instrument::with(['bookings', 'queues'])->get()->map(function ($inst) {
            $inst->status = $inst->is_available ? 'available' : 'booked';

            $inst->waitingQueue = $inst->queues->map(function ($q) {
                return [
                    'user' => $q->user_name,
                    'position' => $q->queue_position,
                ];
            });

            $inst->bookedSlots = $inst->bookings
                ->where('status', 'approved')
                ->map(function ($b) {
                    return [
                        'user' => $b->name,
                        'from' => $b->start_date,
                        'to' => $b->end_date,
                    ];
                });

            return $inst;
        });

        return response()->json([
            'success' => true,
            'data' => $instruments
        ]);
    }

    public function show($id)
    {
        $instrument = Instrument::with(['bookings', 'queues'])->findOrFail($id);
        $instrument->usageCost = $instrument->usage_cost;
        
        return response()->json([
            'success' => true,
            'data' => $instrument
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'required|string|max:255',
            'usage_cost' => 'nullable|string|max:255',
            'status' => 'nullable|in:available,active,booked,blocked,limited',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // ✅ FIX: AUTO UNIQUE ID (NO DUPLICATE BUG)
        $validated['id'] = 'INS' . strtoupper(uniqid());

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('instruments', 'public');
            $validated['image'] = $path;
        }

        $instrument = Instrument::create($validated);

        return response()->json([
            'success' => true,
            'data' => $instrument
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $instrument = Instrument::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'location' => 'sometimes|string|max:255',
            'usage_cost' => 'nullable|string|max:255',
            'status' => 'nullable|in:available,active,booked,blocked,limited',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('instruments', 'public');
            $validated['image'] = $path;
        }

        $instrument->update($validated);
        $instrument->refresh();

        return response()->json([
            'success' => true,
            'data' => $instrument
        ]);
    }

    public function delete($id)
    {
        Instrument::destroy($id);
        
        return response()->json([
            'success' => true,
            'message' => 'Instrument deleted successfully'
        ]);
    }
}