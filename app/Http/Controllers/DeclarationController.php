<?php

namespace App\Http\Controllers;

use App\Models\Declaration;
use App\Models\Employer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class DeclarationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * This method is used in the context of the resource routes.
     * For employers, it shows only their declarations.
     * For admins, it shows all declarations (but admins should use getAllDeclarations instead).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            // Admin can see all declarations
            $declarations = Declaration::with('employer.user')->get();
        } else {
            // Employer can only see their own declarations
            $employer = $user->employer;
            $declarations = Declaration::where('employer_id', $employer->id)->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => $declarations
        ]);
    }

    /**
     * Get all declarations - Admin only endpoint.
     *
     * This method is specifically for admin users to get all declarations.
     * It's separated from the index method to make access control more explicit.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllDeclarations(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Ensure only admins can access this endpoint
        if (!$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Only administrators can access all declarations.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Allow filtering by status
        $query = Declaration::with('employer.user');

        if ($request->has('status') && in_array($request->status, [
            Declaration::STATUS_PENDING,
            Declaration::STATUS_APPROVED,
            Declaration::STATUS_REJECTED
        ])) {
            $query->where('status', $request->status);
        }

        $declarations = $query->get();

        // Group declarations by status for easier frontend handling
        $grouped = [
            'pending' => $declarations->where('status', Declaration::STATUS_PENDING),
            'approved' => $declarations->where('status', Declaration::STATUS_APPROVED),
            'rejected' => $declarations->where('status', Declaration::STATUS_REJECTED),
            'all' => $declarations
        ];

        return response()->json([
            'status' => 'success',
            'data' => $declarations,
            'grouped' => $grouped,
            'counts' => [
                'pending' => $grouped['pending']->count(),
                'approved' => $grouped['approved']->count(),
                'rejected' => $grouped['rejected']->count(),
                'total' => $declarations->count()
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        // Only employers can create declarations
        if (!$user->isEmployer()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Only employers can create declarations.'
            ], Response::HTTP_FORBIDDEN);
        }

        $validator = Validator::make($request->all(), [
            'issue_title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $employer = $user->employer;

        $declaration = Declaration::create([
            'issue_title' => $request->issue_title,
            'description' => $request->description,
            'employer_id' => $employer->id,
            'status' => Declaration::STATUS_PENDING,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Declaration created successfully',
            'data' => $declaration
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $user = auth()->user();
        $declaration = Declaration::with('employer.user')->find($id);

        if (!$declaration) {
            return response()->json([
                'status' => 'error',
                'message' => 'Declaration not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if the user is authorized to view this declaration
        if ($user->isEmployer() && $declaration->employer_id !== $user->employer->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. You can only view your own declarations.'
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'status' => 'success',
            'data' => $declaration
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = auth()->user();
        $declaration = Declaration::find($id);

        if (!$declaration) {
            return response()->json([
                'status' => 'error',
                'message' => 'Declaration not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if the user is authorized to update this declaration
        if ($user->isEmployer() && $declaration->employer_id !== $user->employer->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. You can only update your own declarations.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Define validation rules based on user role
        if ($user->isAdmin()) {
            // Admins can update status and admin_comment, but not content fields
            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|required|string|in:' . Declaration::STATUS_PENDING . ',' . Declaration::STATUS_APPROVED . ',' . Declaration::STATUS_RESOLVED . ',' . Declaration::STATUS_REJECTED,
                'admin_comment' => 'sometimes|nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Update only admin-allowed fields
            $updateData = $request->only(['status', 'admin_comment']);
            $declaration->update($updateData);

            $statusText = isset($updateData['status']) ? $updateData['status'] : 'updated';
            $message = isset($updateData['status']) ? "Declaration status has been updated to {$statusText} successfully" : 'Declaration updated successfully';

        } else {
            // Employers can only update content fields
            $validator = Validator::make($request->all(), [
                'issue_title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $declaration->update($request->only(['issue_title', 'description']));
            $message = 'Declaration updated successfully';
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $declaration
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $user = auth()->user();
        $declaration = Declaration::find($id);

        if (!$declaration) {
            return response()->json([
                'status' => 'error',
                'message' => 'Declaration not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if the user is authorized to delete this declaration
        if ($user->isEmployer() && $declaration->employer_id !== $user->employer->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. You can only delete your own declarations.'
            ], Response::HTTP_FORBIDDEN);
        }

        $declaration->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Declaration deleted successfully'
        ]);
    }

    /**
     * Get declarations by employer ID or for the current logged-in employer.
     *
     * @param Request $request
     * @param string|null $employerId
     * @return JsonResponse
     */
    public function getByEmployer(Request $request, ?string $employerId = null): JsonResponse
    {
        // For the test route without authentication
        if (!auth()->check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated. Please login to access this resource.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = auth()->user();

        // If no employer ID is provided, use the current logged-in employer
        if (!$employerId) {
            // Check if the user is an employer
            if ($user->isEmployer()) {
                $employerId = $user->employer->id;
            } elseif ($user->isAdmin()) {
                // Admin without employer ID specified - return all declarations
                $declarations = Declaration::with('employer.user')->get();

                return response()->json([
                    'status' => 'success',
                    'data' => $declarations
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You must be an employer or admin to use this endpoint.'
                ], Response::HTTP_FORBIDDEN);
            }
        } else {
            // If employer ID is provided, only admin can view other employers' declarations
            if (!$user->isAdmin() && ($user->isEmployer() && $user->employer->id != $employerId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. You can only view your own declarations.'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // Get declarations for the specified employer
        $query = Declaration::with('employer.user')
            ->where('employer_id', $employerId);

        // Allow filtering by status
        if ($request->has('status') && in_array($request->status, [
            Declaration::STATUS_PENDING,
            Declaration::STATUS_APPROVED,
            Declaration::STATUS_REJECTED
        ])) {
            $query->where('status', $request->status);
        }

        $declarations = $query->get();

        // Group declarations by status for easier frontend handling
        $grouped = [
            'pending' => $declarations->where('status', Declaration::STATUS_PENDING),
            'approved' => $declarations->where('status', Declaration::STATUS_APPROVED),
            'rejected' => $declarations->where('status', Declaration::STATUS_REJECTED),
            'all' => $declarations
        ];

        return response()->json([
            'status' => 'success',
            'data' => $declarations,
            'grouped' => $grouped,
            'counts' => [
                'pending' => $grouped['pending']->count(),
                'approved' => $grouped['approved']->count(),
                'rejected' => $grouped['rejected']->count(),
                'total' => $declarations->count()
            ]
        ]);
    }

    /**
     * Process a declaration (approve or reject) - Admin only endpoint.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function processDeclaration(Request $request, string $id): JsonResponse
    {
        $user = auth()->user();

        // Ensure only admins can access this endpoint
        if (!$user->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Only administrators can approve or reject declarations.'
            ], Response::HTTP_FORBIDDEN);
        }

        $declaration = Declaration::with('employer.user')->find($id);

        if (!$declaration) {
            return response()->json([
                'status' => 'error',
                'message' => 'Declaration not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:' . Declaration::STATUS_APPROVED . ',' . Declaration::STATUS_REJECTED,
            'admin_comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $declaration->status = $request->status;
        $declaration->admin_comment = $request->admin_comment;
        $declaration->save();

        $statusText = $request->status === Declaration::STATUS_APPROVED ? 'approved' : 'rejected';

        return response()->json([
            'status' => 'success',
            'message' => "Declaration has been {$statusText} successfully",
            'data' => $declaration
        ]);
    }
}
