<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\CourierMarketing;
use App\Models\backend\CourierMarketingItem;
use App\Models\backend\Institution;
use App\Models\backend\MarketingRepresentative;
use App\Models\backend\Product;
use App\Models\backend\Teacher;
use App\Models\backend\Upazila;
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
        if (!$request->has('product_items') && $request->has('items')) {
            $request->merge(['product_items' => $request->input('items')]);
        }

        $data = $request->validate([
            'marketing_representative_id' => ['required', 'integer', Rule::exists('marketing_representatives', 'id') ],
            'teacher_id' => ['required', 'integer', Rule::exists('teachers', 'id')],
            'institution_id' => ['nullable', 'integer',  Rule::exists('institutions', 'id') ],
            'upazila_id' => ['nullable','integer',Rule::exists('upazilas', 'id')],
            'district_id' => ['nullable','integer'],
            'division_id' => ['nullable','integer'],
            'courier_company' => ['nullable', 'string', 'max:150' ],
            'dispatch_date' => ['required', 'date' ],
            'delivery_date' => [ 'nullable', 'date'],
            'note' => ['nullable','string','max:1000'],
            'product_items' => ['required','array','min:1'],
            'product_items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'product_items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $teacher = Teacher::query()->findOrFail($data['teacher_id']);
        $institutionId = $data['institution_id'] ?? $teacher->institution_id;

        $mergedItems = [];
        foreach ($data['product_items'] as $item) {
            $pid = (int) $item['product_id'];
            $mergedItems[$pid] = ($mergedItems[$pid] ?? 0) + (int) $item['quantity'];
        }

        $totalQty = max(1, (int) array_sum($mergedItems));
        $dispatchDate = Carbon::parse($data['dispatch_date'])->toDateString();
        $deliveryDate = !empty($data['delivery_date'])
            ? Carbon::parse($data['delivery_date'])->toDateString()
            : null;

        $courierMarketing = DB::transaction(function () use ($data, $teacher, $institutionId, $mergedItems, $totalQty, $dispatchDate, $deliveryDate) {
            $payload = [
                'marketing_representative_id' => $data['marketing_representative_id'],
                'teacher_id'                  => $teacher->id,
                'institution_id'              => $institutionId,
                'courier_company'             => $data['courier_company'] ?? null,
                'dispatch_date'               => $dispatchDate,
                'delivery_date'               => $deliveryDate,
                'status'                      => 'dispatched',
                'note'                        => $data['note'] ?? null,
            ];

            if (Schema::hasColumn('courier_marketings', 'tracking_number')) {
                $payload['tracking_number'] = null; // Tracking omitted per requirement
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
            if (Schema::hasColumn('courier_marketings', 'upazila_id') && !empty($data['upazila_id'])) {
                $payload['upazila_id'] = $data['upazila_id'];
            }
            if (Schema::hasColumn('courier_marketings', 'district_id') && !empty($data['district_id'])) {
                $payload['district_id'] = $data['district_id'];
            }
            if (Schema::hasColumn('courier_marketings', 'division_id') && !empty($data['division_id'])) {
                $payload['division_id'] = $data['division_id'];
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
            'marketing_representative_id' => ['nullable', 'integer', Rule::exists('marketing_representatives', 'id')],
            'courier_company' => ['nullable','string','max:150'],
            'dispatch_date' => ['required','date'],
            'delivery_date' => ['nullable','date'],
            'note' => ['nullable','string', 'max:1000'],
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
            'status' => ['required', Rule::in(['dispatched', 'in_transit', 'delivered','received','partially_received', 'failed', 'returned'])],
            'delivery_date' => ['nullable','date' ],
            'note' => [ 'nullable', 'string', 'max:1000'],
        ]);

        $updateData = [
            'status' => $data['status'],
        ];

        if (!empty($data['delivery_date'])) {
            $updateData['delivery_date'] = $data['delivery_date'];
        }

        if (in_array($data['status'], ['received', 'partially_received', 'delivered'])) {
            if ($this->canStoreUserIdInReceivedBy()) {
                $updateData['received_by'] = auth()->id();
            }
            if (Schema::hasColumn('courier_marketings', 'received_at')) {
                $updateData['received_at'] = now();
            }
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
            ->whereNull('cmi.deleted_at')
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

    /**
     * Select2: Upazilas (filtered by Marketing Representative's assigned territories)
     */
    public function upazilasSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));
        $repId = $request->input('marketing_representative_id');

        $query = Upazila::query()->with('upazila_district.district_division');

        if ($repId) {
            $rep = MarketingRepresentative::with('territories')->find($repId);
            if ($rep) {
                $upazilaIds = $rep->territories->pluck('upazila_id')->filter()->unique()->toArray();
                if (empty($upazilaIds) && $rep->upazila_id) {
                    $upazilaIds = [$rep->upazila_id];
                }
                if (!empty($upazilaIds)) {
                    $query->whereIn('id', $upazilaIds);
                }
            }
        }

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('upazila_name', 'like', "%{$term}%")
                  ->orWhere('upazila_bn_name', 'like', "%{$term}%");
            });
        }

        $upazilas = $query->orderBy('upazila_name')->limit(50)->get();

        return response()->json([
            'results' => $upazilas->map(function ($u) {
                $dist = $u->upazila_district;
                $div = $dist?->district_division;
                return [
                    'id'            => $u->id,
                    'text'          => $u->upazila_name . ($dist ? " ({$dist->district_name})" : ''),
                    'upazila_id'    => $u->id,
                    'upazila_name'  => $u->upazila_name,
                    'district_id'   => $dist?->district_id,
                    'district_name' => $dist?->district_name ?? '',
                    'division_id'   => $div?->id,
                    'division_name' => $div?->name ?? ($div?->bn_name ?? ''),
                ];
            })->values(),
        ]);
    }

    /**
     * Select2: Institutions (filtered by Upazila)
     */
    public function institutionsSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));
        $upazilaId = $request->input('upazila_id');

        if (!$upazilaId) {
            return response()->json(['results' => []]);
        }

        $institutions = Institution::query()
            ->with(['upazila', 'district', 'division'])
            ->where(function ($q) use ($upazilaId) {
                $q->where('upazila_id', $upazilaId)
                  ->orWhereHas('teachers', fn($t) => $t->where('upazila_id', $upazilaId));
            })
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($b) use ($term) {
                    $b->where('institution_name', 'like', "%{$term}%")
                      ->orWhere('code', 'like', "%{$term}%");
                });
            })
            ->orderBy('institution_name')
            ->limit(50)
            ->get();

        return response()->json([
            'results' => $institutions->map(function ($inst) {
                $name = $inst->institution_name ?: $inst->name;
                return [
                    'id'               => $inst->id,
                    'text'             => $name . ($inst->code ? " ({$inst->code})" : ''),
                    'institution_name' => $name,
                    'address'          => $inst->address ?? '',
                    'upazila_id'       => $inst->upazila_id,
                    'upazila_name'     => $inst->upazila?->upazila_name ?? '',
                    'district_id'      => $inst->district_id,
                    'district_name'    => $inst->district?->district_name ?? '',
                    'division_id'      => $inst->division_id,
                    'division_name'    => $inst->division?->name ?? '',
                ];
            })->values(),
        ]);
    }

    /**
     * Select2: Teachers searchable by Phone or Name (filtered by Upazila and/or Institution)
     */
    public function teachersByPhoneSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));
        $upazilaId = $request->input('upazila_id');
        $institutionId = $request->input('institution_id');

        $query = Teacher::query()
            ->with(['institution.upazila', 'institution.district', 'institution.division', 'upazila', 'district', 'division'])
            ->when($institutionId, function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId);
            })
            ->when(!$institutionId && $upazilaId, function ($q) use ($upazilaId) {
                $q->where(function ($sub) use ($upazilaId) {
                    $sub->where('upazila_id', $upazilaId)
                        ->orWhereHas('institution', fn($inst) => $inst->where('upazila_id', $upazilaId));
                });
            })
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($b) use ($term) {
                    $b->where('phone', 'like', "%{$term}%")
                      ->orWhere('teacher_name', 'like', "%{$term}%");
                });
            })
            ->orderByRaw("CASE WHEN phone IS NOT NULL AND phone != '' THEN 0 ELSE 1 END")
            ->orderBy('phone')
            ->limit(30);

        $teachers = $query->get();

        return response()->json([
            'results' => $teachers->map(function ($teacher) {
                $inst = $teacher->institution;
                $instName = $inst?->institution_name ?? '';
                $upazila = $teacher->upazila ?? ($inst?->upazila);
                $district = $teacher->district ?? ($inst?->district);
                $division = $teacher->division ?? ($inst?->division);

                $phone = $teacher->phone ?: 'No Phone';
                $text = $phone;
                if ($teacher->teacher_name) {
                    $text .= ' — ' . $teacher->teacher_name;
                }
                if ($instName) {
                    $text .= ' (' . $instName . ')';
                }

                $locParts = array_filter([$upazila?->upazila_name, $district?->district_name]);
                $locStr = !empty($locParts) ? implode(', ', $locParts) : '';
                $addr = $teacher->address ?: ($inst?->address ?? '');
                $fullLoc = $locStr ? ($locStr . ($addr ? ' — ' . $addr : '')) : $addr;

                return [
                    'id'               => $teacher->id,
                    'text'             => $text,
                    'teacher_name'     => $teacher->teacher_name ?? '',
                    'phone'            => $teacher->phone ?? '',
                    'institution_id'   => $teacher->institution_id,
                    'institution_name' => $instName,
                    'upazila_id'       => $upazila?->id,
                    'upazila_name'     => $upazila?->upazila_name ?? '',
                    'district_id'      => $district?->district_id,
                    'district_name'    => $district?->district_name ?? '',
                    'division_id'      => $division?->id,
                    'division_name'    => $division?->name ?? ($division?->bn_name ?? ''),
                    'address'          => $fullLoc,
                ];
            })->values(),
        ]);
    }

    /**
     * Quick confirm receiving route handler
     */
    public function confirmReceiving(Request $request, CourierMarketing $courierMarketing)
    {
        $updateData = [
            'status'        => 'received',
            'delivery_date' => $request->input('delivery_date', now()->toDateString()),
        ];

        if ($this->canStoreUserIdInReceivedBy()) {
            $updateData['received_by'] = auth()->id();
        }
        if (Schema::hasColumn('courier_marketings', 'received_at')) {
            $updateData['received_at'] = now();
        }

        $courierMarketing->update($updateData);

        return response()->json([
            'ok'  => true,
            'msg' => 'Parcel confirmed as received.',
        ]);
    }

    /**
     * Guard writes to `received_by` when DB column is misconfigured as timestamp/datetime
     */
    private function canStoreUserIdInReceivedBy(): bool
    {
        if (!Schema::hasColumn('courier_marketings', 'received_by')) {
            return false;
        }

        $type = strtolower((string) Schema::getColumnType('courier_marketings', 'received_by'));

        return in_array($type, ['int', 'integer', 'bigint', 'smallint', 'tinyint', 'mediumint'], true);
    }
}
