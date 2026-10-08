<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\backend\Institution;
use Illuminate\Http\Request;

class InstitutionController extends Controller
{
    /**
     * Active institutions list (dropdown / search for the app).
     */
    public function index(Request $request)
    {
        $query = Institution::query()->where('status', 1);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('institution_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $institutions = $query->orderBy('institution_name')->get();

        return response()->json([
            'success' => true,
            'message' => 'Institutions fetched successfully.',
            'data' => $institutions->map(fn ($institution) => [
                'id' => $institution->id,
                'name' => $institution->institution_name,
                'code' => $institution->code,
                'email' => $institution->email,
                'phone' => $institution->phone,
                'address' => $institution->address,
            ]),
        ]);
    }
}
