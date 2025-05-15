<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Intervention;
use App\Models\Equipment;

class InterventionController extends Controller
{
    public function index(Request $request)
    {
        $query = Intervention::with('equipment');
        
        if ($request->has('equipment_id')) {
            $query->where('equipment_id', $request->equipment_id);
        }
        
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'technician_name' => 'required|string',
            'note' => 'required|string',
            'equipment_id' => 'required|exists:equipments,id'
        ]);

        $intervention = Intervention::create($validated);
        return response()->json($intervention, 201);
    }

    public function show(Intervention $intervention)
    {
        return response()->json($intervention->load('equipment'));
    }

    public function update(Request $request, Intervention $intervention)
    {
        $validated = $request->validate([
            'date' => 'sometimes|date',
            'technician_name' => 'sometimes|string',
            'note' => 'sometimes|string',
            'equipment_id' => 'sometimes|exists:equipments,id'
        ]);

        $intervention->update($validated);
        return response()->json($intervention);
    }

    public function destroy(Intervention $intervention)
    {
        $intervention->delete();
        return response()->json(null, 204);
    }

}
