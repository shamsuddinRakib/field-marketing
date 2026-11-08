<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\CourierMarketing;
use App\Models\backend\CourierMarketingItem;
use App\Models\backend\MarketingRepresentative;
use App\Models\backend\Product;
use App\Models\backend\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CourierMarketingController extends Controller
{
    public function index()
    {
        return view('backend.modules.courier_marketing.index');
    }

    public function createModal()
    {
        return view('backend.modules.courier_marketing.create_modal');
    }

    /**
     * Store new courier dispatch entry
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'marketing_representative_id' => [
                'required',
                'integer',
                Rule::exists('marketing_representatives', 'id'),
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
            'dispatch_date' => [
                'required',
                'date',
            ],
            'delivery_date' => [
                'nullable',
                'date',
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
            'product_items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id'),
            ],
            'product_items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $teacher = Teacher::query()->findOrFail($data['teacher_id']);

        $courierMarketing = DB::transaction(function () use ($data, $teacher) {
            $cm = CourierMarketing::create([
                'marketing_representative_id' => $data['marketing_representative_id'],
                'teacher_id'                  => $teacher->id,
                'institution_id'              => $teacher->institution_id,
                'courier_company'             => $data['courier_company'] ?? null,
                'tracking_number'             => null, // Tracking omitted per requirement
                'dispatch_date'               => $data['dispatch_date'],
                'delivery_date'               => $data['delivery_date'] ?? null,
                'status'                      => 'dispatched',
                'created_by'                  => auth()->id(),
                'note'                        => $data['note'] ?? null,
            ]);

            foreach ($data['product_items'] as $item) {
                CourierMarketingItem::create([
                    'courier_marketing_id' => $cm->id,
                    'product_id'           => (int) $item['product_id'],
                    'quantity'             => (int) $item['quantity'],
                    'book_request_item_id' => null,
                ]);
            }

            return $cm;
        });

        return response()->json([
            'ok'  => true,
            'id'  => $courierMarketing->id,
            'msg' => 'Courier dispatch saved successfully.',
        ]);
    }

    /**
     * Show courier details modal
     */
    public function show(CourierMarketing $courierMarketing)
    {
        $courierMarketing->load([
            'representative.user',
            'teacher.institution.district',
            'teacher.institution.upazila',
            'institution',
            'creator',
            'receiver',
            'items.product',
        ]);

        return view('backend.modules.courier_marketing.show', compact('courierMarketing'));
    }

    /**
     * Edit modal
     */
    public function editModal(CourierMarketing $courierMarketing)
    {
        $courierMarketing->load([
            'representative.user',
            'teacher.institution',
            'institution',
            'items.product',
        ]);

        return view('backend.modules.courier_marketing.edit_modal', compact('courierMarketing'));
    }

    /**
     * Update courier details
     */
    public function update(Request $request, CourierMarketing $courierMarketing)
    {
        $data = $request->validate([
            'marketing_representative_id' => [
                'nullable',
                'integer',
                Rule::exists('marketing_representatives', 'id'),
            ],
            'courier_company' => [
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
            ],
            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $courierMarketing->update($data);

        return response()->json([
            'ok'  => true,
            'msg' => 'Courier entry updated successfully.',
        ]);
    }

    /**
     * Call Center & Delivery Status Modal
     */
    public function statusModal(CourierMarketing $courierMarketing)
    {
        $courierMarketing->load([
            'teacher.institution',
            'institution',
            'representative.user',
            'items.product',
        ]);

        return view('backend.modules.courier_marketing.status_modal', compact('courierMarketing'));
    }

    /**
     * Update courier status & append Call Center verification note
     */
    public function updateStatus(Request $request, CourierMarketing $courierMarketing)
    {
        $data = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'dispatched',
                    'in_transit',
                    'delivered',
                    'received',
                    'partially_received',
                    'failed',
                    'returned',
                ]),
            ],
            'delivery_date' => [
                'nullable',
                'date',
            ],
            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $updateData = [
            'status' => $data['status'],
        ];

        if (!empty($data['delivery_date'])) {
            $updateData['delivery_date'] = $data['delivery_date'];
        }

        if (in_array($data['status'], ['received', 'partially_received', 'delivered'])) {
            $updateData['received_by'] = auth()->id();
            $updateData['received_at'] = now();
        }

        if (!empty($data['note'])) {
            $author = auth()->user()->name ?? 'Call Center';
            $timeStr = now()->format('d M, Y h:i A');
            $newEntry = "[{$timeStr} by {$author}]: " . trim($data['note']);
            $existingNote = trim((string)$courierMarketing->note);
            $updateData['note'] = $existingNote !== '' ? $existingNote . "\n" . $newEntry : $newEntry;
        }

        $courierMarketing->update($updateData);

        return response()->json([
            'ok'  => true,
            'msg' => 'Status updated successfully.',
        ]);
    }

    /**
     * DataTable listing
     */
    public function listAjax(Request $request)
    {
        $columns = [
            'id',
            'representative_name',
            'teacher_name',
            'institution_name',
            'product_name',
            'quantity',
            'courier_company',
            'dispatch_date',
            'status',
        ];

        $sortable = [
            'id'                  => 'cm.id',
            'representative_name' => 'u.name',
            'teacher_name'        => 't.teacher_name',
            'institution_name'    => 'inst.institution_name',
            'product_name'        => 'product_name',
            'quantity'            => 'total_quantity',
            'courier_company'     => 'cm.courier_company',
            'dispatch_date'       => 'cm.dispatch_date',
            'status'              => 'cm.status',
        ];

        $draw = (int) $request->input('draw');
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        $length = $length > 0 ? min($length, 100) : 10;

        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
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
            ->leftJoin('marketing_representatives as mr', 'mr.id', '=', 'cm.marketing_representative_id')
            ->leftJoin('users as u', 'u.id', '=', 'mr.user_id')
            ->leftJoin('teachers as t', 't.id', '=', 'cm.teacher_id')
            ->leftJoin('institutions as inst', 'inst.id', '=', 'cm.institution_id')
            ->leftJoinSub($itemSummary, 'item_summary', function ($join) {
                $join->on('item_summary.courier_marketing_id', '=', 'cm.id');
            })
            ->select(
                'cm.*',
                'u.name as representative_name',
                'mr.employee_id as employee_id',
                't.teacher_name',
                't.phone as teacher_phone',
                'inst.institution_name',
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
                    ->orWhere('t.phone', 'like', "%{$search}%")
                    ->orWhere('inst.institution_name', 'like', "%{$search}%")
                    ->orWhere('cm.courier_company', 'like', "%{$search}%")
                    ->orWhere('cm.status', 'like', "%{$search}%")
                    ->orWhere('item_summary.product_name', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count('cm.id');
        $orderColumn = $columns[$orderIndex] ?? 'id';

        $query->orderBy($sortable[$orderColumn] ?? 'cm.id', $orderDirection);

        $data = $query->skip($start)->take($length)->get()->map(function ($item) {
            $statusClass = match ($item->status) {
                'received'                  => 'success',
                'partially_received'        => 'info',
                'dispatched', 'in_transit'  => 'primary',
                'failed', 'returned'        => 'danger',
                default                     => 'warning',
            };

            $statusLabel = CourierMarketing::STATUSES[$item->status]
                ?? ucfirst(str_replace('_', ' ', $item->status));

            $statusHtml = '<span class="bg-' . $statusClass . '-focus text-' . $statusClass . '-600 border border-' . $statusClass . '-main px-12 py-4 radius-4 fw-medium text-sm">'
                . e($statusLabel)
                . '</span>';

            $showUrl = route('courier-marketings.show', $item->id);
            $editUrl = route('courier-marketings.editModal', $item->id);
            $statusUrl = route('courier-marketings.statusModal', $item->id);
            $deleteUrl = route('courier-marketings.destroy', $item->id);

            $actions = '<div class="d-inline-flex align-items-center justify-content-end gap-1 w-100">'
                . '<a href="#" data-ajax-modal="' . $statusUrl . '" data-size="lg" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-warning-focus text-warning-main AjaxModal" data-onsuccess="CourierMarketingIndex.onSaved" title="Call Verification / Status"><iconify-icon icon="lucide:phone-call"></iconify-icon></a>'
                . '<a href="#" data-ajax-modal="' . $showUrl . '" data-size="lg" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-info-focus text-info-main AjaxModal" title="View Details"><iconify-icon icon="lucide:eye"></iconify-icon></a>'
                . '<a href="#" data-ajax-modal="' . $editUrl . '" data-size="lg" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-success-focus text-success-main AjaxModal" data-onsuccess="CourierMarketingIndex.onSaved" title="Edit"><iconify-icon icon="lucide:edit"></iconify-icon></a>'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-courier-marketing-delete" data-url="' . $deleteUrl . '" title="Delete"><iconify-icon icon="mdi:delete"></iconify-icon></a>'
                . '</div>';

            $teacherCell = '<strong>' . e($item->teacher_name ?: '-') . '</strong>';
            if ($item->teacher_phone) {
                $teacherCell .= '<br><span class="badge bg-light text-primary border">' . e($item->teacher_phone) . '</span>';
            }

            return [
                (int) $item->id,
                e($item->representative_name ?: '-'),
                $teacherCell,
                e($item->institution_name ?: '-'),
                e($item->product_name ?: '-'),
                (int) ($item->total_quantity ?? 0),
                e($item->courier_company ?: '-'),
                $item->dispatch_date ? Carbon::parse($item->dispatch_date)->format('d M, Y') : '-',
                $statusHtml,
                $actions,
            ];
        })->values();

        return response()->json([
            'draw'                 => $draw,
            'iTotalRecords'        => $total,
            'iTotalDisplayRecords' => $filtered,
            'aaData'               => $data,
        ]);
    }

    /**
     * Delete courier entry
     */
    public function destroy(CourierMarketing $courierMarketing)
    {
        $courierMarketing->items()->delete();
        $courierMarketing->delete();

        return response()->json([
            'ok'  => true,
            'msg' => 'Courier entry deleted successfully.',
        ]);
    }
}
