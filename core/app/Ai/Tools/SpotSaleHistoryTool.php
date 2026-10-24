<?php

namespace App\Ai\Tools;

use Throwable;
use App\Ai\Concerns\FormatsAgentOutput;
use App\Ai\Concerns\ResolvesTimeframe;
use App\Ai\Concerns\ResolvesUser;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SpotSaleHistoryTool implements Tool
{
    use FormatsAgentOutput;
    use ResolvesTimeframe;
    use ResolvesUser;

    public function description(): Stringable|string
    {
        return 'Lists detailed spot sales in a period (date, rep, teacher/library customer, status, items with product, quantity, price) with pending vs approved totals. '
            .'Answers "show me rep X\'s spot sales last week", "how many pending spot sales". '
            .'When the user gives no date range it defaults to the previous calendar month; always tell the user the period used.';
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            $frame = $this->resolveTimeframe($request);

            if (is_string($frame)) {
                return $frame;
            }

            $status = $this->agentStatus($request, ['pending', 'approved', 'all']);

            $rep = $this->resolveRepresentative($request);

            if ($error = $this->representativeGuard($rep)) {
                return $error;
            }

            $product = trim((string) ($request['product'] ?? ''));
            $limit = $this->agentLimit($request, 10, 50);
            $period = $this->agentPeriod($frame);

            $common = function ($q) use ($frame, $status, $rep, $product) {
                return $q->whereBetween('ss.created_at', [$frame['from'], $frame['to']])
                    ->when($status !== 'all', fn ($w) => $w->where('ss.status', $status))
                    ->when($rep['type'] === 'match', fn ($w) => $w->where('ss.user_id', $rep['user_id']))
                    ->when($product !== '', fn ($w) => $w->whereExists(function ($sub) use ($product) {
                        $sub->selectRaw('1')
                            ->from('spot_sale_items as ssi')
                            ->join('products as p', 'p.id', '=', 'ssi.product_id')
                            ->whereColumn('ssi.spot_sale_id', 'ss.id')
                            ->where(function ($inner) use ($product) {
                                $inner->where('p.name', 'like', "%{$product}%")
                                    ->orWhere('p.sku', 'like', "%{$product}%");
                            });
                    }));
            };

            $sales = DB::table('spot_sales as ss')
                ->join('users as u', 'u.id', '=', 'ss.user_id')
                ->leftJoin('teachers as t', 't.id', '=', 'ss.teacher_id')
                ->leftJoin('libraries as l', 'l.id', '=', 'ss.library_id')
                ->where($common)
                ->orderByDesc('ss.created_at')
                ->limit($limit)
                ->select('ss.id as sale_id', 'ss.created_at', 'ss.status', 'ss.total', 'ss.note')
                ->selectRaw(
                    $this->repNameRaw('u', 'ss.user_id').' as rep_name, '
                    ."(CASE WHEN ss.teacher_id IS NOT NULL THEN 'teacher' WHEN ss.library_id IS NOT NULL THEN 'library' ELSE NULL END) as customer_type, "
                    .'COALESCE(t.teacher_name, l.library_name) AS customer_name'
                )
                ->get();

            $summary = DB::table('spot_sales as ss')
                ->where($common)
                ->selectRaw('COUNT(*) as total_sales')
                ->selectRaw("SUM(CASE WHEN ss.status = 'pending' THEN 1 ELSE 0 END) as pending_count")
                ->selectRaw("SUM(CASE WHEN ss.status = 'pending' THEN ss.total ELSE 0 END) as pending_amount")
                ->selectRaw("SUM(CASE WHEN ss.status = 'approved' THEN 1 ELSE 0 END) as approved_count")
                ->selectRaw("SUM(CASE WHEN ss.status = 'approved' THEN ss.total ELSE 0 END) as approved_amount")
                ->selectRaw('SUM(ss.total) as total_amount')
                ->first();

            if ($summary->total_sales === 0) {
                $scope = $rep['type'] === 'match' ? " for {$rep['name']}" : '';

                return $this->agentEmpty(
                    "No spot sales found {$scope} in period {$period['from']} to {$period['to']}".($status !== 'all' ? " with status '{$status}'" : '').".",
                    $period
                );
            }

            $items = DB::table('spot_sale_items as ssi')
                ->leftJoin('products as p', 'p.id', '=', 'ssi.product_id')
                ->whereIn('ssi.spot_sale_id', $sales->pluck('sale_id'))
                ->select('ssi.spot_sale_id', DB::raw("COALESCE(NULLIF(p.name, ''), CONCAT('Product #', ssi.product_id)) as product_name"), 'ssi.price', 'ssi.quantity', 'ssi.total')
                ->orderBy('ssi.id')
                ->get();

            $itemsBySale = $items->groupBy('spot_sale_id');

            $scopeNote = $rep['type'] === 'match' ? " for {$rep['name']}" : '';

            return $this->agentJson([
                'ok' => true,
                'period_used' => $period,
                'summary' => [
                    'scope' => $scopeNote ?: 'all representatives',
                    'total_sales' => (int) $summary->total_sales,
                    'total_amount' => $this->agentNumber($summary->total_amount),
                    'pending' => [
                        'count' => (int) $summary->pending_count,
                        'amount' => $this->agentNumber($summary->pending_amount),
                    ],
                    'approved' => [
                        'count' => (int) $summary->approved_count,
                        'amount' => $this->agentNumber($summary->approved_amount),
                    ],
                    'currency' => 'Tk',
                ],
                'sales' => $sales->map(function ($row) use ($itemsBySale) {
                    return [
                        'sale_id' => (int) $row->sale_id,
                        'date' => substr((string) $row->created_at, 0, 16),
                        'rep_name' => (string) $row->rep_name,
                        'customer' => [
                            'type' => $row->customer_type ? (string) $row->customer_type : 'unknown',
                            'name' => $row->customer_name ? (string) $row->customer_name : null,
                        ],
                        'status' => (string) $row->status,
                        'total' => $this->agentNumber($row->total),
                        'note' => $row->note ? (string) $row->note : null,
                        'items' => $itemsBySale->get($row->sale_id, collect())->map(fn ($item) => [
                            'product_name' => (string) $item->product_name,
                            'quantity' => (int) $item->quantity,
                            'price' => $this->agentNumber($item->price),
                            'total' => $this->agentNumber($item->total),
                        ])->all(),
                    ];
                })->all(),
                'note' => (int) $summary->total_sales > $sales->count()
                    ? ('Showing the most recent '.$sales->count().' of '.(int) $summary->total_sales.' sales. Raise the limit to see more.')
                    : null,
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->agentError($e->getMessage() ?: 'Unexpected error while listing spot sales.');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from_date' => $schema->string()->description('Period start (inclusive), Y-m-d. If user gave no dates, omit and the previous calendar month is used.'),
            'to_date' => $schema->string()->description('Period end (inclusive), Y-m-d.'),
            'representative' => $schema->string()->description('Optional representative name, employee id or user id to scope results to one rep.'),
            'product' => $schema->string()->description('Optional product name or sku filter.'),
            'status' => $schema->string()->enum(['pending', 'approved', 'all'])->description('Sale status filter, default all.'),
            'limit' => $schema->integer()->description('Max sales to list (most recent first), default 10.'),
        ];
    }
}
