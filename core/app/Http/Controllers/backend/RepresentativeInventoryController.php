<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\AssignSpecimenItem;
use App\Models\backend\CurrentStock;
use App\Models\backend\MarketingRepresentative;
use App\Models\backend\Product;
use App\Models\backend\ProductDistributionItem;
use App\Models\backend\SpotSaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepresentativeInventoryController extends Controller
{
    public function index()
    {
        // KPI Summary Cards
        $totalInField = (int) CurrentStock::sum('current_stock');

        $totalDistributed = (int) DB::table('product_distribution_items as pdi')
            ->join('product_distributions as pd', 'pd.id', '=', 'pdi.product_distribution_id')
            ->where('pd.status', 'delivered')
            ->sum('pdi.quantity');

        $totalAssigned = (int) DB::table('assign_specimen_items as asi')
            ->join('assign_specimens as aspec', 'aspec.id', '=', 'asi.assign_specimen_id')
            ->where('aspec.status', 'approved')
            ->sum('asi.quantity');

        $totalSpotSold = (int) DB::table('spot_sale_items as ssi')
            ->join('spot_sales as ss', 'ss.id', '=', 'ssi.spot_sale_id')
            ->where('ss.status', 'approved')
            ->sum('ssi.quantity');

        $totalReturned = (int) DB::table('book_returns')
            ->whereIn('status', ['received', 'partial'])
            ->sum('returned_quantity');

        return view('backend.modules.representative_inventory.index', compact(
            'totalInField',
            'totalDistributed',
            'totalAssigned',
            'totalSpotSold',
            'totalReturned'
        ));
    }

    public function listAjax(Request $request)
    {
        $draw      = (int) $request->input('draw');
        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        $orderDir  = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchVal = trim($request->input('search.value', ''));

        $query = DB::table('marketing_representatives as mr')
            ->whereNull('mr.deleted_at')
            ->join('users as u', 'u.id', '=', 'mr.user_id')
            ->leftJoin('mr_designations as des', 'des.id', '=', 'mr.mr_designation_id')
            ->leftJoin('branches as b', 'b.id', '=', 'mr.branch_id')
            ->leftJoin('upazilas as upz', 'upz.id', '=', 'mr.upazila_id')
            // 1. Total Distributed (Delivered only)
            ->leftJoinSub(
                DB::table('product_distributions as pd')
                    ->join('product_distribution_items as pdi', 'pdi.product_distribution_id', '=', 'pd.id')
                    ->where('pd.status', 'delivered')
                    ->select('pd.user_id', DB::raw('SUM(pdi.quantity) as total_distributed'))
                    ->groupBy('pd.user_id'),
                'dist_sum',
                'dist_sum.user_id', '=', 'mr.user_id'
            )
            // 2. Total Specimen Assigned (Approved only)
            ->leftJoinSub(
                DB::table('assign_specimens as aspec')
                    ->join('assign_specimen_items as asi', 'asi.assign_specimen_id', '=', 'aspec.id')
                    ->where('aspec.status', 'approved')
                    ->select('aspec.user_id', DB::raw('SUM(asi.quantity) as total_assigned'))
                    ->groupBy('aspec.user_id'),
                'assign_sum',
                'assign_sum.user_id', '=', 'mr.user_id'
            )
            // 3. Total Spot Sold (Approved only)
            ->leftJoinSub(
                DB::table('spot_sales as ss')
                    ->join('spot_sale_items as ssi', 'ssi.spot_sale_id', '=', 'ss.id')
                    ->where('ss.status', 'approved')
                    ->select('ss.user_id', DB::raw('SUM(ssi.quantity) as total_spot_sold'))
                    ->groupBy('ss.user_id'),
                'spot_sum',
                'spot_sum.user_id', '=', 'mr.user_id'
            )
            // 4. Total Returned (Received/Partial only)
            ->leftJoinSub(
                DB::table('book_returns as br')
                    ->whereIn('br.status', ['received', 'partial'])
                    ->select('br.marketing_representative_id', DB::raw('SUM(br.returned_quantity) as total_returned'))
                    ->groupBy('br.marketing_representative_id'),
                'return_sum',
                'return_sum.marketing_representative_id', '=', 'mr.id'
            )
            // 5. Current Stock Total In Hand
            ->leftJoinSub(
                DB::table('current_stocks as cs')
                    ->select('cs.user_id', DB::raw('SUM(cs.current_stock) as current_stock_total'))
                    ->groupBy('cs.user_id'),
                'stock_sum',
                'stock_sum.user_id', '=', 'mr.user_id'
            )
            ->select(
                'mr.id as mr_id',
                'mr.user_id',
                'mr.employee_id',
                'u.name as representative_name',
                'u.phone as representative_phone',
                'u.email as representative_email',
                'des.designation_name',
                'b.name as branch_name',
                'upz.upazila_name',
                DB::raw('COALESCE(dist_sum.total_distributed, 0) as total_distributed'),
                DB::raw('COALESCE(assign_sum.total_assigned, 0) as total_assigned'),
                DB::raw('COALESCE(spot_sum.total_spot_sold, 0) as total_spot_sold'),
                DB::raw('COALESCE(return_sum.total_returned, 0) as total_returned'),
                DB::raw('COALESCE(stock_sum.current_stock_total, 0) as current_stock')
            );

        $total = (clone $query)->count('mr.id');

        if ($searchVal !== '') {
            $query->where(function ($q) use ($searchVal) {
                $q->where('u.name', 'like', "%{$searchVal}%")
                    ->orWhere('u.phone', 'like', "%{$searchVal}%")
                    ->orWhere('mr.employee_id', 'like', "%{$searchVal}%")
                    ->orWhere('des.designation_name', 'like', "%{$searchVal}%")
                    ->orWhere('b.name', 'like', "%{$searchVal}%")
                    ->orWhere('upz.upazila_name', 'like', "%{$searchVal}%");
            });
        }

        $filtered = (clone $query)->count('mr.id');

        $rows = $query->orderBy('current_stock', $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $repIds = $rows->pluck('mr_id')->all();
        $territoriesByRep = DB::table('marketing_representative_territories as mrt')
            ->join('upazilas as upz', 'upz.id', '=', 'mrt.upazila_id')
            ->whereIn('mrt.marketing_representative_id', $repIds)
            ->select('mrt.marketing_representative_id', 'upz.upazila_name')
            ->get()
            ->groupBy('marketing_representative_id');

        $data = [];
        foreach ($rows as $row) {
            $repCol = '<div>
                <strong class="text-dark">' . e($row->representative_name) . '</strong>' .
                ($row->employee_id ? ' <span class="badge bg-light text-secondary text-xs">' . e($row->employee_id) . '</span>' : '') .
                '<div class="text-secondary" style="font-size:12px;">' . e($row->representative_phone ?? '—') . '</div>
            </div>';

            $designationCol = e($row->designation_name ?? '—');

            $repTerritories = $territoriesByRep->get($row->mr_id);
            $upazilaList = $repTerritories ? $repTerritories->pluck('upazila_name')->filter()->unique()->values() : collect();

            if ($upazilaList->isNotEmpty()) {
                $upazilaDisplay = e($upazilaList->take(3)->implode(', '));
                if ($upazilaList->count() > 3) {
                    $extraCount = $upazilaList->count() - 3;
                    $allUpazilas = e($upazilaList->implode(', '));
                    $upazilaDisplay .= ' <span class="badge bg-light text-primary text-xs" title="' . $allUpazilas . '">+' . $extraCount . ' more</span>';
                }
            } else {
                $upazilaDisplay = e($row->upazila_name ?? '—');
            }

            $branchName = e($row->branch_name ?? '—');

            $territoryCol = '<div class="d-flex flex-column gap-1">
                <div class="d-flex align-items-center gap-1 fw-semibold text-dark">
                    <iconify-icon icon="ri-store-3-line" class="text-secondary flex-shrink-0 text-base"></iconify-icon>
                    <span>' . $branchName . '</span>
                </div>
                <div class="d-flex align-items-center gap-1 text-secondary" style="font-size:12px;">
                    <iconify-icon icon="mdi:map-marker-multiple-outline" class="text-primary flex-shrink-0 text-base"></iconify-icon>
                    <span>' . $upazilaDisplay . '</span>
                </div>
            </div>';

            $distCol = '<div class="text-center"><span class="fw-semibold text-primary">' . number_format((int) $row->total_distributed) . '</span></div>';
            $assignCol = '<div class="text-center"><span class="fw-semibold text-info">' . number_format((int) $row->total_assigned) . '</span></div>';
            $soldCol = '<div class="text-center"><span class="fw-semibold text-success">' . number_format((int) $row->total_spot_sold) . '</span></div>';
            $returnCol = '<div class="text-center"><span class="fw-semibold text-warning">' . number_format((int) $row->total_returned) . '</span></div>';

            $stockClass = (int) $row->current_stock > 0
                ? 'bg-success-600 text-white'
                : 'bg-secondary text-white';

            $stockCol = '<div class="text-center"><span class="badge text-sm fw-semibold ' . $stockClass . ' px-16 py-6 radius-4">'
                . number_format((int) $row->current_stock)
                . '</span></div>';

            $actionCol = '<div class="d-flex align-items-center justify-content-center w-100">
                <a href="' . route('representative-inventory.show', $row->mr_id) . '"
                   class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-info-focus text-info-main"
                   title="View Product Breakdown">
                   <iconify-icon icon="mdi:eye" class="text-lg"></iconify-icon>
                </a>
            </div>';

            $data[] = [
                $row->mr_id,
                $repCol,
                $designationCol,
                $territoryCol,
                $distCol,
                $assignCol,
                $soldCol,
                $returnCol,
                $stockCol,
                $actionCol,
            ];
        }

        return response()->json([
            'draw'                 => $draw,
            'iTotalRecords'        => $total,
            'iTotalDisplayRecords' => $filtered,
            'aaData'               => $data,
        ]);
    }

    public function show(MarketingRepresentative $representative)
    {
        $representative->load(['user', 'mrDesignation', 'branch', 'upazila', 'territories.upazila']);
        $products = $this->getRepresentativeProducts($representative);

        $totalInHand      = (int) $products->sum('current_stock');
        $totalDistributed = (int) $products->sum('distributed_qty');
        $totalAssigned    = (int) $products->sum('assigned_qty');
        $totalSpotSold    = (int) $products->sum('spot_sold_qty');
        $totalReturned    = (int) $products->sum('returned_qty');

        return view('backend.modules.representative_inventory.show', compact(
            'representative',
            'products',
            'totalInHand',
            'totalDistributed',
            'totalAssigned',
            'totalSpotSold',
            'totalReturned'
        ));
    }

    public function detailsModal(MarketingRepresentative $representative)
    {
        $representative->load(['user', 'mrDesignation', 'branch', 'upazila', 'territories.upazila']);
        $products = $this->getRepresentativeProducts($representative);

        return view('backend.modules.representative_inventory.details_modal', compact('representative', 'products'));
    }

    private function getRepresentativeProducts(MarketingRepresentative $representative)
    {
        $userId = $representative->user_id;
        $repId  = $representative->id;

        return DB::table('products as p')
            ->leftJoin('current_stocks as cs', function ($join) use ($userId) {
                $join->on('cs.product_id', '=', 'p.id')
                     ->where('cs.user_id', '=', $userId);
            })
            ->leftJoinSub(
                DB::table('product_distributions as pd')
                    ->join('product_distribution_items as pdi', 'pdi.product_distribution_id', '=', 'pd.id')
                    ->where('pd.user_id', $userId)
                    ->where('pd.status', 'delivered')
                    ->select('pdi.product_id', DB::raw('SUM(pdi.quantity) as qty'))
                    ->groupBy('pdi.product_id'),
                'dist',
                'dist.product_id', '=', 'p.id'
            )
            ->leftJoinSub(
                DB::table('assign_specimens as aspec')
                    ->join('assign_specimen_items as asi', 'asi.assign_specimen_id', '=', 'aspec.id')
                    ->where('aspec.user_id', $userId)
                    ->where('aspec.status', 'approved')
                    ->select('asi.product_id', DB::raw('SUM(asi.quantity) as qty'))
                    ->groupBy('asi.product_id'),
                'asgn',
                'asgn.product_id', '=', 'p.id'
            )
            ->leftJoinSub(
                DB::table('spot_sales as ss')
                    ->join('spot_sale_items as ssi', 'ssi.spot_sale_id', '=', 'ss.id')
                    ->where('ss.user_id', $userId)
                    ->where('ss.status', 'approved')
                    ->select('ssi.product_id', DB::raw('SUM(ssi.quantity) as qty'))
                    ->groupBy('ssi.product_id'),
                'spot',
                'spot.product_id', '=', 'p.id'
            )
            ->leftJoinSub(
                DB::table('book_returns as br')
                    ->where('br.marketing_representative_id', $repId)
                    ->whereIn('br.status', ['received', 'partial'])
                    ->select('br.product_id', DB::raw('SUM(br.returned_quantity) as qty'))
                    ->groupBy('br.product_id'),
                'ret',
                'ret.product_id', '=', 'p.id'
            )
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.product_code',
                DB::raw('COALESCE(cs.current_stock, 0) as current_stock'),
                DB::raw('COALESCE(dist.qty, 0) as distributed_qty'),
                DB::raw('COALESCE(asgn.qty, 0) as assigned_qty'),
                DB::raw('COALESCE(spot.qty, 0) as spot_sold_qty'),
                DB::raw('COALESCE(ret.qty, 0) as returned_qty')
            )
            ->where(function ($q) {
                $q->whereRaw('COALESCE(cs.current_stock, 0) > 0')
                  ->orWhereRaw('COALESCE(dist.qty, 0) > 0')
                  ->orWhereRaw('COALESCE(asgn.qty, 0) > 0')
                  ->orWhereRaw('COALESCE(spot.qty, 0) > 0')
                  ->orWhereRaw('COALESCE(ret.qty, 0) > 0');
            })
            ->orderBy('p.name', 'asc')
            ->get();
    }
}
