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

class SpotSaleTopProductsTool implements Tool
{
    use FormatsAgentOutput;
    use ResolvesTimeframe;
    use ResolvesUser;

    public function description(): Stringable|string
    {
        return 'Ranks the best-selling products in spot sales by revenue and quantity in a period, optionally for one representative. '
            .'Answers "which products sold most in spot sales" and product-wise performance. '
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

            $period = $this->agentPeriod($frame);
            $limit = $this->agentLimit($request, 10, 50);

            $rows = DB::table('spot_sale_items as ssi')
                ->join('spot_sales as ss', 'ss.id', '=', 'ssi.spot_sale_id')
                ->join('users as u', 'u.id', '=', 'ss.user_id')
                ->leftJoin('marketing_representatives as mr', 'mr.user_id', '=', 'ss.user_id')
                ->leftJoin('products as p', 'p.id', '=', 'ssi.product_id')
                ->whereBetween('ss.created_at', [$frame['from'], $frame['to']])
                ->when($status !== 'all', fn ($q) => $q->where('ss.status', $status))
                ->when($rep['type'] === 'match', fn ($q) => $q->where('ss.user_id', $rep['user_id']))
                ->groupBy('ssi.product_id')
                ->select(
                    'ssi.product_id',
                    DB::raw("COALESCE(NULLIF(p.name, ''), CONCAT('Product #', ssi.product_id)) as product_name"),
                    DB::raw('SUM(ssi.quantity) as total_qty'),
                    DB::raw('SUM(ssi.total) as total_revenue'),
                    DB::raw('COUNT(DISTINCT ss.id) as sales_count'),
                    DB::raw('AVG(ssi.price) as avg_price')
                )
                ->orderByDesc('total_revenue')
                ->limit($limit)
                ->get();

            if ($rows->isEmpty()) {
                $scope = $rep['type'] === 'match' ? " for {$rep['name']}" : '';

                return $this->agentEmpty(
                    "No spot sale items found {$scope} in period {$period['from']} to {$period['to']}.",
                    $period
                );
            }

            return $this->agentJson([
                'ok' => true,
                'period_used' => $period,
                'status_filter' => $status,
                'metric' => 'spot sale items grouped by product, highest revenue first',
                'top_products' => $rows->values()->map(function ($row, $index) {
                    return [
                        'rank' => $index + 1,
                        'product_id' => (int) $row->product_id,
                        'product_name' => (string) $row->product_name,
                        'quantity_sold' => (int) $row->total_qty,
                        'revenue' => $this->agentNumber($row->total_revenue),
                        'sales_count' => (int) $row->sales_count,
                        'avg_price' => $this->agentNumber($row->avg_price),
                        'currency' => 'Tk',
                    ];
                })->all(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->agentError($e->getMessage() ?: 'Unexpected error while ranking spot sale products.');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from_date' => $schema->string()->description('Period start (inclusive), Y-m-d. If user gave no dates, omit and the previous calendar month is used.'),
            'to_date' => $schema->string()->description('Period end (inclusive), Y-m-d.'),
            'representative' => $schema->string()->description('Optional representative name, employee id or user id to scope results to one rep.'),
            'status' => $schema->string()->enum(['pending', 'approved', 'all'])->description('Sale status filter, default all.'),
            'limit' => $schema->integer()->description('Max products to return, default 10.'),
        ];
    }
}
