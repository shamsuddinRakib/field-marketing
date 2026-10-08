<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\backend\Library;
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
}
