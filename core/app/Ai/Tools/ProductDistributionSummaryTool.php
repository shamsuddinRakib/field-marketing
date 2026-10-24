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

class ProductDistributionSummaryTool implements Tool
{
    use FormatsAgentOutput;
    use ResolvesTimeframe;
    use ResolvesUser;

    public function description(): Stringable|string
    {
        return 'Summarizes product distributions to marketing representatives in a period: document counts (delivered vs pending), total quantity distributed and top products per rep. '
            .'Answers "how many products were distributed to rep X in Q1" and "who received the most stock". '
            .'When the user gives no date range it defaults to the previous calendar month; always tell the user the period used.';
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            $frame = $this->resolveTimeframe($request);

            if (is_string($frame)) {
                return $frame;
            }

            $rep = $this->resolveRepresentative($request);

            if ($error = $this->representativeGuard($rep)) {
                return $error;
            }

            $period = $this->agentPeriod($frame);

            $rows = DB::table('product_distributions as pd')
                ->join('users as u', 'u.id', '=', 'pd.user_id')
                ->leftJoin('product_distribution_items as pdi', 'pdi.product_distribution_id', '=', 'pd.id')
                ->whereBetween('pd.created_at', [$frame['from'], $frame['to']])
                ->when($rep['type'] === 'match', fn ($q) => $q->where('pd.user_id', $rep['user_id']))
                ->groupBy('pd.user_id')
                ->select(
                    'pd.user_id',
                    DB::raw($this->repNameExpression('u', 'pd.user_id')),
                    DB::raw('COUNT(DISTINCT pd.id) as doc_count'),
                    DB::raw("COUNT(DISTINCT CASE WHEN pd.status = 'delivered' THEN pd.id END) as delivered_docs"),
                    DB::raw("COUNT(DISTINCT CASE WHEN pd.status = 'pending' THEN pd.id END) as pending_docs"),
                    DB::raw('SUM(COALESCE(pdi.quantity, 0)) as total_qty')
                )
                ->orderByDesc('total_qty')
                ->orderBy('rep_name')
                ->get();

            if ($rows->isEmpty()) {
                $scope = $rep['type'] === 'match' ? " for {$rep['name']}" : '';

                return $this->agentEmpty(
                    "No product distributions found {$scope} in period {$period['from']} to {$period['to']}.",
                    $period
                );
            }

            $productRows = DB::table('product_distribution_items as pdi')
                ->join('product_distributions as pd', 'pd.id', '=', 'pdi.product_distribution_id')
                ->join('products as p', 'p.id', '=', 'pdi.product_id')
                ->whereBetween('pd.created_at', [$frame['from'], $frame['to']])
                ->when($rep['type'] === 'match', fn ($q) => $q->where('pd.user_id', $rep['user_id']))
                ->groupBy('pd.user_id', 'pdi.product_id')
                ->select(
                    'pd.user_id',
                    'pdi.product_id',
                    DB::raw("COALESCE(NULLIF(p.name, ''), CONCAT('Product #', pdi.product_id)) as product_name"),
                    DB::raw('SUM(pdi.quantity) as qty')
                )
                ->orderByDesc('qty')
                ->get()
                ->groupBy(fn ($r) => (int) $r->user_id);

            $limit = $this->agentLimit($request);
            $topPerRep = $rep['type'] === 'match' ? 10 : 3;

            return $this->agentJson([
                'ok' => true,
                'period_used' => $period,
                'currency_note' => 'quantities are units, not money',
                'summaries' => $rows->take($limit)->values()->map(function ($row) use ($productRows, $topPerRep) {
                    return [
                        'user_id' => (int) $row->user_id,
                        'rep_name' => (string) $row->rep_name,
                        'doc_count' => (int) $row->doc_count,
                        'delivered_docs' => (int) $row->delivered_docs,
                        'pending_docs' => (int) $row->pending_docs,
                        'total_qty' => (int) $row->total_qty,
                        'top_products' => $productRows->get((int) $row->user_id, collect())->take($topPerRep)->values()->map(fn ($t) => [
                            'product_name' => (string) $t->product_name,
                            'qty' => (int) $t->qty,
                        ])->all(),
                    ];
                })->all(),
                'note' => $rows->count() > $limit
                    ? ($rows->count() - $limit).' more representatives outside the requested limit.'
                    : null,
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->agentError($e->getMessage() ?: 'Unexpected error while summarizing product distributions.');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from_date' => $schema->string()->description('Period start (inclusive), Y-m-d. If user gave no dates, omit and the previous calendar month is used.'),
            'to_date' => $schema->string()->description('Period end (inclusive), Y-m-d.'),
            'representative' => $schema->string()->description('Optional representative name, employee id or user id to scope results to one rep.'),
            'limit' => $schema->integer()->description('Max reps in the summary, default 5.'),
        ];
    }
}
