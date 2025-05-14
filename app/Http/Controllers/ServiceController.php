<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

        $service->delete();

        return response()->json([
            'message' => 'Service deleted successfully'
        ]);
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
