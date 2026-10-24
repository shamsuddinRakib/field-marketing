<?php

namespace App\Ai\Tools;

use Throwable;
use App\Ai\Concerns\FormatsAgentOutput;
use App\Ai\Concerns\ResolvesTimeframe;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class EfficiencyRankingTool implements Tool
{
    use FormatsAgentOutput;
    use ResolvesTimeframe;

    public function description(): Stringable|string
    {
        return 'Ranks ALL representatives by efficiency (cost per result) in a period: total posted expenses divided by daily visits (default) or spot sales. '
            .'The best representative has the LOWEST cost per result. '
            .'Answers "most efficient representative in terms of cost per result within a time range". '
            .'When the user gives no date range it defaults to the previous calendar month; always tell the user the period used.';
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            $frame = $this->resolveTimeframe($request);

            if (is_string($frame)) {
                return $frame;
            }

            $resultType = strtolower(trim((string) ($request['result_type'] ?? '')));

            if ($resultType === '') {
                $resultType = 'visits';
            }

            if (! in_array($resultType, ['visits', 'spot_sales'], true)) {
                return $this->agentError("Invalid result_type '{$resultType}'. Allowed values: visits, spot_sales.");
            }

            $minResults = max(1, (int) ($request['min_results'] ?? 1));
            $period = $this->agentPeriod($frame);

            $reps = DB::table('marketing_representatives as mr')
                ->join('users as u', 'u.id', '=', 'mr.user_id')
                ->leftJoin('marketing_representatives as mr2', 'mr2.user_id', '=', 'mr.user_id')
                ->whereNull('mr.deleted_at')
                ->select('mr.user_id', DB::raw("COALESCE(NULLIF(u.name, ''), NULLIF(mr.employee_id, ''), CONCAT('User #', mr.user_id)) as rep_name"))
                ->distinct()
                ->get()
                ->keyBy(fn ($r) => (int) $r->user_id);

            $visitCounts = DB::table('daily_visits')
                ->whereBetween('created_at', [$frame['from'], $frame['to']])
                ->groupBy('user_id')
                ->selectRaw('user_id, COUNT(*) as c')
                ->get()
                ->mapWithKeys(fn ($r) => [(int) $r->user_id => (int) $r->c]);

            $salesRows = DB::table('spot_sales')
                ->whereBetween('created_at', [$frame['from'], $frame['to']])
                ->groupBy('user_id')
                ->selectRaw('user_id, COUNT(*) as c')
                ->get()
                ->mapWithKeys(fn ($r) => [(int) $r->user_id => (int) $r->c]);

            $expenseTotals = DB::table('expenses')
                ->where('status', 'posted')
                ->whereBetween('expense_date', [$frame['from']->toDateString(), $frame['to']->toDateString()])
                ->groupBy('user_id')
                ->selectRaw('user_id, COALESCE(SUM(total_amount), 0) as s')
                ->get()
                ->mapWithKeys(fn ($r) => [(int) $r->user_id => (float) $r->s]);

            if ($reps->isEmpty()) {
                return $this->agentEmpty('No representatives found.', $period);
            }

            $rows = $reps->map(function ($user) use ($visitCounts, $salesRows, $expenseTotals) {
                $userId = (int) $user->user_id;

                return [
                    'user' => $user,
                    'visits' => (int) ($visitCounts[$userId] ?? 0),
                    'sales' => (int) ($salesRows[$userId] ?? 0),
                    'expense' => (float) ($expenseTotals[$userId] ?? 0),
                ];
            });

            $results = collect();
            $excluded = collect();

            $rows->each(function ($r) use ($results, $excluded, $minResults, $resultType) {
                $resultCount = $resultType === 'visits' ? $r['visits'] : $r['sales'];

                $row = [
                    'user_id' => (int) $r['user']->user_id,
                    'rep_name' => (string) $r['user']->rep_name,
                    'total_visits' => $r['visits'],
                    'total_spot_sales' => $r['sales'],
                    'total_expense' => $this->agentNumber($r['expense']),
                    'result_count' => $resultCount,
                    'cost_per_result' => $resultCount > 0 ? $this->agentNumber($r['expense'] / $resultCount) : null,
                    'currency' => 'Tk',
                ];

                if ($resultCount >= $minResults && $row['cost_per_result'] !== null) {
                    $results->push($row);
                } else {
                    $excluded->push($row);
                }
            });

            $results = $results->sortBy('cost_per_result')->values();
            $excluded = $excluded->sortBy('cost_per_result')->values();

            if ($results->isEmpty()) {
                if ($excluded->isEmpty()) {
                    return $this->agentEmpty(
                        "No representative activity found in period {$period['from']} to {$period['to']}.",
                        $period
                    );
                }

                $sole = $excluded->sortByDesc('result_count')->first();

                return $this->agentEmpty(
                    "No representative had at least {$minResults} ".($resultType === 'visits' ? 'visits' : 'spot sales')
                        ." in period {$period['from']} to {$period['to']}; the closest was {$sole['rep_name']} with {$sole['result_count']}.",
                    $period
                );
            }

            $limit = $this->agentLimit($request);

            return $this->agentJson([
                'ok' => true,
                'period_used' => $period,
                'result_type' => $resultType,
                'definition' => 'cost_per_result = total posted expense divided by '.($resultType === 'visits' ? 'daily visits' : 'spot sales').'; lowest = most efficient.',
                'efficiency_ranking' => $results->take($limit)->map(function ($row, $index) {
                    return array_merge(['rank' => $index + 1], $row);
                })->all(),
                'company_average' => [
                    'avg_cost_per_result' => $this->agentNumber($results->avg('cost_per_result')),
                    'avg_total_expense' => $this->agentNumber($results->avg('total_expense')),
                    'avg_result_count' => $this->agentNumber($results->avg('result_count')),
                ],
                'excluded_from_ranking' => $excluded->map(function ($row) {
                    return [
                        'rep_name' => $row['rep_name'],
                        'user_id' => $row['user_id'],
                        'result_count' => $row['result_count'],
                        'total_expense' => $row['total_expense'],
                        'reason' => $row['result_count'] === 0
                            ? 'zero results in the period'
                            : 'below min_results threshold',
                    ];
                })->take(10)->all(),
                'note' => $results->count() > $limit
                    ? ($results->count() - $limit).' more representatives outside the requested limit.'
                    : null,
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->agentError($e->getMessage() ?: 'Unexpected error while ranking efficiency.');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from_date' => $schema->string()->description('Period start (inclusive), Y-m-d. If user gave no dates, omit and the previous calendar month is used.'),
            'to_date' => $schema->string()->description('Period end (inclusive), Y-m-d.'),
            'result_type' => $schema->string()->enum(['visits', 'spot_sales'])->description('What counts as a result: visits (default) or spot_sales.'),
            'min_results' => $schema->integer()->description('Minimum results (visits or spot sales) to be ranked; rows below are excluded and listed separately. Default 1.'),
            'limit' => $schema->integer()->description('Max rows in the ranking, default 5.'),
        ];
    }
}
