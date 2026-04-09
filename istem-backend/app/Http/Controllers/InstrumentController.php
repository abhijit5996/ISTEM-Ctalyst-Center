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

    public function import(Request $request)
    {
        // Validate inputs
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120', // 5MB limit
            'images.*' => 'nullable|file|image|mimes:jpg,jpeg,png|max:2048', // 2MB per image
        ]);

        try {
            // Get the CSV file
            $csvFile = $request->file('file');
            $csvPath = $csvFile->getRealPath();
            
            // Map uploaded images by filename
            $uploadedImages = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $uploadedImages[$imageFile->getClientOriginalName()] = $imageFile;
                }
            }

            // Parse CSV
            $successCount = 0;
            $failedRows = [];
            $row = 0;

            if (($handle = fopen($csvPath, 'r')) !== false) {
                // Skip header row
                $header = fgetcsv($handle, 0, ',');
                
                while (($data = fgetcsv($handle, 0, ',')) !== false) {
                    $row++;
                    
                    // Skip empty rows
                    if (empty(array_filter($data))) {
                        continue;
                    }

                    try {
                        // Map CSV columns: instrument_name, category, location, usage_cost, status, description, image
                        $name = isset($data[0]) ? trim($data[0]) : null;
                        $category = isset($data[1]) ? trim($data[1]) : null;
                        $location = isset($data[2]) ? trim($data[2]) : null;
                        $usage_cost = isset($data[3]) ? trim($data[3]) : null;
                        $status = isset($data[4]) ? trim($data[4]) : 'available';
                        $description = isset($data[5]) ? trim($data[5]) : null;
                        $imageName = isset($data[6]) ? trim($data[6]) : null;

                        // Validate required fields
                        if (!$name || !$category) {
                            $failedRows[] = "Row $row: Missing instrument name or category";
                            continue;
                        }

                        // Prepare instrument data
                        $instrumentData = [
                            'name' => $name,
                            'category' => $category,
                            'location' => $location ?? 'TBD',
                            'usage_cost' => $usage_cost ?? '₹0/hour',
                            'status' => $status,
                            'description' => $description ?? '',
                        ];

                        // Handle image if specified
                        if ($imageName && isset($uploadedImages[$imageName])) {
                            $imagePath = $uploadedImages[$imageName]->store('instruments', 'public');
                            $instrumentData['image'] = $imagePath;
                        } elseif ($imageName) {
                            // Image filename in CSV but not uploaded - log warning but continue
                            $failedRows[] = "Row $row: Image '$imageName' not found in uploaded files";
                        }

                        // Use firstOrCreate to avoid duplicates based on name
                        $instrument = Instrument::firstOrCreate(
                            ['name' => $name],
                            $instrumentData
                        );

                        // If it was created (not just found), increment counter
                        if ($instrument->wasRecentlyCreated) {
                            $successCount++;
                        }

                    } catch (\Exception $e) {
                        $failedRows[] = "Row $row: " . $e->getMessage();
                        continue;
                    }
                }
                fclose($handle);
            }

            return response()->json([
                'success' => true,
                'message' => "$successCount instruments imported successfully",
                'imported' => $successCount,
                'warnings' => $failedRows,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 400);
        }
    }
}