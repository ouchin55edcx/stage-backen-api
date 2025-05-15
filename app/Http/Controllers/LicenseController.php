<?php

namespace App\Http\Controllers;

use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LicenseController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => License::all()
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'key' => 'required|string|max:255',
            'expiration_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $license = License::create($validator->validated());

        return response()->json([
            'status' => 'success',
            'data' => $license
        ], 201);
    }

    public function show(License $license)
    {
        return response()->json([
            'status' => 'success',
            'data' => $license
        ]);
    }

    public function update(Request $request, License $license)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'type' => 'string|max:255',
            'key' => 'string|max:255',
            'expiration_date' => 'date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $license->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'data' => $license
        ]);
    }

    public function destroy(License $license)
    {
        $license->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'License deleted successfully'
        ]);
    }
}
