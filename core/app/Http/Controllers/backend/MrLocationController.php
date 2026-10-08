<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\MarketingRepresentative;
use App\Models\backend\MrLocation;
use Illuminate\Http\Request;

class MrLocationController extends Controller
{
    public function map(Request $request)
    {
        $mrs = MarketingRepresentative::with('user')
            ->whereHas('user')
            ->get();

        $location = null;

        if ($request->filled('user_id') && $request->filled('date')) {
            $location = MrLocation::where('user_id', $request->user_id)
                ->whereDate('date', $request->date)
                ->first();
        }

        return view('backend.modules.mr_locations.map', compact(
            'mrs',
            'location'
        ));
    }
}
