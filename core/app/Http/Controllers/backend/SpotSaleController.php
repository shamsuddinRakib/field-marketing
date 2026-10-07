<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\Library;
use App\Models\backend\Product;
use App\Models\backend\SpotSale;
use App\Models\backend\SpotSaleItem;
use App\Models\backend\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpotSaleController extends Controller
{
    public function index()
    {
        return view('backend.modules.spot_sale.index');
    }

    public function listAjax(Request $request)
    {
        $draw      = (int) $request->input('draw');
        $start     = (int) $request->input('start', 0);
        $length    = (int) $request->input('length', 10);
        $orderDir  = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchVal = trim($request->input('search.value', ''));

        $base = SpotSale::query()
            ->select(['id', 'user_id', 'total', 'teacher_id', 'library_id', 'status', 'note', 'created_at'])
            ->with([
                'user:id,name',
                'teacher:id,teacher_name',
                'library:id,library_name',
                'items.product:id,name',
            ])
            // NOTE: withSum() must come AFTER select() — select() replaces
            // the column list and would otherwise wipe the sum subquery.
            ->withSum('items as items_qty', 'quantity');

        $total = (clone $base)->count();

        if ($searchVal !== '') {
            $base->where(function ($q) use ($searchVal) {
                $q->where('note', 'like', "%{$searchVal}%")
                    ->orWhere('id', $searchVal)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$searchVal}%"));
            });
        }

        $filtered = (clone $base)->count();

        $rows = $base
            ->orderBy('id', $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $data = [];
        foreach ($rows as $b) {
            $type = $b->teacher_id ? 'teacher' : ($b->library_id ? 'library' : '—');
            $typeBadge = $type === 'teacher'
                ? '<span class="badge text-sm fw-semibold bg-primary px-12 py-6 radius-4">Teacher</span>'
                : ($type === 'library'
                    ? '<span class="badge text-sm fw-semibold bg-info px-12 py-6 radius-4">Library</span>'
                    : '—');

            $customer = $b->teacher_id
                ? e(optional($b->teacher)->teacher_name ?? ('#'.$b->teacher_id))
                : ($b->library_id
                    ? e(optional($b->library)->library_name ?? ('#'.$b->library_id))
                    : '—');

            $statusBadge = $b->status === 'approved'
                ? '<span class="badge text-sm fw-semibold bg-dark-success-gradient px-20 py-9 radius-4 text-white">Approved</span>'
                : '<button class="badge text-sm fw-semibold bg-dark-warning-gradient px-20 py-9 radius-4 text-white btn-toggle-status"
                     data-url="' . route('spot-sales.toggleStatus', $b->id) . '"
                     data-id="' . $b->id . '"
                     style="border: none; cursor: pointer;">Pending</button>';

            // ---------------- ACTION BUTTONS ----------------
            $actions = '<div class="d-inline-flex align-items-center gap-1">';

            // 👁 View details (always available, like expense print)
            $actions .= '
            <a href="#"
               class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
                      bg-info-focus text-info-main AjaxModal"
               data-ajax-modal="' . route('spot-sales.show', $b->id) . '"
               data-size="lg"
               title="View Details">
                <iconify-icon icon="mdi:eye"></iconify-icon>
            </a>';

            if ($b->status !== 'approved') {
                $actions .= '
            <a href="#"
               class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
                      bg-success-focus text-success-main AjaxModal"
               data-ajax-modal="' . route('spot-sales.editModal', $b->id) . '"
               data-size="lg"
               data-onload="SpotSaleIndex.onLoad"
               data-onsuccess="SpotSaleIndex.onSaved"
               title="Edit Spot Sale">
                <iconify-icon icon="lucide:edit"></iconify-icon>
            </a>

            <a href="#"
               class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
                      bg-danger-focus text-danger-main btn-spot-sale-delete"
               data-url="' . route('spot-sales.destroy', $b->id) . '"
               data-id="' . $b->id . '"
               title="Delete Spot Sale">
                <iconify-icon icon="mdi:delete"></iconify-icon>
            </a>';
            }

            $actions .= '</div>';

            $qty = $b->items_qty ?? $b->items->sum('quantity');

            $data[] = [
                $b->id,
                '<strong>' . e(optional($b->user)->name ?? ('#'.$b->user_id)) . '</strong>',
                $typeBadge,
                $customer,
                e($b->note ?? '—'),
                number_format((float) $qty),
                number_format((float) $b->total, 2),
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

    public function createModal()
    {
        return view('backend.modules.spot_sale.create'); // modal partial
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'user_id'               => 'required|integer|exists:users,id',
            'customer_type'         => 'required|in:teacher,library',
            'teacher_id'            => 'nullable|integer|exists:teachers,id|required_if:customer_type,teacher',
            'library_id'            => 'nullable|integer|exists:libraries,id|required_if:customer_type,library',
            'note'                  => 'nullable|string|max:255',
            'status'                => 'required|in:pending,approved',

            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|integer|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
        ]);

        $productIds = collect($data['items'])->pluck('product_id')->unique()->values();
        $prices     = Product::whereIn('id', $productIds)->pluck('price', 'id');

        if ($prices->count() !== $productIds->count()) {
            return response()->json(['ok' => false, 'msg' => 'Invalid product selected'], 422);
        }

        $lines = [];
        $total = 0;
        foreach ($data['items'] as $item) {
            $price     = (float) ($prices[$item['product_id']] ?? 0);
            $lineTotal = round($price * (int) $item['quantity'], 2);
            $total += $lineTotal;
            $lines[] = [
                'product_id' => (int) $item['product_id'],
                'price'      => $price,
                'quantity'   => (int) $item['quantity'],
                'total'      => $lineTotal,
            ];
        }

        DB::beginTransaction();

        try {
            $spotSale = SpotSale::create([
                'user_id'    => $data['user_id'],
                'total'      => (int) round($total),
                'teacher_id' => $data['customer_type'] === 'teacher' ? $data['teacher_id'] : null,
                'library_id' => $data['customer_type'] === 'library' ? $data['library_id'] : null,
                'status'     => $data['status'],
                'note'       => $data['note'] ?? null,
            ]);

            foreach ($lines as $line) {
                SpotSaleItem::create(['spot_sale_id' => $spotSale->id] + $line);
            }

            DB::commit();

            return response()->json([
                'ok'   => true,
                'msg'  => 'Spot sale saved successfully',
                'data' => ['id' => $spotSale->id, 'status' => $spotSale->status],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['ok' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    public function editModal(SpotSale $spotSale)
    {
        if ($spotSale->status === 'approved') {
            abort(403, 'Approved spot sale cannot be edited');
        }

        $spotSale->load([
            'items.product:id,name,price',
            'user:id,name',
            'teacher:id,teacher_name',
            'library:id,name,library_name',
        ]);

        return view('backend.modules.spot_sale.edit', compact('spotSale'));
    }

    public function show(SpotSale $spotSale)
    {
        $spotSale->load([
            'items.product:id,name,price',
            'user:id,name',
            'teacher:id,teacher_name',
            'library:id,name,library_name',
        ]);

        return view('backend.modules.spot_sale.show', compact('spotSale'));
    }

    public function update(Request $req, SpotSale $spotSale)
    {
        if ($spotSale->status === 'approved') {
            return response()->json(['ok' => false, 'msg' => 'Approved spot sale cannot be modified'], 422);
        }

        $data = $req->validate([
            'user_id'               => 'required|integer|exists:users,id',
            'customer_type'         => 'required|in:teacher,library',
            'teacher_id'            => 'nullable|integer|exists:teachers,id|required_if:customer_type,teacher',
            'library_id'            => 'nullable|integer|exists:libraries,id|required_if:customer_type,library',
            'note'                  => 'nullable|string|max:255',
            'status'                => 'required|in:pending,approved',

            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|integer|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
        ]);

        $productIds = collect($data['items'])->pluck('product_id')->unique()->values();
        $prices     = Product::whereIn('id', $productIds)->pluck('price', 'id');

        if ($prices->count() !== $productIds->count()) {
            return response()->json(['ok' => false, 'msg' => 'Invalid product selected'], 422);
        }

        $lines = [];
        $total = 0;
        foreach ($data['items'] as $item) {
            $price     = (float) ($prices[$item['product_id']] ?? 0);
            $lineTotal = round($price * (int) $item['quantity'], 2);
            $total += $lineTotal;
            $lines[] = [
                'product_id' => (int) $item['product_id'],
                'price'      => $price,
                'quantity'   => (int) $item['quantity'],
                'total'      => $lineTotal,
            ];
        }

        DB::beginTransaction();

        try {
            $spotSale->update([
                'user_id'    => $data['user_id'],
                'total'      => (int) round($total),
                'teacher_id' => $data['customer_type'] === 'teacher' ? $data['teacher_id'] : null,
                'library_id' => $data['customer_type'] === 'library' ? $data['library_id'] : null,
                'status'     => $data['status'],
                'note'       => $data['note'] ?? null,
            ]);

            $spotSale->items()->delete();

            foreach ($lines as $line) {
                $spotSale->items()->create($line);
            }

            DB::commit();

            return response()->json(['ok' => true, 'msg' => 'Spot sale updated successfully', 'id' => $spotSale->id]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['ok' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    public function destroy(SpotSale $spotSale)
    {
        if ($spotSale->status === 'approved') {
            return response()->json(['ok' => false, 'msg' => 'Approved spot sale cant be deleted'], 422);
        }

        $spotSale->items()->delete();
        $spotSale->delete();

        return response()->json(['ok' => true, 'msg' => 'Spot sale deleted']);
    }

    public function toggleStatus(SpotSale $spotSale)
    {
        if ($spotSale->status === 'approved') {
            return response()->json(['ok' => false, 'msg' => 'Spot sale is already approved'], 422);
        }

        $spotSale->update(['status' => 'approved']);

        return response()->json([
            'ok'   => true,
            'msg'  => 'Spot sale approved successfully',
            'data' => ['id' => $spotSale->id, 'status' => $spotSale->status],
        ]);
    }

    public function productPrice(Product $product)
    {
        return response()->json(['ok' => true, 'price' => (float) $product->price]);
    }
}
