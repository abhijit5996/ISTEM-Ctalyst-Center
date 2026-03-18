<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Instrument;

class InstrumentController extends Controller
{
    public function index()
    {
        return response()->json(Instrument::all());
    }

    public function show($id)
    {
        return response()->json(Instrument::findOrFail($id));
    }

    public function store(Request $request)
    {
        return Instrument::create($request->all());
    }

    public function update(Request $request, $id)
    {
        $instrument = Instrument::findOrFail($id);
        $instrument->update($request->all());
        return $instrument;
    }

    public function delete($id)
    {
        Instrument::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
}