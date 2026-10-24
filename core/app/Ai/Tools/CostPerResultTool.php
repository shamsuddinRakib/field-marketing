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

class CostPerResultTool implements Tool
{
    use FormatsAgentOutput;
    use ResolvesTimeframe;
    use ResolvesUser;

    public function description(): Stringable|string
    {
        return 'Calculates cost per result for representatives in a period: total posted expenses divided by results, where a result is a daily visit (default) or a spot sale. '
            .'Answers "what is rep X\'s cost per visit/sale in a range". Lower cost per result is better. '
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

            $resultType = strtolower(trim((string) ($request['result_type'] ?? '')));

            if ($resultType === '') {
                $resultType = 'visits';
            }

            if (! in_array($resultType, ['visits', 'spot_sales'], true)) {
                return $this->agentError("Invalid result_type '{$resultType}'. Allowed values: visits, spot_sales.");
            }

            $period = $this->agentPeriod($frame);

            $userIds = DB::table('marketing_representatives as mr')
                ->whereNull('mr.deleted_at')
                ->distinct()
                ->pluck('mr.user_id');

            $names = DB::table('users as u')
                ->whereIn('u.id', $userIds->all() ?: [0])
                ->pluck('u.name', 'u.id');

            $visitCounts = DB::table('daily_visits')
                ->whereBetween('created_at', [$frame['from'], $frame['to']])
                ->groupBy('user_id')
                ->selectRaw('user_id, COUNT(*) as c')
                ->get()
                ->mapWithKeys(fn ($r) => [(int) $r->user_id => (int) $r->c]);

            $salesRows = DB::table('spot_sales')
                ->whereBetween('created_at', [$frame['from'], $frame['to']])
                ->groupBy('user_id')
                ->selectRaw('user_id, COUNT(*) as c, COALESCE(SUM(total), 0) as rev')
                ->get()
                ->keyBy(fn ($r) => (int) $r->user_id);

            $expenseTotals = DB::table('expenses')
                ->where('status', 'posted')
                ->whereBetween('expense_date', [$frame['from']->toDateString(), $frame['to']->toDateString()])
                ->groupBy('user_id')
                ->selectRaw('user_id, COALESCE(SUM(total_amount), 0) as s')
                ->get()
                ->mapWithKeys(fn ($r) => [(int) $r->user_id => (float) $r->s]);

            $activeAll = array_merge(
                $visitCounts->keys()->all(),
                $salesRows->keys()->all(),
                $expenseTotals->keys()->all()
            );

            $ids = $rep['type'] === 'match'
                ? collect([(int) $rep['user_id']])
                : collect($userIds)->merge($activeAll)->unique()->values();

            if ($ids->isEmpty()) {
                return $this->agentEmpty("No representatives found in period {$period['from']} to {$period['to']}.", $period);
            }

            $rows = collect();

            foreach ($ids as $userId) {
                $userId = (int) $userId;
                $visits = (int) ($visitCounts[$userId] ?? 0);
                $sales = (int) ($salesRows[$userId]->c ?? 0);
                $revenue = $this->agentNumber($salesRows[$userId]->rev ?? 0);
                $expense = $this->agentNumber($expenseTotals[$userId] ?? 0);

                if ($rep['type'] === 'none' && $visits === 0 && $sales === 0 && $expense == 0) {
                    continue;
                }

                $resultCount = $resultType === 'visits' ? $visits : $sales;

                $rows->push([
                    'user_id' => $userId,
                    'rep_name' => (string) ($names[$userId] ?? 'User #'.$userId),
                    'total_visits' => $visits,
                    'total_spot_sales' => $sales,
                    'spot_sales_revenue' => $revenue,
                    'total_expense' => $expense,
                    'result_count' => $resultCount,
                    'cost_per_result' => $resultCount > 0 ? $this->agentNumber($expense / $resultCount) : null,
                    'currency' => 'Tk',
                ]);
            }

            if ($rows->isEmpty()) {
                $scope = $rep['type'] === 'match' ? " for {$rep['name']}" : '';

                return $this->agentEmpty(
                    "No visits, spot sales or expenses found {$scope} in period {$period['from']} to {$period['to']}.",
                    $period
                );
            }

            $comparable = $rows->filter(fn ($r) => $r['cost_per_result'] !== null)->sortBy('cost_per_result')->values();
            $companyAvgCost = $comparable->isEmpty() ? null : $this->agentNumber($comparable->avg('cost_per_result'));
            $companyAvgExpense = $this->agentNumber($rows->avg('total_expense'));
            $companyAvgVisits = $this->agentNumber($rows->avg('total_visits'));
            $companyAvgSales = $this->agentNumber($rows->avg('total_spot_sales'));

            $basePayload = [
                'ok' => true,
                'period_used' => $period,
                'result_type' => $resultType,
                'currency' => 'Tk',
                'definition' => 'cost_per_result = total posted expense divided by '.($resultType === 'visits' ? 'daily visits' : 'spot sales').'; null means zero results in the period.',
                'company_average' => [
                    'avg_total_expense' => $companyAvgExpense,
                    'avg_total_visits' => $companyAvgVisits,
                    'avg_total_spot_sales' => $companyAvgSales,
                    'avg_cost_per_result' => $companyAvgCost,
                    'cost_per_result_based_on' => 'representatives with at least one result only',
                ],
            ];

            if ($rep['type'] === 'match') {
                $row = $rows->firstWhere('user_id', $rep['user_id']);

                $payload = array_merge($basePayload, [
                    'representative' => $row,
                    'note' => $row['cost_per_result'] === null
                        ? ('Cost per result unavailable: '.$rep['name'].' had zero '.($resultType === 'visits' ? 'visits' : 'spot sales').' in the period.')
                        : null,
                ]);

                return $this->agentJson($payload);
            }

            $limit = $this->agentLimit($request, 10, 50);

            $payload = array_merge($basePayload, [
                'ranking_best_first' => $comparable->take($limit)->values()->all(),
                'note' => ($comparable->count() > $limit ? ($comparable->count() - $limit).' more representatives outside the requested limit. ' : '')
                    .'null cost_per_result rows (zero results) were excluded from the ranking; there are '
                    .$rows->filter(fn ($r) => $r['cost_per_result'] === null)->count().' such representatives.',
            ]);

            return $this->agentJson($payload);
        } catch (Throwable $e) {
            report($e);

            return $this->agentError($e->getMessage() ?: 'Unexpected error while calculating cost per result.');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from_date' => $schema->string()->description('Period start (inclusive), Y-m-d. If user gave no dates, omit and the previous calendar month is used.'),
            'to_date' => $schema->string()->description('Period end (inclusive), Y-m-d.'),
            'representative' => $schema->string()->description('Optional representative name, employee id or user id. When omitted all reps are ranked by lowest cost per result.'),
            'result_type' => $schema->string()->enum(['visits', 'spot_sales'])->description('What counts as a result: visits (default) or spot_sales.'),
        ];
    }
}
