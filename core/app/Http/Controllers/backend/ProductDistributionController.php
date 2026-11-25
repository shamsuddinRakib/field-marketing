<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\BookRequest;
use App\Models\backend\CurrentStock;
use App\Models\backend\MarketingRepresentative;
use App\Models\backend\Product;
use App\Models\backend\ProductDistribution;
use App\Models\backend\ProductDistributionItem;
use App\Services\BookRequestAvailabilityService;
use App\Models\backend\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductDistributionController extends Controller
{
    public function index()
    {
        return view('backend.modules.product_distribution.index');
    }

    public function listAjax(Request $request)
    {
        $draw      = (int) $request->input('draw');
        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        $orderDir  = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchVal = trim($request->input('search.value', ''));

        $base = ProductDistribution::query()
            ->select(['id', 'user_id', 'distribution_type', 'note', 'status', 'created_at'])
            ->with(['user:id,name', 'items.product:id,name'])
            ->withSum('items as items_qty', 'quantity');

        $total = (clone $base)->count();

        if ($searchVal !== '') {
            $base->where(function ($q) use ($searchVal) {
                $q->where('note', 'like', "%{$searchVal}%")
                    ->orWhere('id', $searchVal)
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$searchVal}%"));
            });
        }

        $filtered = (clone $base)->count();

        $rows = $base->orderBy('id', $orderDir)
            ->skip($start)->take($length)->get();

        $data = [];
        foreach ($rows as $b) {
            $nameCol = '<strong>' . e($b->user->name ?? '') . '</strong>';

            $whenCol = $b->created_at
                ? $b->created_at->format('d M Y')
                : '—';

            $qty = (float) ($b->items_qty ?? 0);


            // $active = $b->is_active
            //     ? '<span class="badge text-sm fw-semibold bg-dark-success-gradient px-20 py-9 radius-4 text-white">Active</span>'
            //     : '<span class="badge text-sm fw-semibold bg-dark-warning-gradient px-20 py-9 radius-4 text-white">Inactive</span>';

            $actions = '<div class="d-inline-flex justify-content-end gap-1 w-100">
                <a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
                    bg-success-focus text-success-main AjaxModal"
                    data-ajax-modal="' . route('product-distribution.edit', $b->id) . '"
                    data-size="xl"
                    data-onsuccess="BranchesIndex.onSaved"
                    title="Edit">
                    <iconify-icon icon="lucide:edit"></iconify-icon>
                </a>
                <a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-branch-delete"
                    data-id="' . $b->id . '"
                    data-url="' . route('product-distribution.destroy', $b->id) . '"
                    title="Delete">
                    <iconify-icon icon="mdi:delete"></iconify-icon>
                </a>
            </div>';

            $statusLabel = match ($b->status) {
                'pending'   => '<span class="badge text-sm fw-semibold bg-info-600 px-20 py-9 radius-4 text-white">Pending</span>',
                'delivered' => '<span class="badge text-sm fw-semibold bg-success-600 px-20 py-9 radius-4 text-white">Delivered</span>',
                default     => '<span class="badge text-sm fw-semibold bg-lilac-600 px-20 py-9 radius-4 text-white">' . e(ucfirst($b->status)) . '</span>',
            };

            $statusBadge = $b->status === 'delivered'
                ? $statusLabel
                : '<a href="javascript:void(0)" class="AjaxModal" data-ajax-modal="' . route('product-distribution.statusModal', $b->id) . '" data-onsuccess="BranchesIndex.onSaved" data-size="sm">' . $statusLabel . '</a>';

            $typeBadge = ($b->distribution_type ?? 'specimen') === 'sale'
                ? '<span class="badge bg-success-focus text-success-main border border-success px-12 py-4 radius-4">Sale Copy</span>'
                : '<span class="badge bg-info-focus text-info-main border border-info px-12 py-4 radius-4">Specimen Copy</span>';

            $actions = '<div class="d-inline-flex align-items-center gap-1">';

            $actions .= '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-info-focus text-info-main AjaxModal" data-ajax-modal="' . route('product-distribution.show', $b->id) . '" data-size="lg" title="View Details"><iconify-icon icon="mdi:eye"></iconify-icon></a>';

            if ($b->status !== 'delivered') {
                $actions .= '<a href="' . route('product-distribution.edit', $b->id) . '" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-success-focus text-success-main" title="Edit"><iconify-icon icon="lucide:edit"></iconify-icon></a>';
                $actions .= '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-branch-delete" data-id="' . $b->id . '" data-url="' . route('product-distribution.destroy', $b->id) . '" title="Delete"><iconify-icon icon="mdi:delete"></iconify-icon></a>';
            }

            $actions .= '</div>';

            $data[] = [
                $b->id,
                $nameCol,
                $typeBadge,
                $whenCol,
                number_format((float) $qty),
                e($b->note ?? '—'),
                $statusBadge,
                $actions,
            ];
        }

        return response()->json([
            'draw'                 => $draw,
            'iTotalRecords'        => $total,
            'iTotalDisplayRecords' => $filtered,
            'aaData'               => $data,
        ]);
    }

    public function create(Request $req)
    {
        $fromBookRequest = null;
        $requestedItems = collect();

        if ($req->filled('book_request_id')) {
            $bookRequest = BookRequest::with([
                'representative.user',
                'items.product:id,name,category_id,subcategory_id,thumbnail_image,price',
                'items.product.category:id,name',
                'items.product.subcategory:id,name',
            ])->find((int) $req->input('book_request_id'));

            if ($bookRequest) {
                $fromBookRequest = $bookRequest;
                $requestedItems = $bookRequest->request_group_id
                    ? BookRequest::with([
                        'items.product:id,name,category_id,subcategory_id,thumbnail_image,price',
                        'items.product.category:id,name',
                        'items.product.subcategory:id,name',
                    ])->where('request_group_id', $bookRequest->request_group_id)->get()
                        ->flatMap(fn (BookRequest $request) => $request->items)->values()
                    : $bookRequest->items;
            }
        }

        return view('backend.modules.product_distribution.create', compact('fromBookRequest', 'requestedItems'));
    }

    public function createModal(Request $req)
    {
        return $this->create($req);
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'book_request_id'     => ['nullable', 'integer', 'exists:book_requests,id'],
            'user_id'             => ['required', 'integer', 'exists:users,id'],
            'distribution_type'   => ['required', 'string', 'in:specimen,sale'],
            'note'                => ['nullable', 'string', 'max:255'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.product_id'  => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'    => ['required', 'integer', 'min:1'],
        ]);

        // Merge duplicate products into single line
        $merged = [];
        foreach ($data['items'] as $item) {
            $pid = (int) $item['product_id'];
            $merged[$pid] = ($merged[$pid] ?? 0) + (int) $item['quantity'];
        }

        DB::beginTransaction();
        try {
            $distribution = ProductDistribution::create([
                'user_id'           => $data['user_id'],
                'product_id'        => $data['items'][0]['product_id'],
                'quantity'          => collect($data['items'])->sum('quantity'),
                'distribution_type' => $data['distribution_type'] ?? 'specimen',
                'note'              => $data['note'] ?? null,
                'status'            => 'pending',
            ]);

            foreach ($merged as $productId => $qty) {
                ProductDistributionItem::create([
                    'product_distribution_id' => $distribution->id,
                    'product_id'              => $productId,
                    'quantity'                => $qty,
                ]);
            }

            if (!empty($data['book_request_id'])) {
                $br = BookRequest::find($data['book_request_id']);
                if ($br) {
                    if ($br->request_group_id) {
                        BookRequest::where('request_group_id', $br->request_group_id)->update([
                            'product_distribution_id' => $distribution->id,
                            'status' => 'approved',
                        ]);
                    } else {
                        $br->update([
                            'product_distribution_id' => $distribution->id,
                            'status' => 'approved',
                        ]);
                    }
                }
            }

            DB::commit();

            if ($req->ajax() || $req->wantsJson()) {
                return response()->json([
                    'ok'       => true,
                    'msg'      => 'Products distributed successfully (pending)',
                    'redirect' => route('product-distribution.index'),
                    'data'     => ['id' => $distribution->id]
                ]);
            }

            return redirect()->route('product-distribution.index')->with('success', 'Products distributed successfully (pending)');
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($req->ajax() || $req->wantsJson()) {
                return response()->json(['ok' => false, 'msg' => $e->getMessage()], 500);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(ProductDistribution $productDistribution)
    {
        $productDistribution->load(['items.product:id,name,thumbnail_image', 'user:id,name']);

        return view('backend.modules.product_distribution.show', compact('productDistribution'));
    }

    public function edit(ProductDistribution $productDistribution)
    {
        if ($productDistribution->status === 'delivered') {
            abort(403, 'Delivered record cannot be edited');
        }

        $productDistribution->load([
            'items.product:id,name,category_id,subcategory_id,thumbnail_image',
            'items.product.category:id,name',
            'items.product.subcategory:id,name',
            'user:id,name'
        ]);

        return view('backend.modules.product_distribution.edit', compact('productDistribution'));
    }

    public function editModal(ProductDistribution $productDistribution)
    {
        return $this->edit($productDistribution);
    }

    public function update(ProductDistribution $productDistribution, Request $req)
    {
        if ($productDistribution->status === 'delivered') {
            return response()->json(['ok' => false, 'msg' => 'Delivered record cannot be updated'], 422);
        }

        $data = $req->validate([
            'user_id'             => ['required', 'integer', 'exists:users,id'],
            'distribution_type'   => ['required', 'string', 'in:specimen,sale'],
            'note'                => ['nullable', 'string', 'max:255'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.product_id'  => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'    => ['required', 'integer', 'min:1'],
        ]);

        $merged = [];
        foreach ($data['items'] as $item) {
            $pid = (int) $item['product_id'];
            $merged[$pid] = ($merged[$pid] ?? 0) + (int) $item['quantity'];
        }

        DB::beginTransaction();
        try {
            $productDistribution->update([
                'user_id'           => $data['user_id'],
                'distribution_type' => $data['distribution_type'] ?? ($productDistribution->distribution_type ?? 'specimen'),
                'note'              => $data['note'] ?? null,
            ]);

            $productDistribution->items()->delete();

            foreach ($merged as $productId => $qty) {
                $productDistribution->items()->create([
                    'product_id' => $productId,
                    'quantity'   => $qty,
                ]);
            }

            DB::commit();

            if ($req->ajax() || $req->wantsJson()) {
                return response()->json([
                    'ok'       => true,
                    'msg'      => 'Updated Successfully',
                    'redirect' => route('product-distribution.index')
                ]);
            }

            return redirect()->route('product-distribution.index')->with('success', 'Updated Successfully');
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($req->ajax() || $req->wantsJson()) {
                return response()->json(['ok' => false, 'msg' => $e->getMessage()], 500);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(ProductDistribution $productDistribution)
    {
        if ($productDistribution->status !== 'pending') {
            return response()->json(['ok' => false, 'msg' => 'Only pending records can be deleted.'], 422);
        }

        $productDistribution->items()->delete();
        $productDistribution->delete();

        return response()->json(['ok' => true, 'msg' => 'Deleted Successfully']);
    }

    public function statusModal(ProductDistribution $productDistribution)
    {
        $productDistribution->load(['items.product:id,name', 'user:id,name']);

        return view('backend.modules.product_distribution.statusModal', compact('productDistribution'));
    }

    public function updateStatus(ProductDistribution $productDistribution, Request $req)
    {
        $data = $req->validate([
            'status' => ['required', 'string', 'in:pending,delivered'],
        ]);

        if ($productDistribution->status === 'delivered') {
            return response()->json(['ok' => false, 'msg' => 'Delivered record cannot be updated'], 422);
        }

        if ($data['status'] === 'pending') {
            return response()->json(['ok' => true, 'msg' => 'Status is already pending']);
        }

        $productDistribution->load('items');

        if ($productDistribution->items->isEmpty()) {
            return response()->json(['ok' => false, 'msg' => 'No items to deliver'], 422);
        }

        DB::beginTransaction();
        try {
            $distType = in_array($productDistribution->distribution_type, ['specimen', 'sale'])
                ? $productDistribution->distribution_type
                : 'specimen';

            foreach ($productDistribution->items as $item) {
                $stock = CurrentStock::where('user_id', $productDistribution->user_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    if ($distType === 'sale') {
                        $stock->increment('sale_stock', $item->quantity);
                    } else {
                        $stock->increment('specimen_stock', $item->quantity);
                    }
                    $stock->current_stock = (int) $stock->specimen_stock + (int) $stock->sale_stock;
                    $stock->save();
                } else {
                    CurrentStock::create([
                        'user_id'        => $productDistribution->user_id,
                        'product_id'     => $item->product_id,
                        'specimen_stock' => $distType === 'specimen' ? $item->quantity : 0,
                        'sale_stock'     => $distType === 'sale' ? $item->quantity : 0,
                        'current_stock'  => $item->quantity,
                    ]);
                }

                StockLedger::create([
                    'ref_type'   => 'Distribution',
                    'ref_id'     => $productDistribution->id,
                    'product_id' => $item->product_id,
                    'stock_type' => $distType,
                    'quantity'   => $item->quantity,
                    'direction'  => 'IN',
                    'note'       => $productDistribution->note ?? '',
                ]);
            }

            $productDistribution->status = 'delivered';
            $productDistribution->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['ok' => false, 'msg' => $e->getMessage() ?: 'Something went wrong. Please try again.'], 500);
        }

        return response()->json(['ok' => true, 'msg' => 'Status Updated Successfully']);
    }

    public function productsSelect2(Request $request)
    {
        $repId            = (int) $request->input('marketing_representative_id');
        $excludeGroupId   = $request->input('exclude_request_group_id');
        $excludeRequestId = $request->input('exclude_request_id') ? (int) $request->input('exclude_request_id') : null;
        $term             = trim($request->input('q', ''));

        if (!$repId) {
            return response()->json(['results' => []]);
        }

        $availability = app(BookRequestAvailabilityService::class)->forRepresentative(
            $repId,
            $excludeGroupId,
            $excludeRequestId
        );

        if (empty($availability)) {
            return response()->json(['results' => []]);
        }

        $productIds = array_keys($availability);

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->when($request->filled('category_id'), function ($q) use ($request) {
                $q->where('category_id', $request->input('category_id'));
            })
            ->when($request->filled('subcategory_id'), function ($q) use ($request) {
                $q->where('subcategory_id', $request->input('subcategory_id'));
            })
            ->when($term !== '', function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%");
            })
            ->get();

        $results = $products->map(function ($product) use ($availability) {
            $avail = $availability[$product->id]['available_quantity'] ?? 0;
            return [
                'id'                 => $product->id,
                'text'               => $product->name . " (Available: {$avail})",
                'available_quantity' => $avail,
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    public function distributionsSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));
        $query = ProductDistribution::query()
            ->with(['user', 'items.product'])
            ->where('status', 'delivered')
            ->when($term !== '', function ($q) use ($term) {
                $q->where('id', $term)
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$term}%"));
            })
            ->orderByDesc('id')
            ->limit(30);

        $items = $query->get();

        return response()->json([
            'results' => $items->map(function ($dist) {
                $userName = $dist->user?->name ?? '#' . $dist->user_id;
                return [
                    'id'   => $dist->id,
                    'text' => "Distribution #{$dist->id} - {$userName} (" . ($dist->created_at ? $dist->created_at->format('d M Y') : '—') . ')',
                ];
            })->values(),
        ]);
    }
}
