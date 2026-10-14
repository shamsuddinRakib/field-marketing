<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\BookRequest;
use App\Models\backend\MarketingRepresentative;
use App\Models\backend\ProductDistribution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookRequestController extends Controller
{
    public function index()
    {
        return view('backend.modules.book_requests.index');
    }

    public function createModal()
    {
        return view('backend.modules.book_requests.create_modal');
    }

    public function editModal(BookRequest $bookRequest)
    {
        $bookRequest->load(['representative.user', 'product']);
        $bookRequests = $bookRequest->request_group_id
            ? BookRequest::with('product')->where('request_group_id', $bookRequest->request_group_id)->get()
            : collect([$bookRequest]);

        // Dist quantities for this MR, to enforce quantity <= Dist in the form.
        $distQuantities = [];
        if ($bookRequest->representative) {
            $distQuantities = ProductDistribution::where('user_id', (string) $bookRequest->representative->user_id)
                ->selectRaw('product_id, SUM(quantity) as total')
                ->groupBy('product_id')
                ->pluck('total', 'product_id')
                ->map(fn($v) => (int) $v)
                ->toArray();
        }

        return view('backend.modules.book_requests.edit_modal', compact('bookRequest', 'bookRequests', 'distQuantities'));
    }

    public function show(BookRequest $bookRequest)
    {
        $bookRequest->load(['representative.user', 'product']);
        $bookRequests = $bookRequest->request_group_id
            ? BookRequest::with('product')->where('request_group_id', $bookRequest->request_group_id)->orderBy('id')->get()
            : collect([$bookRequest]);

        return view('backend.modules.book_requests.show', compact('bookRequest', 'bookRequests'));
    }

    public function listAjax(Request $request)
    {
        $columns = ['id', 'representative_name', 'product_name', 'quantity', 'note', 'request_date', 'status'];
        $sortable = [
            'id' => 'book_requests.id',
            'representative_name' => 'us.name',
            'product_name' => 'book_requests.product_name',
            'quantity' => 'book_requests.quantity',
            'note' => 'book_requests.note',
            'request_date' => 'book_requests.request_date',
            'status' => 'book_requests.status',
        ];

        $draw = (int) $request->input('draw');
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim($request->input('search.value', ''));

        $groupedRequests = DB::table('book_requests as br')
            ->leftJoin('products as p', 'p.id', '=', 'br.product_id')
            ->selectRaw("\n                COALESCE(br.request_group_id, CONCAT('legacy-', br.id)) as group_key,\n                MIN(br.id) as id,\n                GROUP_CONCAT(CONCAT(COALESCE(p.name, CONCAT('#', br.product_id)), ' (', br.quantity, ')') ORDER BY br.id SEPARATOR ', ') as product_name,\n                SUM(br.quantity) as quantity,\n                MAX(br.marketing_representative_id) as marketing_representative_id,\n                MAX(br.note) as note,\n                MAX(br.request_date) as request_date,\n                MAX(br.status) as status\n            ")
            ->groupByRaw("COALESCE(br.request_group_id, CONCAT('legacy-', br.id))");

        $query = DB::query()
            ->fromSub($groupedRequests, 'book_requests')
            ->leftJoin('marketing_representatives as mr', 'mr.id', '=', 'book_requests.marketing_representative_id')
            ->leftJoin('users as us', 'us.id', '=', 'mr.user_id')
            ->select(
                'book_requests.*',
                'us.name as representative_name',
                'mr.employee_id as representative_employee_id'
            );

        $total = (clone $query)->count('book_requests.id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('us.name', 'like', "%{$search}%")
                    ->orWhere('mr.employee_id', 'like', "%{$search}%")
                    ->orWhere('book_requests.product_name', 'like', "%{$search}%")
                    ->orWhere('book_requests.note', 'like', "%{$search}%")
                    ->orWhere('book_requests.status', 'like', "%{$search}%")
                    ->orWhere('book_requests.request_date', 'like', "%{$search}%")
                    ->orWhere('book_requests.quantity', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count('book_requests.id');

        $orderColumn = $columns[$orderIndex] ?? 'id';
        $query->orderBy($sortable[$orderColumn] ?? 'book_requests.id', $orderDirection);

        $data = $query->skip($start)->take($length)->get()->map(function ($bookRequest) {
            $status = match ($bookRequest->status) {
                'approved' => '<span class="bg-success-focus text-success-600 border border-success-main px-12 py-4 radius-4 fw-medium text-sm">Approved</span>',
                'rejected' => '<span class="bg-danger-focus text-danger-600 border border-danger-main px-12 py-4 radius-4 fw-medium text-sm">Rejected</span>',
                default => '<span class="bg-warning-focus text-warning-600 border border-warning-main px-12 py-4 radius-4 fw-medium text-sm">Pending</span>',
            };

            $showUrl = route('book-request.book-requests.show', $bookRequest->id);
            $editUrl = route('book-request.book-requests.editModal', $bookRequest->id);
            $deleteUrl = route('book-request.book-requests.destroy', $bookRequest->id);

            $actions = '<div class="d-inline-flex align-items-center justify-content-end gap-1 w-100">'
                . '<a href="#" data-ajax-modal="' . $showUrl . '" data-size="lg" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-info-focus text-info-main AjaxModal" title="View"><iconify-icon icon="lucide:eye"></iconify-icon></a>'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-success-focus text-success-main AjaxModal" data-ajax-modal="' . $editUrl . '" data-size="lg" data-onload="BookRequestsIndex.onLoad" data-onsuccess="BookRequestsIndex.onSaved" title="Edit"><iconify-icon icon="lucide:edit"></iconify-icon></a>'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-book-request-delete" data-url="' . $deleteUrl . '" title="Delete"><iconify-icon icon="mdi:delete"></iconify-icon></a>'
                . '</div>';

            return [
                (int) $bookRequest->id,
                e($bookRequest->representative_name ?: '-'),
                e($bookRequest->product_name ?? '-'),
                (int) $bookRequest->quantity,
                e($bookRequest->note ?: '-'),
                $bookRequest->request_date
                    ? \Illuminate\Support\Carbon::parse($bookRequest->request_date)->format('d M, Y')
                    : '-',
                $status,
                $actions,
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'iTotalRecords' => $total,
            'iTotalDisplayRecords' => $filtered,
            'aaData' => $data,
        ]);
    }

    public function store(Request $request)
    {
        [$data, $products] = $this->validated($request);
        $groupId = (string) Str::uuid();

        $bookRequest = DB::transaction(function () use ($data, $products, $groupId) {
            $firstRequest = null;
            foreach ($products as $product) {
                $created = BookRequest::create(array_merge($data, $product, ['request_group_id' => $groupId]));
                $firstRequest ??= $created;
            }

            return $firstRequest;
        });

        return response()->json([
            'ok' => true,
            'id' => $bookRequest?->id,
            'msg' => 'Book request created.',
        ]);
    }

    public function update(Request $request, BookRequest $bookRequest)
    {
        [$data, $products] = $this->validated($request);
        $groupId = $bookRequest->request_group_id ?: (string) Str::uuid();

        DB::transaction(function () use ($bookRequest, $data, $products, $groupId) {
            $existingRequests = $bookRequest->request_group_id
                ? BookRequest::where('request_group_id', $groupId)->get()->keyBy('product_id')
                : collect([$bookRequest])->keyBy('product_id');
            $retainedIds = [];

            foreach ($products as $product) {
                $existing = $existingRequests->get($product['product_id']);
                if ($existing) {
                    $existing->update(array_merge($data, $product, ['request_group_id' => $groupId]));
                    $retainedIds[] = $existing->id;
                } else {
                    $created = BookRequest::create(array_merge($data, $product, ['request_group_id' => $groupId]));
                    $retainedIds[] = $created->id;
                }
            }

            BookRequest::where('request_group_id', $groupId)
                ->whereNotIn('id', $retainedIds)
                ->delete();
        });

        return response()->json([
            'ok' => true,
            'msg' => 'Book request updated.',
        ]);
    }

    public function destroy(BookRequest $bookRequest)
    {
        if ($bookRequest->request_group_id) {
            BookRequest::where('request_group_id', $bookRequest->request_group_id)->delete();
        } else {
            $bookRequest->delete();
        }

        return response()->json(['ok' => true, 'msg' => 'Book request deleted.']);
    }

    public function representativesSelect2(Request $request)
    {
        $term = trim($request->input('q', ''));

        $representatives = MarketingRepresentative::with('user')
            ->where('status', 1)
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($builder) use ($term) {
                    $builder->where('employee_id', 'like', "%{$term}%")
                        ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$term}%"));
                });
            })
            ->orderBy('id', 'desc')
            ->limit(30)
            ->get();

        return response()->json([
            'results' => $representatives->map(fn($representative) => [
                'id' => $representative->id,
                'text' => trim($representative->user?->name ?: ($representative->employee_id ?: '#' . $representative->id)),
            ])->values(),
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'marketing_representative_id' => ['required', 'integer', Rule::exists('marketing_representatives', 'id')],
            'product_items' => ['required', 'array', 'min:1'],
            'product_items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')],
            'product_items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:1000'],
            'request_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(BookRequest::STATUSES))],
        ]);

        $products = $data['product_items'];
        unset($data['product_items']);

        // Dist check: each requested quantity must be equal or less than
        // the distributed quantity for this MR + product.
        $representative = MarketingRepresentative::find($data['marketing_representative_id']);
        if ($representative) {
            foreach ($products as $index => $item) {
                $distributed = (int) ProductDistribution::where('user_id', (string) $representative->user_id)
                    ->where('product_id', $item['product_id'])
                    ->sum('quantity');

                if ((int) $item['quantity'] > $distributed) {
                    throw ValidationException::withMessages([
                        "product_items.{$index}.quantity" => "Quantity cannot exceed distributed quantity (Dist: {$distributed}). Must be equal or less.",
                    ]);
                }
            }
        }

        return [$data, $products];
    }
}
