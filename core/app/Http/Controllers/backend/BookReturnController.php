<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\BookRequest;
use App\Models\backend\BookReturn;
use App\Models\backend\MarketingRepresentative;
use App\Models\backend\ProductDistribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookReturnController extends Controller
{
    public function index()
    {
        return view('backend.modules.book_returns.index');
    }

    public function createModal()
    {
        return view('backend.modules.book_returns.create_modal');
    }

    public function editModal(BookReturn $bookReturn)
    {
        $bookReturn->load(['representative.user', 'institution', 'product', 'productDistribution.product']);
        $bookReturns = $bookReturn->return_group_id
            ? BookReturn::with(['bookRequest.product', 'productDistribution.product'])->where('return_group_id', $bookReturn->return_group_id)->orderBy('id')->get()
            : collect([$bookReturn]);

        return view('backend.modules.book_returns.edit_modal', compact('bookReturn', 'bookReturns'));
    }

    public function show(BookReturn $bookReturn)
    {
        $bookReturn->load(['representative.user', 'institution', 'product']);
        $bookReturns = $bookReturn->return_group_id
            ? BookReturn::with('product')->where('return_group_id', $bookReturn->return_group_id)->orderBy('id')->get()
            : collect([$bookReturn]);

        return view('backend.modules.book_returns.show', compact('bookReturn', 'bookReturns'));
    }

    public function listAjax(Request $request)
    {
        $columns = [
            'id',
            'representative_name',
            'institution_name',
            'product_name',
            'issued_quantity',
            'returned_quantity',
            'note',
            'status',
            'received_by',
            'received_date',
        ];
        $sortable = [
            'id' => 'book_returns.id',
            'representative_name' => 'us.name',
            'institution_name' => 'i.institution_name',
            'product_name' => 'book_returns.product_name',
            'issued_quantity' => 'book_returns.issued_quantity',
            'returned_quantity' => 'book_returns.returned_quantity',
            'note' => 'book_returns.note',
            'status' => 'book_returns.status',
            'received_by' => 'book_returns.received_by',
            'received_date' => 'book_returns.received_date',
        ];

        $draw = (int) $request->input('draw');
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower($request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim($request->input('search.value', ''));

        $groupedReturns = DB::table('book_returns as br')
            ->leftJoin('products as p', 'p.id', '=', 'br.product_id')
            ->selectRaw("\n                COALESCE(br.return_group_id, CONCAT('legacy-', br.id)) as group_key,\n                MIN(br.id) as id,\n                MAX(br.marketing_representative_id) as marketing_representative_id,\n                MAX(br.institution_id) as institution_id,\n                GROUP_CONCAT(CONCAT(COALESCE(p.name, CONCAT('#', br.product_id)), ' (', br.returned_quantity, '/', br.issued_quantity, ')') ORDER BY br.id SEPARATOR ', ') as product_name,\n                SUM(br.issued_quantity) as issued_quantity,\n                SUM(br.returned_quantity) as returned_quantity,\n                MAX(br.note) as note,\n                MAX(br.status) as status,\n                MAX(br.received_by) as received_by,\n                MAX(br.received_date) as received_date\n            ")
            ->groupByRaw("COALESCE(br.return_group_id, CONCAT('legacy-', br.id))");

        $query = DB::query()
            ->fromSub($groupedReturns, 'book_returns')
            ->leftJoin('marketing_representatives as mr', 'mr.id', '=', 'book_returns.marketing_representative_id')
            ->leftJoin('users as us', 'us.id', '=', 'mr.user_id')
            ->leftJoin('institutions as i', 'i.id', '=', 'book_returns.institution_id')
            ->select(
                'book_returns.*',
                'us.name as representative_name',
                'mr.employee_id as representative_employee_id',
                'i.institution_name as institution_name'
            );

        $total = (clone $query)->count();

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('us.name', 'like', "%{$search}%")
                    ->orWhere('mr.employee_id', 'like', "%{$search}%")
                    ->orWhere('i.institution_name', 'like', "%{$search}%")
                    ->orWhere('book_returns.product_name', 'like', "%{$search}%")
                    ->orWhere('book_returns.note', 'like', "%{$search}%")
                    ->orWhere('book_returns.status', 'like', "%{$search}%")
                    ->orWhere('book_returns.received_by', 'like', "%{$search}%")
                    ->orWhere('book_returns.received_date', 'like', "%{$search}%")
                    ->orWhere('book_returns.issued_quantity', 'like', "%{$search}%")
                    ->orWhere('book_returns.returned_quantity', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count();

        $orderColumn = $columns[$orderIndex] ?? 'id';
        $query->orderBy($sortable[$orderColumn] ?? 'book_returns.id', $orderDirection);

        $data = $query->skip($start)->take($length)->get()->map(function ($bookReturn) {
            $status = match ($bookReturn->status) {
                'received' => '<span class="bg-success-focus text-success-600 border border-success-main px-12 py-4 radius-4 fw-medium text-sm">Received</span>',
                'partial' => '<span class="bg-info-focus text-info-600 border border-info-main px-12 py-4 radius-4 fw-medium text-sm">Partially Returned</span>',
                'rejected' => '<span class="bg-danger-focus text-danger-600 border border-danger-main px-12 py-4 radius-4 fw-medium text-sm">Rejected</span>',
                default => '<span class="bg-warning-focus text-warning-600 border border-warning-main px-12 py-4 radius-4 fw-medium text-sm">Pending</span>',
            };

            $showUrl = route('book-return.book-returns.show', $bookReturn->id);
            $editUrl = route('book-return.book-returns.editModal', $bookReturn->id);
            $deleteUrl = route('book-return.book-returns.destroy', $bookReturn->id);

            $actions = '<div class="d-inline-flex align-items-center justify-content-end gap-1 w-100">'
                . '<a href="#" data-ajax-modal="' . $showUrl . '" data-size="lg" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-info-focus text-info-main AjaxModal" title="View"><iconify-icon icon="lucide:eye"></iconify-icon></a>'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-success-focus text-success-main AjaxModal" data-ajax-modal="' . $editUrl . '" data-size="lg" data-onload="BookReturnsIndex.onLoad" data-onsuccess="BookReturnsIndex.onSaved" title="Edit"><iconify-icon icon="lucide:edit"></iconify-icon></a>'
                . '<a href="#" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-danger-focus text-danger-main btn-book-return-delete" data-url="' . $deleteUrl . '" title="Delete"><iconify-icon icon="mdi:delete"></iconify-icon></a>'
                . '</div>';

            return [
                (int) $bookReturn->id,
                e($bookReturn->representative_name ?: '-'),
                e($bookReturn->institution_name ?? '-'),
                e($bookReturn->product_name ?? '-'),
                (int) $bookReturn->issued_quantity,
                (int) $bookReturn->returned_quantity,
                e($bookReturn->note ?: '-'),
                $status,
                e($bookReturn->received_by ?: '-'),
                $bookReturn->received_date
                    ? \Illuminate\Support\Carbon::parse($bookReturn->received_date)->format('d M, Y')
                    : '-',
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
        [$data, $items] = $this->validatedItems($request);
        $groupId = (string) Str::uuid();

        $bookReturn = DB::transaction(function () use ($data, $items, $groupId) {
            $firstReturn = null;
            foreach ($items as $item) {
                $created = BookReturn::create(array_merge($data, $item, ['return_group_id' => $groupId]));
                $firstReturn ??= $created;
            }

            return $firstReturn;
        });

        return response()->json([
            'ok' => true,
            'id' => $bookReturn->id,
            'msg' => 'Book return created.',
        ]);
    }

    public function update(Request $request, BookReturn $bookReturn)
    {
        if ($request->has('items')) {
            [$data, $items] = $this->validatedItems($request, $bookReturn);
            $groupId = $bookReturn->return_group_id ?: (string) Str::uuid();

            DB::transaction(function () use ($bookReturn, $data, $items, $groupId) {
                $existingCollection = $bookReturn->return_group_id
                    ? BookReturn::where('return_group_id', $groupId)->get()
                    : collect([$bookReturn]);
                // Support both new (distribution) and old (book_request) keys
                $byDistribution = $existingCollection->keyBy('product_distribution_id');
                $byRequest = $existingCollection->keyBy('book_request_id');
                $retainedIds = [];

                foreach ($items as $item) {
                    $existing = null;
                    if (! empty($item['product_distribution_id'])) {
                        $existing = $byDistribution->get($item['product_distribution_id']);
                    }
                    if (! $existing && ! empty($item['book_request_id'])) {
                        $existing = $byRequest->get($item['book_request_id']);
                    }
                    if ($existing) {
                        $existing->update(array_merge($data, $item, ['return_group_id' => $groupId]));
                        $retainedIds[] = $existing->id;
                    } else {
                        $created = BookReturn::create(array_merge($data, $item, ['return_group_id' => $groupId]));
                        $retainedIds[] = $created->id;
                    }
                }

                BookReturn::where('return_group_id', $groupId)->whereNotIn('id', $retainedIds)->delete();
            });

            return response()->json(['ok' => true, 'msg' => 'Book return updated.']);
        }

        $bookReturn->update($this->validated($request));

        return response()->json([
            'ok' => true,
            'msg' => 'Book return updated.',
        ]);
    }

    public function destroy(BookReturn $bookReturn)
    {
        if ($bookReturn->return_group_id) {
            BookReturn::where('return_group_id', $bookReturn->return_group_id)->delete();
        } else {
            $bookReturn->delete();
        }

        return response()->json(['ok' => true, 'msg' => 'Book return deleted.']);
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

    public function requestsSelect2(Request $request)
    {
        $representativeId = $request->input('marketing_representative_id');
        $requestDate = $request->input('request_date');
        if (! $representativeId || ! $requestDate) {
            return response()->json(['results' => []]);
        }

        $term = trim($request->input('q', ''));
        $returnGroupId = $request->input('return_group_id');
        $excludeCurrentGroup = $returnGroupId
            ? ' AND (book_returns.return_group_id IS NULL OR book_returns.return_group_id != ?)'
            : '';
        $remainingBindings = $returnGroupId ? ['rejected', $returnGroupId] : ['rejected'];
        $items = BookRequest::query()
            ->with('product:id,name,sku')
            ->withSum(['returns as returned_total' => function ($query) use ($returnGroupId) {
                $query->where('status', '!=', 'rejected')
                    ->when($returnGroupId, fn($query) => $query->where(function ($builder) use ($returnGroupId) {
                        $builder->whereNull('return_group_id')->orWhere('return_group_id', '!=', $returnGroupId);
                    }));
            }], 'returned_quantity')
            ->where('marketing_representative_id', $representativeId)
            ->whereDate('request_date', $requestDate)
            ->where('status', '!=', 'rejected')
            ->whereRaw('quantity > (select COALESCE(SUM(returned_quantity), 0) from book_returns where book_returns.book_request_id = book_requests.id and book_returns.status != ?' . $excludeCurrentGroup . ')', $remainingBindings)
            ->when($term !== '', fn($query) => $query->whereHas('product', fn($productQuery) => $productQuery->where('name', 'like', "%{$term}%")))
            ->orderByDesc('id')
            ->limit(30)
            ->get();

        return response()->json([
            'results' => $items->map(fn($item) => [
                'id' => $item->id,
                'text' => ($item->product?->name ?? '#' . $item->product_id)
                    . ' (Requested: ' . $item->quantity . ', Remaining: ' . ($item->quantity - (int) $item->returned_total) . ')'
                    . ($item->product_distribution_id ? ' [Dist#' . $item->product_distribution_id . ']' : ''),
                'issued_quantity' => $item->quantity,
                'remaining_quantity' => $item->quantity - (int) $item->returned_total,
                'product_distribution_id' => $item->product_distribution_id,
            ])->values(),
        ]);
    }

    private function validatedItems(Request $request, ?BookReturn $editingReturn = null): array
    {
        $data = $request->validate([
            'marketing_representative_id' => ['required', 'integer', Rule::exists('marketing_representatives', 'id')],
            'institution_id' => ['nullable', 'integer', Rule::exists('institutions', 'id')],
            // request_date kept for backward-compat (old BookRequest flow) but
            // Products now come from Distribution List, so it is optional.
            'request_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.returned_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(array_keys(BookReturn::STATUSES))],
            'received_by' => ['nullable', 'string', 'max:191'],
            'received_date' => ['nullable', 'date'],
        ]);

        $hasDistribution = collect($data['items'])->contains(fn($i) => ! empty($i['product_distribution_id']));
        $hasBookRequest = collect($data['items'])->contains(fn($i) => ! empty($i['book_request_id']));

        // New flow: Products from Distribution List (same as Book Requests)
        if ($hasDistribution || ! $hasBookRequest) {
            $request->validate([
                'items.*.product_distribution_id' => ['required', 'integer', 'distinct', Rule::exists('product_distributions', 'id')],
            ]);

            $representative = MarketingRepresentative::find($data['marketing_representative_id']);
            $distIds = collect($request->input('items'))->pluck('product_distribution_id')->map(fn($id) => (int) $id);
            $distributions = ProductDistribution::whereIn('id', $distIds)
                ->where('user_id', (string) $representative->user_id)
                ->get()
                ->keyBy('id');

            if ($distributions->count() !== $distIds->unique()->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Select products from the Distribution List for this representative.',
                ]);
            }

            $groupId = $editingReturn?->return_group_id;
            $legacyId = $editingReturn?->id;
            $items = [];
            foreach ($request->input('items') as $index => $item) {
                $distribution = $distributions->get((int) $item['product_distribution_id']);
                $previousReturns = BookReturn::where('product_distribution_id', $distribution->id)
                    ->where('status', '!=', 'rejected')
                    ->when($groupId, fn($query) => $query->where('return_group_id', '!=', $groupId))
                    ->when(! $groupId && $legacyId, fn($query) => $query->where('id', '!=', $legacyId))
                    ->sum('returned_quantity');
                $remaining = max(0, (int) $distribution->quantity - (int) $previousReturns);

                if ((int) $item['returned_quantity'] > $remaining) {
                    throw ValidationException::withMessages([
                        "items.{$index}.returned_quantity" => "Returned quantity cannot exceed distributed remaining quantity (Dist remaining: {$remaining}). Must be equal or less.",
                    ]);
                }

                // Link to auto-created BookRequest when it exists (for traceability)
                $linkedRequest = BookRequest::where('product_distribution_id', $distribution->id)->first();

                $items[] = [
                    'book_request_id' => $linkedRequest?->id,
                    'product_distribution_id' => $distribution->id,
                    'product_id' => $distribution->product_id,
                    'issued_quantity' => (int) $distribution->quantity,
                    'returned_quantity' => (int) $item['returned_quantity'],
                ];
            }

            unset($data['request_date'], $data['items']);
            $data['institution_id'] = empty($data['institution_id']) ? null : $data['institution_id'];
            $data['received_date'] = empty($data['received_date']) ? null : $data['received_date'];

            return [$data, $items];
        }

        // Old flow (backward-compat): items.*.book_request_id
        $request->validate([
            'items.*.book_request_id' => ['required', 'integer', 'distinct', Rule::exists('book_requests', 'id')],
        ]);
        $request->validate(['request_date' => ['required', 'date']]);
        $data['request_date'] = $request->input('request_date');

        $requestIds = collect($data['items'])->pluck('book_request_id')->map(fn($id) => (int) $id);
        $bookRequests = BookRequest::whereIn('id', $requestIds)
            ->where('marketing_representative_id', $data['marketing_representative_id'])
            ->whereDate('request_date', $data['request_date'])
            ->where('status', '!=', 'rejected')
            ->get()
            ->keyBy('id');

        if ($bookRequests->count() !== $requestIds->unique()->count()) {
            throw ValidationException::withMessages([
                'items' => 'Select book requests that match the representative and request date.',
            ]);
        }

        $groupId = $editingReturn?->return_group_id;
        $legacyId = $editingReturn?->id;
        $items = [];
        foreach ($data['items'] as $index => $item) {
            $bookRequest = $bookRequests->get((int) $item['book_request_id']);
            $previousReturns = BookReturn::where('book_request_id', $bookRequest->id)
                ->where('status', '!=', 'rejected')
                ->when($groupId, fn($query) => $query->where('return_group_id', '!=', $groupId))
                ->when(! $groupId && $legacyId, fn($query) => $query->where('id', '!=', $legacyId))
                ->sum('returned_quantity');
            $remaining = max(0, $bookRequest->quantity - $previousReturns);

            if ((int) $item['returned_quantity'] > $remaining) {
                throw ValidationException::withMessages([
                    "items.{$index}.returned_quantity" => "Returned quantity cannot exceed the remaining requested quantity ({$remaining}).",
                ]);
            }

            $items[] = [
                'book_request_id' => $bookRequest->id,
                'product_id' => $bookRequest->product_id,
                'issued_quantity' => $bookRequest->quantity,
                'returned_quantity' => (int) $item['returned_quantity'],
            ];
        }

        unset($data['request_date'], $data['items']);
        $data['institution_id'] = empty($data['institution_id']) ? null : $data['institution_id'];
        $data['received_date'] = empty($data['received_date']) ? null : $data['received_date'];

        return [$data, $items];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'marketing_representative_id' => ['required', 'integer', Rule::exists('marketing_representatives', 'id')],
            'institution_id' => ['nullable', 'integer', Rule::exists('institutions', 'id')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'issued_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'returned_quantity' => ['required', 'integer', 'min:0', 'lte:issued_quantity'],
            'note' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(array_keys(BookReturn::STATUSES))],
            'received_by' => ['nullable', 'string', 'max:191'],
            'received_date' => ['nullable', 'date'],
        ]);

        if (empty($data['institution_id'])) {
            $data['institution_id'] = null;
        }

        if (empty($data['received_date'])) {
            $data['received_date'] = null;
        }

        return $data;
    }
}
