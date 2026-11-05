<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\BookRequest;
use App\Models\backend\BookRequestItem;
use App\Models\backend\CourierMarketing;
use App\Models\backend\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;


class CourierMarketingController extends Controller
{
    public function index()
    {
        return view('backend.modules.courier_marketing.index');
    }

    /**
     * Create modal.
     */
    public function createModal()
    {
        return view('backend.modules.courier_marketing.create_modal');
    }

    /**
     * Show courier details.
     */
    public function show(CourierMarketing $courierMarketing)
    {
        $courierMarketing->load([
            'representative.user',
            'teacher',
            'institution',
            'creator',
            'receiver',
            'items.product',
            'items.bookRequestItem',
        ]);

        return view(
            'backend.modules.courier_marketing.show',
            compact('courierMarketing')
        );
    }

    /**
     * Edit modal.
     *
     * Only pending courier entries can be edited.
     */
    public function editModal(CourierMarketing $courierMarketing)
    {
        if ($courierMarketing->status !== 'pending') {
            return response()->json([
                'ok' => false,
                'msg' => 'Only pending courier entries can be edited.',
            ], 422);
        }

        $courierMarketing->load([
            'representative.user',
            'teacher',
            'institution',
            'items.product',
            'items.bookRequestItem',
        ]);

        return view(
            'backend.modules.courier_marketing.edit_modal',
            compact('courierMarketing')
        );
    }

    /**
     * Approved Book Request Select2.
     *
     * One result represents one request group.
     */
    // public function approvedRequestsSelect2(Request $request)
    // {
    //     $term = trim($request->input('q', ''));

    //     $query = DB::table('book_requests as br')
    //         ->leftJoin(
    //             'marketing_representatives as mr',
    //             'mr.id',
    //             '=',
    //             'br.marketing_representative_id'
    //         )
    //         ->leftJoin('users as u', 'u.id', '=', 'mr.user_id')
    //         ->where('br.status', 'approved')
    //         ->whereNull('br.deleted_at')
    //         ->selectRaw("
    //             MIN(br.id) as id,
    //             COALESCE(
    //                 br.request_group_id,
    //                 CONCAT('legacy-', br.id)
    //             ) as group_key,
    //             MAX(br.request_date) as request_date,
    //             MAX(br.marketing_representative_id) as representative_id,
    //             MAX(u.name) as representative_name,
    //             MAX(mr.employee_id) as employee_id
    //         ")
    //         ->groupByRaw("
    //             COALESCE(
    //                 br.request_group_id,
    //                 CONCAT('legacy-', br.id)
    //             )
    //         ");

    //     if ($term !== '') {
    //         $query->havingRaw(
    //             "CAST(MIN(br.id) AS CHAR) LIKE ? OR MAX(u.name) LIKE ? OR MAX(mr.employee_id) LIKE ?",
    //             ["%{$term}%", "%{$term}%", "%{$term}%"]
    //         );
    //     }

    //     $requests = $query
    //         ->orderByDesc('id')
    //         ->limit(30)
    //         ->get();

    //     return response()->json([
    //         'results' => $requests->map(function ($item) {
    //             $label = 'Request #' . $item->id;

    //             if ($item->representative_name) {
    //                 $label .= ' - ' . $item->representative_name;
    //             }

    //             if ($item->employee_id) {
    //                 $label .= ' (' . $item->employee_id . ')';
    //             }

    //             return [
    //                 'id' => $item->id,
    //                 'text' => $label,
    //             ];
    //         })->values(),
    //     ]);
    // }

