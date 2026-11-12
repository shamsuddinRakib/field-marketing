<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstituitionResource;
use App\Models\backend\Institution;
use Illuminate\Http\Request;

class InstitutionController extends Controller
{
    /**
     * Active institutions list (dropdown / search for the app).
     */
    // public function index(Request $request)
    // {
    //     $query = Institution::query()->where('status', 1);

    //     if ($request->filled('search')) {
    //         $search = $request->input('search');
    //         $query->where(function ($q) use ($search) {
    //             $q->where('institution_name', 'like', "%{$search}%")
    //                 ->orWhere('code', 'like', "%{$search}%")
    //                 ->orWhere('phone', 'like', "%{$search}%")
    //                 ->orWhere('address', 'like', "%{$search}%");
    //         });
    //     }

    //     $institutions = $query->orderBy('institution_name')->get();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Institutions fetched successfully.',
    //         'data' => $institutions->map(fn($institution) => [
    //             'id' => $institution->id,
    //             'name' => $institution->institution_name,
    //             'code' => $institution->code,
    //             'email' => $institution->email,
    //             'phone' => $institution->phone,
    //             'address' => $institution->address,
    //         ]),
    //     ]);
    // }


    public function index(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        $query = Institution::query()
            ->with(['upazila', 'district', 'division'])
            ->where('status', 1);

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('institution_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhereHas('upazila', function ($subQuery) use ($search) {
                        $subQuery->where('upazila_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('district', function ($subQuery) use ($search) {
                        $subQuery->where('district_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('division', function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $institutions = $query
            ->orderBy('institution_name')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Institutions fetched successfully.',
            'data' => InstituitionResource::collection(
                $institutions->items()
            ),
            'pagination' => [
                'current_page' => $institutions->currentPage(),
                'last_page' => $institutions->lastPage(),
                'per_page' => $institutions->perPage(),
                'total' => $institutions->total(),
                'next_page_url' => $institutions->nextPageUrl(),
                'prev_page_url' => $institutions->previousPageUrl(),
            ],
        ]);
    }
}
