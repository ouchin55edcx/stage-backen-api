<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Equipment::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'data' => $query->with('employer')->get()
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'nsc' => 'required|string|max:255',
            'status' => ['required', Rule::in(['active', 'on_hold', 'in_progress'])],
            'ip_address' => 'required|string|max:255',
            'serial_number' => 'required|string|max:255',
            'processor' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'office_version' => 'required|string|max:255',
            'label' => 'required|string|max:255',
            'backup_enabled' => 'sometimes|boolean',
            'employer_id' => 'required|exists:employers,id'
        ]);

        // Set backup_enabled to false by default if not provided
        $validatedData['backup_enabled'] = $validatedData['backup_enabled'] ?? false;

        $equipment = Equipment::create($validatedData);

        return response()->json([
            'message' => 'Equipment created successfully',
            'data' => $equipment->load('employer')
        ], 201);
    }

    public function show(Equipment $equipment)
    {
        return response()->json([
            'data' => $equipment->load('employer')
        ]);
    }

    public function update(Request $request, Equipment $equipment)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|max:255',
            'nsc' => 'sometimes|string|max:255',
            'status' => ['sometimes', Rule::in(['active', 'on_hold', 'in_progress'])],
            'ip_address' => 'sometimes|string|max:255',
            'serial_number' => 'sometimes|string|max:255',
            'processor' => 'sometimes|string|max:255',
            'brand' => 'sometimes|string|max:255',
            'office_version' => 'sometimes|string|max:255',
            'label' => 'sometimes|string|max:255',
            'backup_enabled' => 'sometimes|boolean',
            'employer_id' => 'sometimes|exists:employers,id'
        ]);

        $equipment->update($validatedData);

        return response()->json([
            'message' => 'Equipment updated successfully',
            'data' => $equipment->load('employer')
        ]);
    }

    public function destroy(Equipment $equipment)
    {
        $equipment->delete();

        return response()->json([
            'message' => 'Equipment deleted successfully'
        ]);
    }
}
