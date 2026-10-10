<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\backend\MrLocation;
use Illuminate\Http\Request;

class MrLocationController extends Controller
{
    // public function index(Request $request)
    // {
    //     $request->validate([
    //         'date' => ['nullable', 'date'],
    //     ]);

    //     $query = MrLocation::where('user_id', auth()->id());

    //     if ($request->filled('date')) {
    //         $query->whereDate('date', $request->date);
    //     }

    //     $locations = $query
    //         ->latest('date')
    //         ->get();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Location data fetched successfully.',
    //         'data' => $locations,
    //     ]);
    // }

    public function index(Request $request)
    {
        $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $query = MrLocation::where('user_id', auth()->id());

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        $locations = $query
            ->latest('date')
            ->get()
            ->map(function ($item) {

                $latitudes = is_array($item->latitude)
                    ? $item->latitude
                    : [$item->latitude];

                $longitudes = is_array($item->longitude)
                    ? $item->longitude
                    : [$item->longitude];

                $location = [];

                foreach ($latitudes as $index => $latitude) {
                    if (isset($longitudes[$index])) {
                        $location[] = [
                            'latitude' => (string) $latitude,
                            'longitude' => (string) $longitudes[$index],
                        ];
                    }
                }

                return [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'date' => $item->date,
                    'location' => $location,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Location data fetched successfully.',
            'data' => $locations,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $userId = auth()->id();
        $today = now()->toDateString();

        $location = MrLocation::where('user_id', $userId)
            ->whereDate('date', $today)
            ->first();

        // Today's first location
        if (!$location) {
            $location = MrLocation::create([
                'user_id' => $userId,
                'date' => $today,
                'latitude' => [(float) $request->latitude],
                'longitude' => [(float) $request->longitude],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Location tracked successfully.',
                'data' => $location,
            ], 201);
        }

        // Existing data normalize
        $latitudes = is_array($location->latitude)
            ? $location->latitude
            : [$location->latitude];

        $longitudes = is_array($location->longitude)
            ? $location->longitude
            : [$location->longitude];

        // Duplicate coordinate check
        foreach ($latitudes as $index => $latitude) {
            if (
                isset($longitudes[$index]) &&
                (float) $latitude === (float) $request->latitude &&
                (float) $longitudes[$index] === (float) $request->longitude
            ) {
                return response()->json([
                    'success' => true,
                    'message' => 'This location already exists.',
                    'data' => $location,
                ]);
            }
        }

        // Add today's new coordinate
        $latitudes[] = (float) $request->latitude;
        $longitudes[] = (float) $request->longitude;

        $location->update([
            'latitude' => $latitudes,
            'longitude' => $longitudes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Location tracked successfully.',
            'data' => $location->fresh(),
        ]);
    }

    public function show($id)
    {
        $location = MrLocation::where('user_id', auth()->id())
            ->find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'Location not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Location fetched successfully.',
            'data' => $location,
        ]);
    }
}
