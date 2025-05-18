<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class MaintenanceController extends Controller
{
    /**
     * Display a listing of the maintenances.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Maintenance::with(['equipment', 'technician']);
        
        // Filter by equipment_id if provided
        if ($request->has('equipment_id')) {
            $query->where('equipment_id', $request->equipment_id);
        }
        
        return response()->json([
            'data' => $query->get()
        ]);
    }

    /**
     * Store a newly created maintenance in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'equipment_id' => 'required|exists:equipments,id',
            'maintenance_type' => 'required|string|max:255',
            'scheduled_date' => 'required|date',
            'performed_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date',
            'observations' => 'nullable|string',
            'technician_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $maintenance = Maintenance::create($validator->validated());

        return response()->json([
            'message' => 'Maintenance created successfully',
            'data' => $maintenance->load(['equipment', 'technician'])
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified maintenance.
     *
     * @param Maintenance $maintenance
     * @return JsonResponse
     */
    public function show(Maintenance $maintenance): JsonResponse
    {
        return response()->json([
            'data' => $maintenance->load(['equipment', 'technician'])
        ]);
    }

    /**
     * Update the specified maintenance in storage.
     *
     * @param Request $request
     * @param Maintenance $maintenance
     * @return JsonResponse
     */
    public function update(Request $request, Maintenance $maintenance): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'equipment_id' => 'sometimes|exists:equipments,id',
            'maintenance_type' => 'sometimes|string|max:255',
            'scheduled_date' => 'sometimes|date',
            'performed_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date',
            'observations' => 'nullable|string',
            'technician_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $maintenance->update($validator->validated());

        return response()->json([
            'message' => 'Maintenance updated successfully',
            'data' => $maintenance->fresh()->load(['equipment', 'technician'])
        ]);
    }

    /**
     * Remove the specified maintenance from storage.
     *
     * @param Maintenance $maintenance
     * @return JsonResponse
     */
    public function destroy(Maintenance $maintenance): JsonResponse
    {
        $maintenance->delete();

        return response()->json([
            'message' => 'Maintenance deleted successfully'
        ]);
    }
}
