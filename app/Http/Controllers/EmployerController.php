<?php

namespace App\Http\Controllers;

use App\Mail\EmployerCredentials;
use App\Models\Employer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EmployerController extends Controller
{
    /**
     * Display a listing of the employers.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $employers = Employer::with(['user', 'service'])->get()->map(function ($employer) {
            return [
                'id' => $employer->id,
                'user_id' => $employer->user_id,
                'full_name' => $employer->user->full_name,
                'email' => $employer->user->email,
                'poste' => $employer->poste,
                'phone' => $employer->phone,
                'service' => $employer->service->name,
                'service_id' => $employer->service_id,
                'is_active' => $employer->is_active,
                'created_at' => $employer->created_at,
            ];
        });

        return response()->json([
            'employers' => $employers
        ]);
    }

    /**
     * Store a newly created employer in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'poste' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'service_id' => 'required|exists:services,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check if service exists
        $service = Service::find($request->service_id);
        if (!$service) {
            return response()->json([
                'message' => 'Service not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Generate a random password
        $password = Str::random(10);

        try {
            DB::beginTransaction();

            // Create user
            $user = User::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make($password),
                'role' => 'Employer',
            ]);

            // Create employer
            $employer = Employer::create([
                'user_id' => $user->id,
                'poste' => $request->poste,
                'phone' => $request->phone,
                'service_id' => $request->service_id,
                'is_active' => true,
            ]);

            // Send email with credentials
            Mail::to($user->email)->send(new EmployerCredentials(
                $user->full_name,
                $user->email,
                $password
            ));

            DB::commit();

            return response()->json([
                'message' => 'Employer created successfully',
                'employer' => [
                    'id' => $employer->id,
                    'user_id' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'poste' => $employer->poste,
                    'phone' => $employer->phone,
                    'service' => $service->name,
                    'service_id' => $service->id,
                    'is_active' => $employer->is_active,
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create employer',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified employer.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $employer = Employer::with(['user', 'service'])->find($id);

        if (!$employer) {
            return response()->json([
                'message' => 'Employer not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'employer' => [
                'id' => $employer->id,
                'user_id' => $employer->user_id,
                'full_name' => $employer->user->full_name,
                'email' => $employer->user->email,
                'poste' => $employer->poste,
                'phone' => $employer->phone,
                'service' => $employer->service->name,
                'service_id' => $employer->service_id,
                'is_active' => $employer->is_active,
                'created_at' => $employer->created_at,
            ]
        ]);
    }

    /**
     * Update the specified employer in storage.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $employer = Employer::with('user')->find($id);

        if (!$employer) {
            return response()->json([
                'message' => 'Employer not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $employer->user_id,
            'poste' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|required|string|max:255',
            'service_id' => 'sometimes|required|exists:services,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            DB::beginTransaction();

            // Update user
            if ($request->has('full_name')) {
                $employer->user->full_name = $request->full_name;
            }
            if ($request->has('email')) {
                $employer->user->email = $request->email;
            }
            $employer->user->save();

            // Update employer
            if ($request->has('poste')) {
                $employer->poste = $request->poste;
            }
            if ($request->has('phone')) {
                $employer->phone = $request->phone;
            }
            if ($request->has('service_id')) {
                $employer->service_id = $request->service_id;
            }
            $employer->save();

            DB::commit();

            // Refresh the model to get updated data
            $employer = Employer::with(['user', 'service'])->find($id);

            return response()->json([
                'message' => 'Employer updated successfully',
                'employer' => [
                    'id' => $employer->id,
                    'user_id' => $employer->user_id,
                    'full_name' => $employer->user->full_name,
                    'email' => $employer->user->email,
                    'poste' => $employer->poste,
                    'phone' => $employer->phone,
                    'service' => $employer->service->name,
                    'service_id' => $employer->service_id,
                    'is_active' => $employer->is_active,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update employer',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Toggle the active status of the specified employer.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function toggleActive(string $id): JsonResponse
    {
        $employer = Employer::find($id);

        if (!$employer) {
            return response()->json([
                'message' => 'Employer not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $employer->is_active = !$employer->is_active;
        $employer->save();

        return response()->json([
            'message' => 'Employer status updated successfully',
            'is_active' => $employer->is_active
        ]);
    }

    /**
     * Search for employers by name.
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

        $employers = Employer::with(['user', 'service'])
            ->whereHas('user', function ($query) use ($request) {
                $query->where('full_name', 'LIKE', '%' . $request->name . '%');
            })
            ->get()
            ->map(function ($employer) {
                return [
                    'id' => $employer->id,
                    'user_id' => $employer->user_id,
                    'full_name' => $employer->user->full_name,
                    'email' => $employer->user->email,
                    'poste' => $employer->poste,
                    'phone' => $employer->phone,
                    'service' => $employer->service->name,
                    'service_id' => $employer->service_id,
                    'is_active' => $employer->is_active,
                    'created_at' => $employer->created_at,
                ];
            });

        return response()->json([
            'employers' => $employers
        ]);
    }
}
