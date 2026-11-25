<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\BookRequest;
use App\Models\backend\MarketingRepresentative;
use App\Services\BookRequestAvailabilityService;
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

    public function create()
    {
        return view('backend.modules.book_requests.create');
    }

    public function createModal()
    {
        return view('backend.modules.book_requests.modal.create_modal');
    }

    public function edit(BookRequest $bookRequest)
    {
        $bookRequest->load('representative.user');
        $bookRequests = $bookRequest->request_group_id
            ? BookRequest::with([
                'items.product:id,name,category_id,subcategory_id,thumbnail_image',
                'items.product.category:id,name',
                'items.product.subcategory:id,name'
            ])->where('request_group_id', $bookRequest->request_group_id)->get()
                ->flatMap(fn (BookRequest $request) => $request->items)->values()
            : $bookRequest->items()->with([
                'product:id,name,category_id,subcategory_id,thumbnail_image',
                'product.category:id,name',
                'product.subcategory:id,name'
            ])->get();

        return view('backend.modules.book_requests.edit', compact('bookRequest', 'bookRequests'));
    }

    public function editModal(BookRequest $bookRequest)
    {
        $bookRequest->load('representative.user');
        $bookRequests = $bookRequest->request_group_id
            ? BookRequest::with([
                'items.product:id,name,category_id,subcategory_id,thumbnail_image',
                'items.product.category:id,name',
                'items.product.subcategory:id,name'
            ])->where('request_group_id', $bookRequest->request_group_id)->get()
                ->flatMap(fn (BookRequest $request) => $request->items)->values()
            : $bookRequest->items()->with([
                'product:id,name,category_id,subcategory_id,thumbnail_image',
                'product.category:id,name',
                'product.subcategory:id,name'
            ])->get();

        return view('backend.modules.book_requests.modal.edit_modal', compact('bookRequest', 'bookRequests'));
    }

    public function show(BookRequest $bookRequest)
    {
        $bookRequest->load('representative.user');
        $bookRequests = $bookRequest->request_group_id
            ? BookRequest::with('items.product')->where('request_group_id', $bookRequest->request_group_id)->orderBy('id')->get()
                ->flatMap(fn (BookRequest $request) => $request->items)->values()
            : $bookRequest->items()->with('product')->get();

        return view('backend.modules.book_requests.show', compact('bookRequest', 'bookRequests'));
    }

    public function listAjax(Request $request)
    {
        $columns = ['id', 'representative_name', 'request_type', 'product_name', 'quantity', 'note', 'request_date', 'status'];
        $sortable = [
            'id' => 'book_requests.id',
            'representative_name' => 'us.name',
            'request_type' => 'book_requests.request_type',
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
            ->leftJoin('book_request_items as bri', function ($join) {
                $join->on('bri.book_request_id', '=', 'br.id')
                    ->whereNull('bri.deleted_at');
            })
            ->leftJoin('products as p', 'p.id', '=', 'bri.product_id')
            ->selectRaw("
                COALESCE(br.request_group_id, CONCAT('legacy-', br.id)) as group_key,
                MIN(br.id) as id,
                COALESCE(MAX(br.request_type), 'specimen') as request_type,
                GROUP_CONCAT(CONCAT(COALESCE(p.name, CONCAT('#', bri.product_id)), ' (', bri.quantity, ')') ORDER BY bri.id SEPARATOR '|||') as product_name,
                COALESCE(SUM(bri.quantity), 0) as quantity,
                MAX(br.marketing_representative_id) as marketing_representative_id,
                MAX(br.note) as note,
                MAX(br.request_date) as request_date,
                MAX(br.status) as status
            ")
            ->selectRaw('COUNT(bri.id) as product_count')
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
                    ->orWhere('book_requests.request_type', 'like', "%{$search}%")
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
            $editUrl = route('book-request.book-requests.edit', $bookRequest->id);
            $deleteUrl = route('book-request.book-requests.destroy', $bookRequest->id);
            $distributeUrl = route('product-distribution.create', ['book_request_id' => $bookRequest->id]);

            $distributeBtn = $bookRequest->status === 'pending'
                ? '<a href="' . $distributeUrl . '" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-primary-focus text-primary-main" title="Create Distribution / Send Books"><iconify-icon icon="solar:box-delivery-bold"></iconify-icon></a>'
                : '';

            $actions = '<div class="d-inline-flex align-items-center justify-content-end gap-1 w-100">'
                . $distributeBtn
                . '<a href="#" data-ajax-modal="' . $showUrl . '" data-size="lg" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-info-focus text-info-main AjaxModal" title="View"><iconify-icon icon="lucide:eye"></iconify-icon></a>'
                . '<a href="' . $editUrl . '" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-success-focus text-success-main" title="Edit"><iconify-icon icon="lucide:edit"></iconify-icon></a>'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-book-request-delete" data-url="' . $deleteUrl . '" title="Delete"><iconify-icon icon="mdi:delete"></iconify-icon></a>'
                . '</div>';

            $productNames = array_filter(explode('|||', (string) $bookRequest->product_name));
            $visibleProductNames = array_slice($productNames, 0, 5);
            $moreProductCount = max(0, (int) $bookRequest->product_count - count($visibleProductNames));
            $productNameDisplay = implode(', ', $visibleProductNames);
            if ($moreProductCount > 0) {
                $productNameDisplay .= ' +' . $moreProductCount . ' more';
            }

            $typeBadge = ($bookRequest->request_type ?? 'specimen') === 'sale'
                ? '<span class="badge bg-success-focus text-success-main border border-success px-12 py-4 radius-4">Sale Copy</span>'
                : '<span class="badge bg-info-focus text-info-main border border-info px-12 py-4 radius-4">Specimen Copy</span>';

            return [
                (int) $bookRequest->id,
                e($bookRequest->representative_name ?: '-'),
                $typeBadge,
                e($productNameDisplay ?: '-'),
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
        if (!$request->has('product_items') && $request->has('items')) {
            $request->merge(['product_items' => $request->input('items')]);
        }

        [$data, $products] = $this->validated($request);
        $groupId = (string) Str::uuid();

        $bookRequest = DB::transaction(function () use ($data, $products, $groupId) {
            $bookRequest = BookRequest::create(array_merge($data, ['request_group_id' => $groupId]));
            $bookRequest->items()->createMany($products);

            return $bookRequest;
        });

        return response()->json([
            'ok' => true,
            'id' => $bookRequest?->id,
            'msg' => 'Book request created.',
            'redirect' => route('book-request.book-requests.index'),
        ]);
    }

    public function update(Request $request, BookRequest $bookRequest)
    {
        if (!$request->has('product_items') && $request->has('items')) {
            $request->merge(['product_items' => $request->input('items')]);
        }

        [$data, $products] = $this->validated(
            $request,
            $bookRequest->request_group_id,
            $bookRequest->request_group_id ? null : (int) $bookRequest->id
        );
        $groupId = $bookRequest->request_group_id ?: (string) Str::uuid();

        DB::transaction(function () use ($bookRequest, $data, $products, $groupId) {
            $existingRequests = $bookRequest->request_group_id
                ? BookRequest::with('items')->where('request_group_id', $groupId)->get()
                : collect([$bookRequest->load('items')]);
            $anchorRequest = $existingRequests->first() ?? $bookRequest;
            $existingItems = $existingRequests->flatMap(fn (BookRequest $request) => $request->items)->keyBy('product_id');
            $requestedProductIds = collect($products)->pluck('product_id')->map(fn ($id) => (int) $id);

            foreach ($existingRequests as $existingRequest) {
                $existingRequest->update(array_merge($data, ['request_group_id' => $groupId]));
            }

            foreach ($products as $product) {
                $existingItem = $existingItems->get((int) $product['product_id']);
                if ($existingItem) {
                    $existingItem->update(['quantity' => $product['quantity']]);
                } else {
                    $anchorRequest->items()->create($product);
                }
            }

            $existingItems->each(function ($item, $productId) use ($requestedProductIds): void {
                if (! $requestedProductIds->contains((int) $productId)) {
                    $item->delete();
                }
            });
        });

        return response()->json([
            'ok' => true,
            'msg' => 'Book request updated.',
            'redirect' => route('book-request.book-requests.index'),
        ]);
    }

    public function destroy(BookRequest $bookRequest)
    {
        if ($bookRequest->request_group_id) {
            BookRequest::where('request_group_id', $bookRequest->request_group_id)
                ->get()
                ->each(function (BookRequest $request) {
                    $request->items()->delete();
                    $request->delete();
                });
        } else {
            $bookRequest->items()->delete();
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

    private function validated(Request $request, ?string $excludeRequestGroupId = null, ?int $excludeRequestId = null): array
    {
        $data = $request->validate([
            'marketing_representative_id' => ['required', 'integer', Rule::exists('marketing_representatives', 'id')],
            'request_type' => ['required', Rule::in(['specimen', 'sale'])],
            'product_items' => ['required', 'array', 'min:1'],
            'product_items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')],
            'product_items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:1000'],
            'request_date' => ['required', 'date'],
            'status' => ['required', Rule::in(array_keys(BookRequest::STATUSES))],
        ]);

        $products = $data['product_items'];
        unset($data['product_items']);

        return [$data, $products];
    }
}
