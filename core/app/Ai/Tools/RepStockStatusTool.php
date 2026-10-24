<?php

namespace App\Ai\Tools;

use Throwable;
use App\Ai\Concerns\FormatsAgentOutput;
use App\Ai\Concerns\ResolvesUser;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class RepStockStatusTool implements Tool
{
    use FormatsAgentOutput;
    use ResolvesUser;

    public function description(): Stringable|string
    {
        return 'Shows the CURRENT product stock holding per marketing representative (from current stocks), including zero-stock and low-stock warnings. '
            .'Answers "representative product stock status", "which reps are out of stock of product X", "show rep X\'s stock". '
            .'This is a present-state tool, no date range needed. Pass only_low_stock=true to list only problematic rows.';
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            $rep = $this->resolveRepresentative($request);

            if ($error = $this->representativeGuard($rep)) {
                return $error;
            }

            $threshold = (int) ($request['threshold'] ?? 5);

            if ($threshold < 0) {
                $threshold = 0;
            }

            $onlyLow = $this->agentBool($request, 'only_low_stock');
            $product = trim((string) ($request['product'] ?? ''));

            $productIds = collect();

            if ($product !== '') {
                $productIds = DB::table('products as p')
                    ->whereNull('p.deleted_at')
                    ->where(function ($w) use ($product) {
                        $w->where('p.name', 'like', "%{$product}%")
                            ->orWhere('p.sku', 'like', "%{$product}%");
                    })
                    ->limit(10)
                    ->pluck('p.id');

                if ($productIds->isEmpty()) {
                    return $this->agentError("No active product matched '{$product}'. Call list_products to find valid names.");
                }
            }

            $base = function ($q) use ($rep, $productIds, $threshold, $onlyLow) {
                return $q
                    ->when($rep['type'] === 'match', fn ($w) => $w->where('cs.user_id', $rep['user_id']))
                    ->when($productIds->isNotEmpty(), fn ($w) => $w->whereIn('cs.product_id', $productIds->all()))
                    ->when($onlyLow, fn ($w) => $w->where('cs.current_stock', '<=', $threshold));
            };

            $detailRows = $rep['type'] === 'match' || $onlyLow || $productIds->isNotEmpty();

            if ($detailRows) {
                $rows = DB::table('current_stocks as cs')
                    ->join('users as u', 'u.id', '=', 'cs.user_id')
                    ->leftJoin('products as p', 'p.id', '=', 'cs.product_id')
                    ->where($base)
                    ->orderBy('cs.current_stock')
                    ->orderBy('product_name')
                    ->select(
                        'cs.user_id',
                        'cs.product_id',
                        'cs.current_stock',
                        DB::raw($this->repNameRaw('u', 'cs.user_id').' as rep_name'),
                        DB::raw("COALESCE(NULLIF(p.name, ''), CONCAT('Product #', cs.product_id)) as product_name"),
                        DB::raw('COALESCE(p.price, 0) as price'),
                        DB::raw("CASE WHEN cs.current_stock <= 0 THEN 1 ELSE 0 END as is_out"),
                        DB::raw("CASE WHEN cs.current_stock > 0 AND cs.current_stock <= {$threshold} THEN 1 ELSE 0 END as is_low")
                    )
                    ->limit($this->agentLimit($request, 30, 100))
                    ->get();

                if ($rows->isEmpty()) {
                    $scope = $rep['type'] === 'match' ? " for {$rep['name']}" : '';

                    return $this->agentEmpty(
                        "No stock entries found {$scope}".($onlyLow ? " at or below {$threshold} units." : '.'),
                    );
                }

                return $this->agentJson([
                    'ok' => true,
                    'as_of' => now()->format('Y-m-d H:i'),
                    'low_stock_threshold' => $threshold,
                    'price_note' => 'Prices are in BDT (Tk).',
                    'rows' => $rows->values()->map(fn ($row) => [
                        'user_id' => (int) $row->user_id,
                        'rep_name' => (string) $row->rep_name,
                        'product_id' => (int) $row->product_id,
                        'product_name' => (string) $row->product_name,
                        'current_stock' => (int) $row->current_stock,
                        'price' => $this->agentNumber($row->price),
                        'flag' => (int) $row->is_out === 1 ? 'out_of_stock' : ((int) $row->is_low === 1 ? 'low_stock' : 'ok'),
                    ])->all(),
                ]);
            }

            $summary = DB::table('current_stocks as cs')
                ->join('users as u', 'u.id', '=', 'cs.user_id')
                ->groupBy('cs.user_id')
                ->orderByDesc('total_units')
                ->orderBy('rep_name')
                ->select(
                    'cs.user_id',
                    DB::raw($this->repNameExpression('u', 'cs.user_id')),
                    DB::raw('COUNT(*) as product_count'),
                    DB::raw('COALESCE(SUM(cs.current_stock), 0) as total_units'),
                    DB::raw('SUM(CASE WHEN cs.current_stock <= 0 THEN 1 ELSE 0 END) as zero_stock_products'),
                    DB::raw("SUM(CASE WHEN cs.current_stock > 0 AND cs.current_stock <= {$threshold} THEN 1 ELSE 0 END) as low_stock_products")
                )
                ->get();

            if ($summary->isEmpty()) {
                return $this->agentEmpty('No stock entries found for any representative.');
            }

            return $this->agentJson([
                'ok' => true,
                'as_of' => now()->format('Y-m-d H:i'),
                'low_stock_threshold' => $threshold,
                'summary_per_rep' => $summary->values()->map(fn ($row) => [
                    'user_id' => (int) $row->user_id,
                    'rep_name' => (string) $row->rep_name,
                    'product_count' => (int) $row->product_count,
                    'total_units' => (int) $row->total_units,
                    'zero_stock_products' => (int) $row->zero_stock_products,
                    'low_stock_products' => (int) $row->low_stock_products,
                ])->all(),
                'note' => 'Pass a representative name (or only_low_stock=true) for product-level stock rows.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->agentError($e->getMessage() ?: 'Unexpected error while fetching stock status.');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'representative' => $schema->string()->description('Optional representative name, employee id or user id. When provided, full product-level rows for that rep are returned.'),
            'product' => $schema->string()->description('Optional product name or sku filter across reps.'),
            'only_low_stock' => $schema->string()->enum(['true', 'false'])->description('When "true", returns only rows at/below the threshold (or zero stock).'),
            'threshold' => $schema->integer()->description('Low-stock threshold in units, default 5.'),
        ];
    }
}