    public function approvedRequestsSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));

        $query = DB::table('book_requests as br')
            ->leftJoin(
                'marketing_representatives as mr',
                'mr.id',
                '=',
                'br.marketing_representative_id'
            )
            ->leftJoin('users as u', 'u.id', '=', 'mr.user_id')
            ->where('br.status', 'approved')
            ->whereNull('br.deleted_at')
            ->selectRaw("
            MIN(br.id) as id,
            COALESCE(
                br.request_group_id,
                CONCAT('legacy-', br.id)
            ) as group_key,
            MAX(br.request_date) as request_date,
            MAX(br.marketing_representative_id) as representative_id,
            MAX(u.name) as representative_name,
            MAX(mr.employee_id) as employee_id
        ")
            ->groupByRaw("
            COALESCE(
                br.request_group_id,
                CONCAT('legacy-', br.id)
            )
        ");

        if ($term !== '') {
            $query->havingRaw(
                "CAST(MIN(br.id) AS CHAR) LIKE ? OR MAX(u.name) LIKE ? OR MAX(mr.employee_id) LIKE ?",
                ["%{$term}%", "%{$term}%", "%{$term}%"]
            );
        }

        $requests = $query
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return response()->json([
            'results' => $requests->map(function ($item) {
                $label = 'Request #' . $item->id;

                if ($item->representative_name) {
                    $label .= ' - ' . $item->representative_name;
                }

                if ($item->employee_id) {
                    $label .= ' (' . $item->employee_id . ')';
                }

                return [
                    'id' => $item->id,
                    'text' => $label,
                ];
            })->values(),
        ]);
    }
    /**
     * Get products and available quantities
     * for a selected approved Book Request.
     */
    public function requestItems(Request $request)
    {
        $request->validate([
            'book_request_id' => [
                'required',
                'integer',
                'exists:book_requests,id',
            ],
        ]);

        $sourceRequest = BookRequest::query()
            ->where('id', $request->book_request_id)
            ->where('status', 'Delivered')
            ->firstOrFail();

        $sourceRequests = $this->getApprovedRequestGroup($sourceRequest);

        $sourceRequestIds = $sourceRequests->pluck('id');

        $items = BookRequestItem::query()
            ->with('product')
            ->whereIn('book_request_id', $sourceRequestIds)
            ->get();

        $result = $items->map(function ($item) {
            $dispatched = $this->getDispatchedQuantity($item->id);

            $approvedQuantity = (int) $item->quantity;
            $availableQuantity = max(
                0,
                $approvedQuantity - $dispatched
            );

            return [
                'book_request_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? 'Unknown Product',
                'approved_quantity' => $approvedQuantity,
                'previously_dispatched' => $dispatched,
                'available_quantity' => $availableQuantity,
            ];
        })->values();

        return response()->json([
            'ok' => true,
            'request_id' => $sourceRequest->id,
            'request_group_id' => $sourceRequest->request_group_id,
            'marketing_representative_id' =>
            $sourceRequest->marketing_representative_id,
            'items' => $result,
        ]);
    }

    /**
     * Teacher Select2.
     */
    public function teachersSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));

        $teachers = Teacher::query()
            ->with('institution')
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($builder) use ($term) {
                    $builder->where('teacher_name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return response()->json([
            'results' => $teachers->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'text' => $teacher->teacher_name
                        . (
                            $teacher->institution?->institution_name
                            ? ' - ' . $teacher->institution->institution_name
                            : ''
                        ),
                    'institution_id' => $teacher->institution_id,
                ];
            })->values(),
        ]);
    }

    /**
     * Store courier dispatch.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'book_request_id' => [
                'required',
                'integer',
                'exists:book_requests,id',
            ],
            'teacher_id' => [
                'required',
                'integer',
                Rule::exists('teachers', 'id'),
            ],
            'courier_company' => [
                'nullable',
                'string',
                'max:150',
            ],
            'tracking_number' => [
                'nullable',
                'string',
                'max:150',
            ],
            'dispatch_date' => [
                'required',
                'date',
            ],
            'delivery_date' => [
                'nullable',
                'date',
                'after_or_equal:dispatch_date',
            ],
            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'product_items' => [
                'required',
                'array',
                'min:1',
            ],
            'product_items.*.book_request_item_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('book_request_items', 'id'),
            ],
            'product_items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $courierMarketing = DB::transaction(function () use ($data) {

            /*
             * Lock source request rows to prevent concurrent
             * dispatch creation from the same request group.
             */
            $sourceRequest = BookRequest::query()
                ->where('id', $data['book_request_id'])
                ->where('status', 'approved')
                ->lockForUpdate()
                ->firstOrFail();

            $sourceRequests = $this->getApprovedRequestGroup(
                $sourceRequest,
                true
            );

            $sourceRequestIds = $sourceRequests->pluck('id');

            $representativeId = (int) $sourceRequest
                ->marketing_representative_id;

            $teacher = Teacher::query()
                ->findOrFail($data['teacher_id']);

            $sourceItems = BookRequestItem::query()
                ->whereIn('book_request_id', $sourceRequestIds)
                ->whereIn(
                    'id',
                    collect($data['product_items'])
                        ->pluck('book_request_item_id')
                )
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($sourceItems->count() !== count($data['product_items'])) {
                throw ValidationException::withMessages([
                    'product_items' => 'One or more selected products do not belong to this approved request.',
                ]);
            }

            /*
             * Validate remaining quantity per original request item.
             */
            foreach ($data['product_items'] as $index => $item) {
                $sourceItem = $sourceItems->get(
                    (int) $item['book_request_item_id']
                );

                $alreadyDispatched = $this->getDispatchedQuantity(
                    $sourceItem->id
                );

                $available = max(
                    0,
                    (int) $sourceItem->quantity - $alreadyDispatched
                );

                if ((int) $item['quantity'] > $available) {
                    throw ValidationException::withMessages([
                        "product_items.{$index}.quantity" =>
                        "Dispatch quantity exceeds available quantity. Available: {$available}.",
                    ]);
                }
            }

            $courierMarketing = CourierMarketing::create([
                'marketing_representative_id' => $representativeId,
                'teacher_id' => $teacher->id,
                'institution_id' => $teacher->institution_id,
                'book_request_group_id' =>
                $sourceRequest->request_group_id,
                'courier_company' => $data['courier_company'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
                'dispatch_date' => $data['dispatch_date'],
                'delivery_date' => $data['delivery_date'] ?? null,
                'status' => 'dispatched',
                'created_by' => auth()->id(),
                'note' => $data['note'] ?? null,
            ]);

            foreach ($data['product_items'] as $item) {
                $sourceItem = $sourceItems->get(
                    (int) $item['book_request_item_id']
                );

                $courierMarketing->items()->create([
                    'book_request_item_id' => $sourceItem->id,
                    'product_id' => $sourceItem->product_id,
                    'quantity' => $item['quantity'],
                    'received_quantity' => 0,
                ]);
            }

            return $courierMarketing;
        });

        return response()->json([
            'ok' => true,
            'id' => $courierMarketing->id,
            'msg' => 'Courier dispatch created successfully.',
        ]);
    }

    /**
     * Update pending courier entry.
     */
    public function update(
        Request $request,
        CourierMarketing $courierMarketing
    ) {
        if ($courierMarketing->status !== 'pending') {
            return response()->json([
                'ok' => false,
                'msg' => 'Only pending courier entries can be updated.',
            ], 422);
        }

        $data = $request->validate([
            'courier_company' => [
                'nullable',
                'string',
                'max:150',
            ],
            'tracking_number' => [
                'nullable',
                'string',
                'max:150',
            ],
            'dispatch_date' => [
                'required',
                'date',
            ],
            'delivery_date' => [
                'nullable',
                'date',
                'after_or_equal:dispatch_date',
            ],
            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $courierMarketing->update($data);

        return response()->json([
            'ok' => true,
            'msg' => 'Courier entry updated successfully.',
        ]);
    }

    /**
     * Update courier tracking status.
     */
    public function updateStatus(
        Request $request,
        CourierMarketing $courierMarketing
    ) {
        $data = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'in_transit',
                    'delivered',
                    'failed',
                    'returned',
                ]),
            ],
        ]);

        if (in_array($courierMarketing->status, [
            'received',
            'partially_received',
        ], true)) {
            return response()->json([
                'ok' => false,
                'msg' => 'Receiving has already been recorded.',
            ], 422);
        }

        $courierMarketing->update([
            'status' => $data['status'],
        ]);

        return response()->json([
            'ok' => true,
            'msg' => 'Courier status updated successfully.',
        ]);
    }

    /**
     * Admin confirms received quantities.
     */
    public function confirmReceiving(
        Request $request,
        CourierMarketing $courierMarketing
    ) {
        if (in_array($courierMarketing->status, [
            'failed',
            'returned',
            'received',
        ], true)) {
            return response()->json([
                'ok' => false,
                'msg' => 'Receiving cannot be confirmed for this courier status.',
            ], 422);
        }

        $data = $request->validate([
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.id' => [
                'required',
                'integer',
                'distinct',
            ],
            'items.*.received_quantity' => [
                'required',
                'integer',
                'min:0',
            ],
            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        DB::transaction(function () use (
            $data,
            $courierMarketing
        ) {
            $courierMarketing = CourierMarketing::query()
                ->lockForUpdate()
                ->findOrFail($courierMarketing->id);

            $courierItems = $courierMarketing->items()
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($courierItems->count() !== count($data['items'])) {
                throw ValidationException::withMessages([
                    'items' => 'All courier items must be submitted for receiving confirmation.',
                ]);
            }

            $hasReceivedQuantity = false;
            $allReceived = true;

            foreach ($data['items'] as $index => $item) {
                $courierItem = $courierItems->get(
                    (int) $item['id']
                );

                if (! $courierItem) {
                    throw ValidationException::withMessages([
                        "items.{$index}.id" => 'Invalid courier item.',
                    ]);
                }

                $receivedQuantity = (int) $item['received_quantity'];

                if ($receivedQuantity > (int) $courierItem->quantity) {
                    throw ValidationException::withMessages([
                        "items.{$index}.received_quantity" =>
                        'Received quantity cannot exceed dispatched quantity.',
                    ]);
                }

                $courierItem->update([
                    'received_quantity' => $receivedQuantity,
                ]);

                if ($receivedQuantity > 0) {
                    $hasReceivedQuantity = true;
                }

                if ($receivedQuantity !== (int) $courierItem->quantity) {
                    $allReceived = false;
                }
            }

            $status = $allReceived
                ? 'received'
                : ($hasReceivedQuantity
                    ? 'partially_received'
                    : 'delivered');

            $courierMarketing->update([
                'status' => $status,
                'received_by' => auth()->id(),
                'received_at' => now(),
                'note' => $data['note'] ?? $courierMarketing->note,
            ]);
        });

        return response()->json([
            'ok' => true,
            'msg' => 'Receiving confirmation saved successfully.',
        ]);
    }

    /**
     * DataTable listing.
     */
    public function listAjax(Request $request)
    {
        $columns = [
            'id',
            'representative_name',
            'teacher_name',
            'product_name',
            'quantity',
            'courier_company',
            'tracking_number',
            'dispatch_date',
            'status',
        ];

        $sortable = [
            'id' => 'cm.id',
            'representative_name' => 'u.name',
            'teacher_name' => 't.teacher_name',
            'product_name' => 'product_name',
            'quantity' => 'total_quantity',
            'courier_company' => 'cm.courier_company',
            'tracking_number' => 'cm.tracking_number',
            'dispatch_date' => 'cm.dispatch_date',
            'status' => 'cm.status',
        ];

        $draw = (int) $request->input('draw');
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length > 0 ? min($length, 100) : 10;

        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower(
            $request->input('order.0.dir', 'desc')
        ) === 'asc' ? 'asc' : 'desc';

        $search = trim($request->input('search.value', ''));

        $itemSummary = DB::table('courier_marketing_items as cmi')
            ->join('products as p', 'p.id', '=', 'cmi.product_id')
            ->select('cmi.courier_marketing_id')
            ->selectRaw("
                GROUP_CONCAT(
                    CONCAT(p.name, ' (', cmi.quantity, ')')
                    ORDER BY cmi.id
                    SEPARATOR ', '
                ) as product_name
            ")
            ->selectRaw('SUM(cmi.quantity) as total_quantity')
            ->groupBy('cmi.courier_marketing_id');

        $query = DB::table('courier_marketings as cm')
            ->leftJoin(
                'marketing_representatives as mr',
                'mr.id',
                '=',
                'cm.marketing_representative_id'
            )
            ->leftJoin('users as u', 'u.id', '=', 'mr.user_id')
            ->leftJoin('teachers as t', 't.id', '=', 'cm.teacher_id')
            ->leftJoinSub($itemSummary, 'item_summary', function ($join) {
                $join->on(
                    'item_summary.courier_marketing_id',
                    '=',
                    'cm.id'
                );
            })
            ->select(
                'cm.*',
                'u.name as representative_name',
                'mr.employee_id as employee_id',
                't.teacher_name',
                'item_summary.product_name',
                'item_summary.total_quantity'
            )
            ->whereNull('cm.deleted_at');

        $total = (clone $query)->count('cm.id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('u.name', 'like', "%{$search}%")
                    ->orWhere('mr.employee_id', 'like', "%{$search}%")
                    ->orWhere('t.teacher_name', 'like', "%{$search}%")
                    ->orWhere('cm.courier_company', 'like', "%{$search}%")
                    ->orWhere('cm.tracking_number', 'like', "%{$search}%")
                    ->orWhere('cm.status', 'like', "%{$search}%")
                    ->orWhere('item_summary.product_name', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count('cm.id');

        $orderColumn = $columns[$orderIndex] ?? 'id';

        $query->orderBy(
            $sortable[$orderColumn] ?? 'cm.id',
            $orderDirection
        );

        $data = $query
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function ($item) {
                $statusClass = match ($item->status) {
                    'received' => 'success',
                    'partially_received' => 'info',
                    'dispatched', 'in_transit' => 'primary',
                    'failed', 'returned', 'rejected' => 'danger',
                    default => 'warning',
                };

                $statusLabel = CourierMarketing::STATUSES[$item->status]
                    ?? ucfirst(str_replace('_', ' ', $item->status));

                $statusHtml = '<span class="bg-' . $statusClass
                    . '-focus text-' . $statusClass
                    . '-600 border border-' . $statusClass
                    . '-main px-12 py-4 radius-4 fw-medium text-sm">'
                    . e($statusLabel)
                    . '</span>';

                $showUrl = route(
                    'courier-marketings.show',
                    $item->id
                );

                $editUrl = route(
                    'courier-marketings.editModal',
                    $item->id
                );

                $deleteUrl = route(
                    'courier-marketings.destroy',
                    $item->id
                );

                $actions = '<div class="d-inline-flex align-items-center justify-content-end gap-1 w-100">'
                    . '<a href="#" data-ajax-modal="' . $showUrl . '" data-size="lg" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-info-focus text-info-main AjaxModal" title="View"><iconify-icon icon="lucide:eye"></iconify-icon></a>'
                    . '<a href="#" data-ajax-modal="' . $editUrl . '" data-size="lg" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-success-focus text-success-main AjaxModal" title="Edit"><iconify-icon icon="lucide:edit"></iconify-icon></a>'
                    . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-courier-marketing-delete" data-url="' . $deleteUrl . '" title="Delete"><iconify-icon icon="mdi:delete"></iconify-icon></a>'
                    . '</div>';

                return [
                    (int) $item->id,
                    e($item->representative_name ?: '-'),
                    e($item->teacher_name ?: '-'),
                    e($item->product_name ?: '-'),
                    (int) ($item->total_quantity ?? 0),
                    e($item->courier_company ?: '-'),
                    e($item->tracking_number ?: '-'),
                    $item->dispatch_date
                        ? \Illuminate\Support\Carbon::parse(
                            $item->dispatch_date
                        )->format('d M, Y')
                        : '-',
                    $statusHtml,
                    $actions,
                ];
            })
            ->values();

        return response()->json([
            'draw' => $draw,
            'iTotalRecords' => $total,
            'iTotalDisplayRecords' => $filtered,
            'aaData' => $data,
        ]);
    }

    /**
     * Delete pending courier entry.
     */
    public function destroy(CourierMarketing $courierMarketing)
    {
        if ($courierMarketing->status !== 'pending') {
            return response()->json([
                'ok' => false,
                'msg' => 'Only pending courier entries can be deleted.',
            ], 422);
        }

        DB::transaction(function () use ($courierMarketing) {
            $courierMarketing->items()->delete();
            $courierMarketing->delete();
        });

        return response()->json([
            'ok' => true,
            'msg' => 'Courier entry deleted successfully.',
        ]);
    }

    /**
     * Get approved request group.
     */
    private function getApprovedRequestGroup(
        BookRequest $sourceRequest,
        bool $lock = false
    ) {
        $query = BookRequest::query()
            ->where('status', 'approved')
            ->whereNull('deleted_at');

        if ($sourceRequest->request_group_id) {
            $query->where(
                'request_group_id',
                $sourceRequest->request_group_id
            );
        } else {
            $query->where('id', $sourceRequest->id);
        }

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    /**
     * Sum previous dispatch quantity for one source request item.
     *
     * Failed and Returned courier entries are excluded,
     * so their quantities become available again.
     */
    private function getDispatchedQuantity(int $bookRequestItemId): int
    {
        return (int) DB::table('courier_marketing_items as cmi')
            ->join(
                'courier_marketings as cm',
                'cm.id',
                '=',
                'cmi.courier_marketing_id'
            )
            ->where('cmi.book_request_item_id', $bookRequestItemId)
            ->whereNull('cm.deleted_at')
            ->whereNotIn('cm.status', ['failed', 'returned'])
            ->sum('cmi.quantity');
    }
}
