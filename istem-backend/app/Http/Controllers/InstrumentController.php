<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Instrument;

class InstrumentController extends Controller
{
    public function index()
    {
        $instruments = Instrument::with('bookings')->get()->map(function ($inst) {
            $inst->status = $inst->is_available ? 'available' : 'booked';
            return $inst;
        });

        return response()->json($instruments);
    }

    public function show($id)
    {
        $instrument = Instrument::with('bookings')->findOrFail($id);
        return response()->json($instrument);
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

        return response()->json($instrument, 201);
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

        return response()->json($instrument);
    }

    public function delete($id)
    {
        Instrument::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
}