<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    /**
     * Display a listing of the services.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $services = Service::all();

        return response()->json([
            'services' => $services
        ]);
    }

    /**
     * Store a newly created service in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:services',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $service = Service::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Service created successfully',
            'service' => $service
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified service.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $service = Service::find($id);

        if (!$service) {
            return response()->json([
                'message' => 'Service not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'service' => $service
        ]);
    }

    /**
     * Update the specified service in storage.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $service = Service::find($id);

        if (!$service) {
            return response()->json([
                'message' => 'Service not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:services,name,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $service->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Service updated successfully',
            'service' => $service
        ]);
    }

    /**
     * Remove the specified service from storage.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $service = Service::find($id);

        if (!$service) {
            return response()->json([
                'message' => 'Service not found'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            // Use DB transaction to ensure all operations succeed or fail together
            \Illuminate\Support\Facades\DB::beginTransaction();

            // Get all employers associated with this service
            $employers = $service->employers;

            // Delete the service forcefully by first removing the relationship
            if ($employers->count() > 0) {
                // Find a default service to reassign employers to, or create one if none exists
                $defaultService = Service::where('id', '!=', $service->id)->first();

                if (!$defaultService) {
                    // Create a default service if none exists
                    $defaultService = Service::create(['name' => 'Default Service']);
                }

                // Reassign all employers to the default service
                foreach ($employers as $employer) {
                    $employer->service_id = $defaultService->id;
                    $employer->save();
                }
            }

            // Now delete the service
            $service->delete();

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'message' => 'Service deleted successfully'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete service',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Search for services by name.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $services = Service::where('name', 'LIKE', '%' . $request->name . '%')->get();

        return response()->json([
            'services' => $services
        ]);
    }
}
