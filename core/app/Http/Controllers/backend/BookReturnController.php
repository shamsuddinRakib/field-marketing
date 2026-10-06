<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\backend\BookReturn;
use App\Models\backend\MarketingRepresentative;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        $bookReturn->load(['representative.user', 'institution', 'product']);

        return view('backend.modules.book_returns.edit_modal', compact('bookReturn'));
    }

    public function show(BookReturn $bookReturn)
    {
        $bookReturn->load(['representative.user', 'institution', 'product']);

        return view('backend.modules.book_returns.show', compact('bookReturn'));
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
            'product_name' => 'p.name',
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

        $query = BookReturn::query()
            ->leftJoin('marketing_representatives as mr', 'mr.id', '=', 'book_returns.marketing_representative_id')
            ->leftJoin('users as us', 'us.id', '=', 'mr.user_id')
            ->leftJoin('institutions as i', 'i.id', '=', 'book_returns.institution_id')
            ->leftJoin('products as p', 'p.id', '=', 'book_returns.product_id')
            ->select(
                'book_returns.*',
                'us.name as representative_name',
                'mr.employee_id as representative_employee_id',
                'p.name as product_name',
                'i.institution_name as institution_name'
            );

        $total = (clone $query)->count('book_returns.id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('us.name', 'like', "%{$search}%")
                    ->orWhere('mr.employee_id', 'like', "%{$search}%")
                    ->orWhere('i.institution_name', 'like', "%{$search}%")
                    ->orWhere('p.name', 'like', "%{$search}%")
                    ->orWhere('book_returns.note', 'like', "%{$search}%")
                    ->orWhere('book_returns.status', 'like', "%{$search}%")
                    ->orWhere('book_returns.received_by', 'like', "%{$search}%")
                    ->orWhere('book_returns.received_date', 'like', "%{$search}%")
                    ->orWhere('book_returns.issued_quantity', 'like', "%{$search}%")
                    ->orWhere('book_returns.returned_quantity', 'like', "%{$search}%");
            });
        }

        $filtered = (clone $query)->count('book_returns.id');

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
                . '<a href="' . $showUrl . '" class="w-32-px h-32-px rounded-circle d-inline-flex align-items-center justify-content-center bg-info-focus text-info-main" title="View"><iconify-icon icon="lucide:eye"></iconify-icon></a>'
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
                $bookReturn->received_date?->format('d M, Y') ?? '-',
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
        $bookReturn = BookReturn::create($this->validated($request));

        return response()->json([
            'ok' => true,
            'id' => $bookReturn->id,
            'msg' => 'Book return created.',
        ]);
    }

    public function update(Request $request, BookReturn $bookReturn)
    {
        $bookReturn->update($this->validated($request));

        return response()->json([
            'ok' => true,
            'msg' => 'Book return updated.',
        ]);
    }

    public function destroy(BookReturn $bookReturn)
    {
        $bookReturn->delete();

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
