<?php
namespace App\Http\Controllers;

use App\Models\backend\Expense;
use App\Models\backend\Sale;
use App\Models\backend\User;
use App\Models\backend\WebsiteSetting;
use App\Support\BranchScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CoreController extends Controller
{
    public function home()
    {

            $companySettings = WebsiteSetting::first();

        return view('backend.modules.dashboard.home', compact(
            'companySettings'
        ));
    }
    // public function home2()
    // {
    //     // Handle the request and return a view
    //     return view('backend.modules.dashboard.home2');
    // }
}
