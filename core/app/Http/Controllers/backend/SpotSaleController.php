<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\CourseClass;
use App\Models\backend\CurrentFund;
use App\Models\backend\CurrentStock;
use App\Models\backend\FundTransferLedger;
use App\Models\backend\Institution;
use App\Models\backend\Library;
use App\Models\backend\Product;
use App\Models\backend\SpotSale;
use App\Models\backend\SpotSaleCustomer;
use App\Models\backend\SpotSaleItem;
use App\Models\backend\StockLedger;
use App\Models\backend\Student;
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
            ->select(['id', 'user_id', 'total', 'subtotal', 'discount', 'teacher_id', 'library_id', 'student_id', 'customer_id', 'status', 'note', 'created_at'])
            ->with([
                'user:id,name',
                'customer.institution:id,institution_name',
                'customer.courseClass:id,name',
                'teacher:id,teacher_name',
                'library:id,library_name',
                'student:id,student_name',
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
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$searchVal}%"))
                    ->orWhereHas('customer', function ($c) use ($searchVal) {
                        $c->where('name', 'like', "%{$searchVal}%")
                            ->orWhere('phone', 'like', "%{$searchVal}%");
                    })
                    ->orWhereHas('teacher', fn($t) => $t->where('teacher_name', 'like', "%{$searchVal}%"))
                    ->orWhereHas('library', fn($l) => $l->where('library_name', 'like', "%{$searchVal}%"))
                    ->orWhereHas('student', fn($s) => $s->where('student_name', 'like', "%{$searchVal}%"));
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
            $type = $b->customerType() ?? '—';
            $typeBadge = $type === 'teacher'
                ? '<span class="badge text-sm fw-semibold bg-primary px-12 py-6 radius-4">Teacher</span>'
                : ($type === 'library'
                    ? '<span class="badge text-sm fw-semibold bg-info px-12 py-6 radius-4">Library</span>'
                    : ($type === 'student'
                        ? '<span class="badge text-sm fw-semibold bg-success px-12 py-6 radius-4">Student</span>' : '—'));

            $customer = e($b->customerName());
            $phone = $b->customerPhone();
            if ($phone && $phone !== '—') {
                $customer .= '<br><small class="text-muted"><i class="ri-phone-line"></i> ' . e($phone) . '</small>';
            }

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
            <a href="' . route('spot-sales.edit', $b->id) . '"
               class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center
                      bg-success-focus text-success-main"
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
                '<strong>' . e(optional($b->user)->name ?? ('#' . $b->user_id)) . '</strong>',
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

    public function create()
    {
        $classes = CourseClass::where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('backend.modules.spot_sale.create', compact('classes'));
    }

    public function createModal()
    {
        return $this->create();
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'user_id'               => 'required|integer|exists:users,id',
            'customer_type'         => 'required|in:teacher,library,student',

            'name'                  => 'nullable|string|max:255',
            'phone'                 => 'nullable|string|max:50',
            'address'               => 'nullable|string|max:255',
            'institution_id'        => 'nullable|integer|exists:institutions,id',
            'course_class_id'       => 'nullable|integer|exists:course_class,id',

            'teacher_name'          => 'nullable|string|max:255',
            'teacher_phone'         => 'nullable|string|max:50',
            'teacher_institution_id'=> 'nullable|integer|exists:institutions,id',

            'library_name'          => 'nullable|string|max:255',
            'library_phone'         => 'nullable|string|max:50',
            'library_address'       => 'nullable|string|max:255',

            'student_name'          => 'nullable|string|max:255',
            'student_phone'         => 'nullable|string|max:50',
            'student_course_class_id'=> 'nullable|integer|exists:course_class,id',
            'student_institution_id'=> 'nullable|integer|exists:institutions,id',

            'teacher_id'            => 'nullable|integer|exists:teachers,id',
            'library_id'            => 'nullable|integer|exists:libraries,id',

            'note'                  => 'nullable|string|max:255',
            'status'                => 'required|in:pending,approved',
            'discount'              => 'nullable|numeric|min:0',
            'subtotal'              => 'nullable|numeric|min:0',

            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|integer|exists:products,id',
            'items.*.quantity'      => 'required|integer|min:1',
        ]);

        $customerType = $data['customer_type'];
        $customerName = trim((string) (
            $req->input('name')
            ?? $req->input($customerType . '_name')
            ?? ($customerType === 'student' ? $req->input('student_name') : '')
        ));

        if ($customerName === '') {
            $fieldKey = match ($customerType) {
                'teacher' => 'teacher_name',
                'library' => 'library_name',
                'student' => 'student_name',
                default   => 'name',
            };
            return response()->json([
                'ok' => false,
                'msg' => ucfirst($customerType) . ' name is required.',
                'errors' => [
                    $fieldKey => [ucfirst($customerType) . ' name is required.'],
                    'name' => [ucfirst($customerType) . ' name is required.'],
                ]
            ], 422);
        }

        $phone = $req->input('phone') ?? $req->input($customerType . '_phone');
        $address = $req->input('address') ?? $req->input($customerType . '_address');
        $institutionId = $req->input('institution_id') ?? $req->input($customerType . '_institution_id');
        $courseClassId = $req->input('course_class_id') ?? $req->input('student_course_class_id');

        if ($customerType === 'student') {
            if (!$courseClassId) {
                return response()->json([
                    'ok' => false,
                    'msg' => 'Class is required for students.',
                    'errors' => [
                        'course_class_id' => ['Course class is required.'],
                        'student_course_class_id' => ['Course class is required.']
                    ]
                ], 422);
            }
            if (!$institutionId) {
                return response()->json([
                    'ok' => false,
                    'msg' => 'Institution is required for students.',
                    'errors' => [
                        'institution_id' => ['Institution is required.'],
                        'student_institution_id' => ['Institution is required.']
                    ]
                ], 422);
            }
        }

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
            $customer = SpotSaleCustomer::create([
                'user_id'         => $data['user_id'],
                'customer_type'   => $customerType,
                'name'            => $customerName,
                'phone'           => $phone ?: null,
                'institution_id'  => $institutionId ?: null,
                'course_class_id' => $courseClassId ?: null,
                'address'         => $address ?: null,
            ]);

            $subtotal = round($total, 2);
            $discount = max(0, (float) $req->input('discount', 0));
            if ($discount > $subtotal) {
                $discount = $subtotal;
            }
            $grandTotal = round(max(0, $subtotal - $discount), 2);

            $spotSale = SpotSale::create([
                'user_id'     => $data['user_id'],
                'customer_id' => $customer->id,
                'subtotal'    => $subtotal,
                'discount'    => $discount,
                'total'       => $grandTotal,
                'status'      => $data['status'],
                'note'        => $data['note'] ?? null,
            ]);

            foreach ($lines as $line) {
                SpotSaleItem::create(['spot_sale_id' => $spotSale->id] + $line);
            }

            if ($data['status'] === 'approved') {
                $this->processSpotSaleApproval($spotSale);
            }

            DB::commit();

            return response()->json([
                'ok'       => true,
                'msg'      => 'Spot sale saved successfully',
                'data'     => ['id' => $spotSale->id, 'status' => $spotSale->status],
                'redirect' => route('spot-sales.index'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['ok' => false, 'msg' => $e->getMessage()], 500);
        }
    }

    public function edit(SpotSale $spotSale)
    {
        if ($spotSale->status === 'approved') {
            abort(403, 'Approved spot sale cannot be edited');
        }

        $spotSale->load([
            'items.product:id,name,price,thumbnail_image,category_id,subcategory_id',
            'items.product.category:id,name',
            'items.product.subcategory:id,name',
            'user:id,name',
            'customer.institution:id,institution_name',
            'customer.courseClass:id,name',
            'teacher:id,teacher_name',
            'library:id,name,library_name',
            'student.courseClass:id,name',
            'student.institution:id,institution_name',
        ]);

        $classes = CourseClass::where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('backend.modules.spot_sale.edit', compact('spotSale', 'classes'));
    }

    public function editModal(SpotSale $spotSale)
    {
        return $this->edit($spotSale);
    }

    public function show(SpotSale $spotSale)
    {
        $spotSale->load([
            'items.product:id,name,price',
            'user:id,name',
            'customer.institution:id,institution_name',
            'customer.courseClass:id,name',
            'teacher:id,teacher_name',
            'library:id,name,library_name',
            'student.courseClass:id,name',
            'student.institution:id,institution_name',
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
            'customer_type'         => 'required|in:teacher,library,student',

            'name'                  => 'nullable|string|max:255',
            'phone'                 => 'nullable|string|max:50',
            'address'               => 'nullable|string|max:255',
            'institution_id'        => 'nullable|integer|exists:institutions,id',
            'course_class_id'       => 'nullable|integer|exists:course_class,id',

            'teacher_name'          => 'nullable|string|max:255',
            'teacher_phone'         => 'nullable|string|max:50',
            'teacher_institution_id'=> 'nullable|integer|exists:institutions,id',

            'library_name'          => 'nullable|string|max:255',
            'library_phone'         => 'nullable|string|max:50',
            'library_address'       => 'nullable|string|max:255',

            'student_name'          => 'nullable|string|max:255',
            'student_phone'         => 'nullable|string|max:50',
            'student_course_class_id'=> 'nullable|integer|exists:course_class,id',
            'student_institution_id'=> 'nullable|integer|exists:institutions,id',

            'teacher_id'            => 'nullable|integer|exists:teachers,id',
            'library_id'            => 'nullable|integer|exists:libraries,id',

            'note'                  => 'nullable|string|max:255',
            'status'                => 'required|in:pending,approved',
            'discount'              => 'nullable|numeric|min:0',
            'subtotal'              => 'nullable|numeric|min:0',

            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|integer|exists:products,id',
            'items.*.quantity'      => 'required|integer|min:1',
        ]);

        $customerType = $data['customer_type'];
        $customerName = trim((string) (
            $req->input('name')
            ?? $req->input($customerType . '_name')
            ?? ($customerType === 'student' ? $req->input('student_name') : '')
        ));

        if ($customerName === '') {
            $fieldKey = match ($customerType) {
                'teacher' => 'teacher_name',
                'library' => 'library_name',
                'student' => 'student_name',
                default   => 'name',
            };
            return response()->json([
                'ok' => false,
                'msg' => ucfirst($customerType) . ' name is required.',
                'errors' => [
                    $fieldKey => [ucfirst($customerType) . ' name is required.'],
                    'name' => [ucfirst($customerType) . ' name is required.'],
                ]
            ], 422);
        }

        $phone = $req->input('phone') ?? $req->input($customerType . '_phone');
        $address = $req->input('address') ?? $req->input($customerType . '_address');
        $institutionId = $req->input('institution_id') ?? $req->input($customerType . '_institution_id');
        $courseClassId = $req->input('course_class_id') ?? $req->input('student_course_class_id');

        if ($customerType === 'student') {
            if (!$courseClassId) {
                return response()->json([
                    'ok' => false,
                    'msg' => 'Class is required for students.',
                    'errors' => [
                        'course_class_id' => ['Course class is required.'],
                        'student_course_class_id' => ['Course class is required.']
                    ]
                ], 422);
            }
            if (!$institutionId) {
                return response()->json([
                    'ok' => false,
                    'msg' => 'Institution is required for students.',
                    'errors' => [
                        'institution_id' => ['Institution is required.'],
                        'student_institution_id' => ['Institution is required.']
                    ]
                ], 422);
            }
        }

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
            if ($spotSale->customer_id && $spotSale->customer) {
                $spotSale->customer->update([
                    'user_id'         => $data['user_id'],
                    'customer_type'   => $customerType,
                    'name'            => $customerName,
                    'phone'           => $phone ?: null,
                    'institution_id'  => $institutionId ?: null,
                    'course_class_id' => $courseClassId ?: null,
                    'address'         => $address ?: null,
                ]);
                $customerId = $spotSale->customer_id;
            } else {
                $customer = SpotSaleCustomer::create([
                    'user_id'         => $data['user_id'],
                    'customer_type'   => $customerType,
                    'name'            => $customerName,
                    'phone'           => $phone ?: null,
                    'institution_id'  => $institutionId ?: null,
                    'course_class_id' => $courseClassId ?: null,
                    'address'         => $address ?: null,
                ]);
                $customerId = $customer->id;
            }

            $subtotal = round($total, 2);
            $discount = max(0, (float) $req->input('discount', 0));
            if ($discount > $subtotal) {
                $discount = $subtotal;
            }
            $grandTotal = round(max(0, $subtotal - $discount), 2);

            $spotSale->update([
                'user_id'     => $data['user_id'],
                'customer_id' => $customerId,
                'subtotal'    => $subtotal,
                'discount'    => $discount,
                'total'       => $grandTotal,
                'status'      => $data['status'],
                'note'        => $data['note'] ?? null,
            ]);

            $spotSale->items()->delete();

            foreach ($lines as $line) {
                $spotSale->items()->create($line);
            }

            if ($data['status'] === 'approved') {
                $this->processSpotSaleApproval($spotSale);
            }

            DB::commit();

            return response()->json([
                'ok'       => true,
                'msg'      => 'Spot sale updated successfully',
                'id'       => $spotSale->id,
                'redirect' => route('spot-sales.index'),
            ]);
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
        if ($spotSale->customer) {
            $spotSale->customer->delete();
        }
        $spotSale->delete();

        return response()->json(['ok' => true, 'msg' => 'Spot sale deleted']);
    }

    public function toggleStatus(SpotSale $spotSale)
    {
        if ($spotSale->status === 'approved') {
            return response()->json(['ok' => false, 'msg' => 'Spot sale is already approved'], 422);
        }

        try {
            DB::transaction(function () use ($spotSale) {
                $this->processSpotSaleApproval($spotSale);
            });

            return response()->json([
                'ok'   => true,
                'msg'  => 'Spot sale approved successfully',
                'data' => ['id' => $spotSale->id, 'status' => $spotSale->status],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'msg' => $e->getMessage()], 422);
        }
    }

    private function processSpotSaleApproval(SpotSale $spotSale): void
    {
        $spotSale->loadMissing('items.product');

        if ($spotSale->items->isEmpty()) {
            throw new \Exception('No items found in this spot sale to approve.');
        }

        // 1. Verify and deduct MR CurrentStock and record StockLedger OUT
        foreach ($spotSale->items as $item) {
            $stock = CurrentStock::where('user_id', $spotSale->user_id)
                ->where('product_id', $item->product_id)
                ->lockForUpdate()
                ->first();

            $available = $stock ? (int) $stock->sale_stock : 0;
            $requested = (int) $item->quantity;

            if ($available < $requested) {
                $productName = $item->product?->name ?? ('Product #' . $item->product_id);
                throw new \Exception("Insufficient sale stock for {$productName} (Available: {$available}, Requested: {$requested})");
            }

            $stock->decrement('sale_stock', $item->quantity);
            $stock->current_stock = (int) $stock->specimen_stock + (int) $stock->sale_stock;
            $stock->save();

            StockLedger::create([
                'ref_type'   => 'SpotSale',
                'ref_id'     => $spotSale->id,
                'product_id' => $item->product_id,
                'stock_type' => 'sale',
                'quantity'   => $item->quantity,
                'direction'  => 'OUT',
                'note'       => 'Spot Sale #' . $spotSale->id . ($spotSale->note ? ': ' . $spotSale->note : ''),
            ]);
        }

        // 2. Increment MR CurrentFund balance with collected sale cash and record FundTransferLedger IN
        $saleTotal = (float) $spotSale->total;
        if ($saleTotal > 0) {
            $currentFund = CurrentFund::where('user_id', $spotSale->user_id)
                ->lockForUpdate()
                ->first();

            if ($currentFund) {
                $currentFund->increment('balance', $saleTotal);
            } else {
                CurrentFund::create([
                    'user_id' => $spotSale->user_id,
                    'balance' => $saleTotal,
                ]);
            }

            FundTransferLedger::create([
                'ref_type'  => 'SpotSale',
                'ref_id'    => $spotSale->id,
                'amount'    => $saleTotal,
                'direction' => 'IN',
                'note'      => 'Spot sale cash collection #' . $spotSale->id,
            ]);
        }

        $spotSale->status = 'approved';
        $spotSale->save();
    }

    public function productPrice(Product $product)
    {
        return response()->json(['ok' => true, 'price' => (float) $product->price]);
    }
}
