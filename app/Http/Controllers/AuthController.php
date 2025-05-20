<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle a login request to the application.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if the user is an employer and if they are active
        if ($user->isEmployer()) {
            $employer = $user->employer;

            if (!$employer || !$employer->is_active) {
                return response()->json([
                    'message' => 'Your account has been deactivated. Please contact the administrator.'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // Create a token for the user
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'role' => $user->role,
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Log the user out of the application.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get the authenticated user with detailed profile information.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $userData = $user->only(['id', 'full_name', 'email', 'role']);

        // Add additional profile information based on user role
        if ($user->isEmployer()) {
            $employer = $user->employer()->with('service')->first();
            if ($employer) {
                $userData['profile'] = [
                    'poste' => $employer->poste,
                    'phone' => $employer->phone,
                    'service_id' => $employer->service_id,
                    'service_name' => $employer->service->name ?? null,
                    'is_active' => $employer->is_active,
                ];
            }
        }

        return response()->json([
            'user' => $userData,
        ]);
    }

    /**
     * Update the authenticated user's profile information.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        // Different validation rules based on user role
        if ($user->isEmployer()) {
            $validator = Validator::make($request->all(), [
                'full_name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
                'poste' => 'sometimes|required|string|max:255',
                'phone' => 'sometimes|required|string|max:255',
            ]);
        } else {
            // Admin validation rules
            $validator = Validator::make($request->all(), [
                'full_name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            DB::beginTransaction();

            // Update user basic information
            if ($request->has('full_name')) {
                $user->full_name = $request->full_name;
            }
            if ($request->has('email')) {
                $user->email = $request->email;
            }
            $user->save();

            // Update employer-specific information if applicable
            if ($user->isEmployer()) {
                $employer = $user->employer;
                if ($employer) {
                    if ($request->has('poste')) {
                        $employer->poste = $request->poste;
                    }
                    if ($request->has('phone')) {
                        $employer->phone = $request->phone;
                    }
                    $employer->save();
                }
            }

            DB::commit();

            // Return updated user data
            return $this->user($request);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
