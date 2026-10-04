<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\backend\MarketingRepresentative;
use Illuminate\Http\Request;
use App\Models\backend\User;
use Illuminate\Support\Facades\Hash;

class MrAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $loginField = filter_var(
            $request->login,
            FILTER_VALIDATE_EMAIL
        ) ? 'email' : 'phone';

        $user = User::where($loginField, $request->login)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        // User status check
        if (!$user->status) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact the administrator.',
            ], 403);
        }

        // MR profile check
        $mr = $user->marketingRepresentative;

        if (!$mr) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized as a Marketing Representative.',
            ], 403);
        }

        // MR status check
        if (!$mr->status) {
            return response()->json([
                'success' => false,
                'message' => 'Your Marketing Representative account is inactive.',
            ], 403);
        }

        // Password check
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        // Create Sanctum token
        $token = $user->createToken('mr-mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',

            'token' => $token,
            'token_type' => 'Bearer',

            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'username' => $user->username,
            ],

            'marketing_representative' => [
                'id' => $mr->id,
                'employee_id' => $mr->employee_id,
                'organization' => $mr->organization,
                'branch_id' => $mr->branch_id,
                'upazila_id' => $mr->upazila_id,
                'district_id' => $mr->district_id,
                'division_id' => $mr->division_id,
                //'territory' => $mr->territory,
                'status' => (bool) $mr->status,
            ],
        ], 200);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $representative = MarketingRepresentative::with([
            'branch'
        ])->where('user_id', $user->id)->first();

        if (!$representative) {
            return response()->json([
                'success' => false,
                'message' => 'Marketing representative profile not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'employee_id' => $representative->employee_id,
                'organization' => $representative->organization,
                'branch_id' => $representative->branch_id,
                'address' => $representative->address,
                //'territory' => $representative->territory,
                'status' => $representative->status,
            ],
        ]);
    }

    public function updateCredentials(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $request->validate([
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'unique:users,email,' . $user->id,
            ],

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'unique:users,phone,' . $user->id,
            ],

            'current_password' => [
                'required_with:new_password',
                'nullable',
                'string',
            ],

            'new_password' => [
                'required_with:current_password',
                'nullable',
                'string',
                'min:6',
                'confirmed',
            ],
        ]);

        // Password Change

        if ($request->filled('new_password')) {

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect.',
                ], 422);
            }

            $user->password = Hash::make($request->new_password);

            // Revoke ALL existing Sanctum tokens.
            $user->tokens()->delete();
        }

        // Email / Phone Update

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Credentials updated successfully. Please login again.',
        ], 200);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        $mr = $user->marketingRepresentative;

        return response()->json([
            'success' => true,

            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'username' => $user->username,
            ],

            'marketing_representative' => [
                'id' => $mr->id,
                'employee_id' => $mr->employee_id,
                'organization' => $mr->organization,
                'branch_id' => $mr->branch_id,
                'upazila_id' => $mr->upazila_id,
                'district_id' => $mr->district_id,
                'division_id' => $mr->division_id,
                //'territory' => $mr->territory,
                'status' => (bool) $mr->status,
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        // Current token delete
        $user->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
        ], 200);
    }
}
