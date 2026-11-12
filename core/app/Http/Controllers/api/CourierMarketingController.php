<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourierMarketingResource;
use App\Models\backend\CourierMarketing;
use App\Models\backend\CourierMarketingItem;
use App\Models\backend\MarketingRepresentative;
use App\Models\backend\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CourierMarketingController extends Controller
{
    public function index(Request $request)
    {
        $mrIds = $this->activeMrIds();

        if (empty($mrIds)) {
            return $this->missingMrResponse();
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);

        $query = CourierMarketing::with($this->relations())
            ->whereIn('marketing_representative_id', $mrIds);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', (int) $request->input('teacher_id'));
        }

        if ($request->filled('institution_id')) {
            $query->where('institution_id', (int) $request->input('institution_id'));
        }

        if ($request->filled('courier_company')) {
            $company = trim((string) $request->input('courier_company'));
            $query->where('courier_company', 'like', "%{$company}%");
        }

        if ($request->filled('from_date')) {
            $query->whereDate('dispatch_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('dispatch_date', '<=', $request->input('to_date'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('courier_company', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhereHas('teacher', function ($tq) use ($search) {
                        $tq->where('teacher_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('institution', function ($iq) use ($search) {
                        $iq->where('institution_name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('items.product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('product_code', 'like', "%{$search}%");
                    });
            });
        }

        $records = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success'    => true,
            'message'    => 'Courier marketings fetched successfully.',
            'data'       => CourierMarketingResource::collection($records->items()),
            'pagination' => [
                'current_page' => $records->currentPage(),
                'per_page'     => $records->perPage(),
                'total'        => $records->total(),
                'last_page'    => $records->lastPage(),
                'from'         => $records->firstItem(),
                'to'           => $records->lastItem(),
            ],
        ]);
    }

    public function statuses()
    {
        $items = [];
        foreach (CourierMarketing::STATUSES as $key => $label) {
            $items[] = [
                'key'   => $key,
                'label' => $label,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Courier marketing statuses fetched successfully.',
            'data'    => $items,
        ]);
    }

    public function store(Request $request)
    {
        $mr = $this->latestMr();

        if (!$mr) {
            return $this->missingMrResponse();
        }

        if (!$request->has('items') && $request->has('product_items')) {
            $request->merge(['items' => $request->input('product_items')]);
        }

        $data = $request->validate([
            'teacher_id'         => ['required', 'integer', Rule::exists('teachers', 'id')->whereNull('deleted_at')],
            'institution_id'     => ['nullable', 'integer', Rule::exists('institutions', 'id')->whereNull('deleted_at')],
            'courier_company'    => ['nullable', 'string', 'max:150'],
            'dispatch_date'      => ['nullable', 'date'],
            'delivery_date'      => ['nullable', 'date'],
            'note'               => ['nullable', 'string', 'max:1000'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
        ]);

        $teacher       = Teacher::query()->findOrFail($data['teacher_id']);
        $institutionId = $data['institution_id'] ?? $teacher->institution_id;
        $mergedItems   = $this->mergeItems($data['items']);
        $totalQty      = max(1, (int) array_sum($mergedItems));

        $dispatchDate = !empty($data['dispatch_date'])
            ? Carbon::parse($data['dispatch_date'])->toDateString()
            : now()->toDateString();

        $deliveryDate = !empty($data['delivery_date'])
            ? Carbon::parse($data['delivery_date'])->toDateString()
            : null;

        $courierMarketing = DB::transaction(function () use ($mr, $teacher, $institutionId, $data, $mergedItems, $totalQty, $dispatchDate, $deliveryDate) {
            $payload = [
                'marketing_representative_id' => $mr->id,
                'teacher_id'                  => $teacher->id,
                'institution_id'              => $institutionId,
                'courier_company'             => $data['courier_company'] ?? null,
                'dispatch_date'               => $dispatchDate,
                'delivery_date'               => $deliveryDate,
                'status'                      => 'dispatched',
                'note'                        => $data['note'] ?? null,
            ];

            if (Schema::hasColumn('courier_marketings', 'tracking_number')) {
                $payload['tracking_number'] = null;
            }
            if (Schema::hasColumn('courier_marketings', 'request_date')) {
                $payload['request_date'] = $dispatchDate;
            }
            if (Schema::hasColumn('courier_marketings', 'quantity')) {
                $payload['quantity'] = $totalQty;
            }
            if (Schema::hasColumn('courier_marketings', 'created_by')) {
                $payload['created_by'] = auth()->id();
            }

            $cm = CourierMarketing::create($payload);

            foreach ($mergedItems as $productId => $qty) {
                CourierMarketingItem::create([
                    'courier_marketing_id' => $cm->id,
                    'product_id'           => (int) $productId,
                    'quantity'             => (int) $qty,
                ]);
            }

            return $cm;
        });

        $courierMarketing->load($this->relations());

        return response()->json([
            'success' => true,
            'message' => 'Courier marketing created successfully.',
            'data'    => new CourierMarketingResource($courierMarketing),
        ], 201);
    }

    public function show($id)
    {
        $mrIds = $this->activeMrIds();

        if (empty($mrIds)) {
            return $this->missingMrResponse();
        }

        $courierMarketing = $this->findOwnedCourier((int) $id, $mrIds, true);

        if (!$courierMarketing) {
            return $this->notFoundResponse();
        }

        return response()->json([
            'success' => true,
            'message' => 'Courier marketing fetched successfully.',
            'data'    => new CourierMarketingResource($courierMarketing),
        ]);
    }

    public function update(Request $request, $id)
    {
        $mrIds = $this->activeMrIds();

        if (empty($mrIds)) {
            return $this->missingMrResponse();
        }

        $courierMarketing = $this->findOwnedCourier((int) $id, $mrIds);

        if (!$courierMarketing) {
            return $this->notFoundResponse();
        }

        if (!in_array($courierMarketing->status, ['pending', 'dispatched'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending or dispatched courier entries can be edited.',
            ], 403);
        }

        if (!$request->has('items') && $request->has('product_items')) {
            $request->merge(['items' => $request->input('product_items')]);
        }

        $data = $request->validate([
            'teacher_id'         => ['nullable', 'integer', Rule::exists('teachers', 'id')->whereNull('deleted_at')],
            'institution_id'     => ['nullable', 'integer', Rule::exists('institutions', 'id')->whereNull('deleted_at')],
            'courier_company'    => ['nullable', 'string', 'max:150'],
            'dispatch_date'      => ['nullable', 'date'],
            'delivery_date'      => ['nullable', 'date'],
            'note'               => ['nullable', 'string', 'max:1000'],
            'items'              => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity'   => ['required_with:items', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($courierMarketing, $data) {
            $updateData = [];

            if (array_key_exists('teacher_id', $data) && $data['teacher_id'] !== null) {
                $teacher                    = Teacher::query()->findOrFail($data['teacher_id']);
                $updateData['teacher_id']   = $teacher->id;
                $updateData['institution_id'] = $data['institution_id'] ?? $teacher->institution_id;
            } elseif (array_key_exists('institution_id', $data)) {
                $updateData['institution_id'] = $data['institution_id'];
            }

            if (array_key_exists('courier_company', $data)) {
                $updateData['courier_company'] = $data['courier_company'];
            }
            if (!empty($data['dispatch_date'])) {
                $updateData['dispatch_date'] = Carbon::parse($data['dispatch_date'])->toDateString();
            }
            if (array_key_exists('delivery_date', $data)) {
                $updateData['delivery_date'] = $data['delivery_date']
                    ? Carbon::parse($data['delivery_date'])->toDateString()
                    : null;
            }
            if (array_key_exists('note', $data)) {
                $updateData['note'] = $data['note'];
            }

            if (isset($data['items'])) {
                $mergedItems = $this->mergeItems($data['items']);
                $totalQty    = max(1, (int) array_sum($mergedItems));

                if (Schema::hasColumn('courier_marketings', 'quantity')) {
                    $updateData['quantity'] = $totalQty;
                }

                $courierMarketing->items()->delete();

                foreach ($mergedItems as $productId => $qty) {
                    CourierMarketingItem::create([
                        'courier_marketing_id' => $courierMarketing->id,
                        'product_id'           => (int) $productId,
                        'quantity'             => (int) $qty,
                    ]);
                }
            }

            if (!empty($updateData)) {
                $courierMarketing->update($updateData);
            }
        });

        $courierMarketing->load($this->relations());

        return response()->json([
            'success' => true,
            'message' => 'Courier marketing updated successfully.',
            'data'    => new CourierMarketingResource($courierMarketing),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $mrIds = $this->activeMrIds();

        if (empty($mrIds)) {
            return $this->missingMrResponse();
        }

        $courierMarketing = $this->findOwnedCourier((int) $id, $mrIds);

        if (!$courierMarketing) {
            return $this->notFoundResponse();
        }

        $data = $request->validate([
            'status'                    => ['required', Rule::in(['dispatched', 'in_transit', 'delivered', 'partially_received', 'received', 'failed', 'returned'])],
            'delivery_date'             => ['nullable', 'date'],
            'note'                      => ['nullable', 'string', 'max:1000'],
            'items'                     => ['nullable', 'array'],
            'items.*.id'                => ['required_with:items', 'integer'],
            'items.*.received_quantity' => ['required_with:items', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($courierMarketing, $data) {
            $updateData = [
                'status' => $data['status'],
            ];

            if (!empty($data['delivery_date'])) {
                $updateData['delivery_date'] = Carbon::parse($data['delivery_date'])->toDateString();
            }

            if (in_array($data['status'], ['received', 'partially_received', 'delivered'], true)) {
                if ($this->canStoreUserIdInReceivedBy()) {
                    $updateData['received_by'] = auth()->id();
                }
                if (Schema::hasColumn('courier_marketings', 'received_at')) {
                    $updateData['received_at'] = now();
                }
            }

            if (!empty($data['note'])) {
                $author       = auth()->user()->name ?? 'MR App';
                $timeStr      = now()->format('d M, Y h:i A');
                $newEntry     = "[{$timeStr} by {$author}]: " . trim((string) $data['note']);
                $existingNote = trim((string) $courierMarketing->note);
                $updateData['note'] = $existingNote !== '' ? $existingNote . "\n" . $newEntry : $newEntry;
            }

            $courierMarketing->update($updateData);

            if (!empty($data['items'])) {
                foreach ($data['items'] as $itemPayload) {
                    CourierMarketingItem::where('courier_marketing_id', $courierMarketing->id)
                        ->where('id', (int) $itemPayload['id'])
                        ->update([
                            'received_quantity' => (int) $itemPayload['received_quantity'],
                        ]);
                }
            }
        });

        $courierMarketing->load($this->relations());

        return response()->json([
            'success' => true,
            'message' => 'Courier marketing status updated successfully.',
            'data'    => new CourierMarketingResource($courierMarketing),
        ]);
    }

    public function destroy($id)
    {
        $mrIds = $this->activeMrIds();

        if (empty($mrIds)) {
            return $this->missingMrResponse();
        }

        $courierMarketing = $this->findOwnedCourier((int) $id, $mrIds);

        if (!$courierMarketing) {
            return $this->notFoundResponse();
        }

        if (!in_array($courierMarketing->status, ['pending', 'dispatched'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending or dispatched courier entries can be deleted.',
            ], 403);
        }

        DB::transaction(function () use ($courierMarketing) {
            $courierMarketing->items()->delete();
            $courierMarketing->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Courier marketing deleted successfully.',
        ]);
    }

    private function activeMrIds(): array
    {
        return MarketingRepresentative::where('user_id', auth()->id())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function latestMr(): ?MarketingRepresentative
    {
        return MarketingRepresentative::where('user_id', auth()->id())
            ->latest('id')
            ->first();
    }

    private function findOwnedCourier(int $id, array $mrIds, bool $withRelations = false): ?CourierMarketing
    {
        $query = CourierMarketing::query()
            ->where('id', $id)
            ->whereIn('marketing_representative_id', $mrIds);

        if ($withRelations) {
            $query->with($this->relations());
        }

        return $query->first();
    }

    private function mergeItems(array $items): array
    {
        $merged = [];
        foreach ($items as $item) {
            $pid          = (int) $item['product_id'];
            $merged[$pid] = ($merged[$pid] ?? 0) + (int) $item['quantity'];
        }

        return $merged;
    }

    private function canStoreUserIdInReceivedBy(): bool
    {
        if (!Schema::hasColumn('courier_marketings', 'received_by')) {
            return false;
        }

        $type = strtolower((string) Schema::getColumnType('courier_marketings', 'received_by'));

        return in_array($type, ['int', 'integer', 'bigint', 'smallint', 'tinyint', 'mediumint'], true);
    }

    private function relations(): array
    {
        return [
            'representative.user:id,name,phone',
            'teacher.institution.upazila',
            'teacher.institution.district',
            'teacher.institution.division',
            'teacher.upazila',
            'teacher.district',
            'teacher.division',
            'institution.upazila',
            'institution.district',
            'institution.division',
            'items.product:id,name,slug,product_code,edition,price,thumbnail_image,brand_id',
        ];
    }

    private function missingMrResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Marketing representative profile not found.',
        ], 404);
    }

    private function notFoundResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'Courier marketing entry not found.',
        ], 404);
    }
}
