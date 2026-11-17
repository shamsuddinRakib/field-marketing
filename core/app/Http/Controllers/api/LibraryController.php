<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\backend\Library;
use App\Models\backend\Upazila;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    /**
     * Active libraries list (dropdown / search for the app).
     */
    public function index(Request $request)
    {
        $query = Library::query()->where('status', 1);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('library_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $libraries = $query->orderBy('library_name')->get();

        return response()->json([
            'success' => true,
            'message' => 'Libraries fetched successfully.',
            'data' => $libraries->map(fn ($library) => [
                'id' => $library->id,
                'name' => $library->library_name,
                'code' => $library->code,
                'email' => $library->email,
                'phone' => $library->phone,
                'address' => $library->address,
            ]),
        ]);
    }

    /**
     * Create a new library (from MR mobile app).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'library_name' => ['required', 'string', 'max:191'],
            'code'         => ['nullable', 'string', 'max:100', 'unique:libraries,code'],
            'email'        => ['nullable', 'email', 'max:191'],
            'phone'        => ['required', 'string', 'max:50'],
            'upazila_id'   => ['nullable', 'integer', 'exists:upazilas,id'],
            'district_id'  => ['nullable', 'integer', 'exists:districts,district_id'],
            'division_id'  => ['nullable', 'integer', 'exists:divisions,id'],
            'address'      => ['nullable', 'string', 'max:1000'],
        ]);

        $libraryData = $validated;

        // Auto-resolve District and Division from Upazila if provided (matches DailyVisitController pattern)
        if (!empty($libraryData['upazila_id']) && (empty($libraryData['district_id']) || empty($libraryData['division_id']))) {
            $upazila = Upazila::with('upazila_district')->find($libraryData['upazila_id']);
            if ($upazila && $upazila->upazila_district) {
                $libraryData['district_id'] = $libraryData['district_id'] ?? $upazila->upazila_district->district_id;
                $libraryData['division_id'] = $libraryData['division_id'] ?? $upazila->upazila_district->district_division_id;
            }
        }

        $libraryData['status'] = 1;
        $library = Library::create($libraryData);

        return response()->json([
            'success' => true,
            'message' => 'Library created successfully.',
            'data'    => [
                'id'          => $library->id,
                'name'        => $library->library_name,
                'code'        => $library->code,
                'email'       => $library->email,
                'phone'       => $library->phone,
                'upazila_id'  => $library->upazila_id,
                'district_id' => $library->district_id,
                'division_id' => $library->division_id,
                'address'     => $library->address,
                'status'      => (bool) $library->status,
            ],
        ], 201);
    }
}
