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

class ExpenseSummaryTool implements Tool
{
    use FormatsAgentOutput;
    use ResolvesTimeframe;
    use ResolvesUser;

    public function description(): Stringable|string
    {
        return 'Summarizes representative expenses in a period (posted by default): totals per rep ranked, plus category breakdown. '
            .'Answers "representative expense within a time range", "what did rep X spend last month", "where is most money going". '
            .'When the user gives no date range it defaults to the previous calendar month; always tell the user the period used.';
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            $frame = $this->resolveTimeframe($request);

            if (is_string($frame)) {
                return $frame;
            }

            $status = $this->resolveExpenseStatus($request);

            $rep = $this->resolveRepresentative($request);

            if ($error = $this->representativeGuard($rep)) {
                return $error;
            }

            $category = trim((string) ($request['category'] ?? ''));
            $period = $this->agentPeriod($frame);

            $baseFilters = function ($q) use ($frame, $status, $rep, $category) {
                return $q
                    ->whereBetween('ex.expense_date', [$frame['from']->toDateString(), $frame['to']->toDateString()])
                    ->when($status !== 'all', fn ($w) => $w->where('ex.status', $status))
                    ->when($rep['type'] === 'match', fn ($w) => $w->where('ex.user_id', $rep['user_id']))
                    ->when($category !== '', fn ($w) => $w->whereExists(function ($sub) use ($category) {
                        $sub->selectRaw('1')
                            ->from('expense_items as ei2')
                            ->join('expense_categories as ec2', 'ec2.id', '=', 'ei2.expense_category_id')
                            ->whereColumn('ei2.expense_id', 'ex.id')
                            ->where('ec2.name', 'like', "%{$category}%");
                    }));
            };

            $repRows = DB::table('expenses as ex')
                ->join('users as u', 'u.id', '=', 'ex.user_id')
                ->where($baseFilters)
                ->groupBy('ex.user_id')
                ->select(
                    'ex.user_id',
                    DB::raw($this->repNameExpression('u', 'ex.user_id')),
                    DB::raw('COUNT(*) as expense_count'),
                    DB::raw('SUM(ex.total_amount) as total_amount'),
                    DB::raw('AVG(ex.total_amount) as avg_amount')
                )
                ->orderByDesc('total_amount')
                ->orderBy('rep_name')
                ->get();

            if ($repRows->isEmpty()) {
                $scope = $rep['type'] === 'match' ? " for {$rep['name']}" : '';

                return $this->agentEmpty(
                    "No expenses found {$scope} in period {$period['from']} to {$period['to']}".($status !== 'all' ? " with status '{$status}'" : '').'.',
                    $period
                );
            }

            $categoryRows = DB::table('expenses as ex')
                ->join('expense_items as ei', 'ei.expense_id', '=', 'ex.id')
                ->join('expense_categories as ec', 'ec.id', '=', 'ei.expense_category_id')
                ->where($baseFilters)
                ->groupBy('ec.id')
                ->select('ec.id', 'ec.name as category', DB::raw('SUM(ei.amount) as amount'), DB::raw('COUNT(DISTINCT ex.id) as expense_count'))
                ->orderByDesc('amount')
                ->get();

            $overallTotal = $repRows->sum('total_amount');

            $payload = [
                'ok' => true,
                'period_used' => $period,
                'status_filter' => $status,
                'currency' => 'Tk',
                'overall_total' => $this->agentNumber($overallTotal),
            ];

            if ($rep['type'] === 'match') {
                $row = $repRows->first();

                $payload['representative'] = [
                    'user_id' => (int) $row->user_id,
                    'rep_name' => (string) $row->rep_name,
                    'expense_count' => (int) $row->expense_count,
                    'total_amount' => $this->agentNumber($row->total_amount),
                    'avg_amount' => $this->agentNumber($row->avg_amount),
                ];
                $payload['category_breakdown'] = $categoryRows->values()->map(fn ($c) => [
                    'category' => (string) $c->category,
                    'amount' => $this->agentNumber($c->amount),
                    'expense_count' => (int) $c->expense_count,
                ])->all();
            } else {
                $limit = $this->agentLimit($request);

                $payload['per_representative'] = $repRows->take($limit)->values()->map(function ($row, $index) use ($overallTotal) {
                    return [
                        'rank' => $index + 1,
                        'user_id' => (int) $row->user_id,
                        'rep_name' => (string) $row->rep_name,
                        'expense_count' => (int) $row->expense_count,
                        'total_amount' => $this->agentNumber($row->total_amount),
                        'avg_amount' => $this->agentNumber($row->avg_amount),
                        'share_of_total_percent' => $overallTotal > 0 ? $this->agentNumber($row->total_amount / $overallTotal * 100) : null,
                    ];
                })->all();
                $payload['top_categories'] = $categoryRows->take(10)->values()->map(fn ($c) => [
                    'category' => (string) $c->category,
                    'amount' => $this->agentNumber($c->amount),
                ])->all();
                $payload['note'] = ($repRows->count() > $limit ? ($repRows->count() - $limit).' more representatives outside the requested limit. ' : '')
                    .'Pass a representative name for their full category breakdown.';
            }

            return $this->agentJson($payload);
        } catch (Throwable $e) {
            report($e);

            return $this->agentError($e->getMessage() ?: 'Unexpected error while summarizing expenses.');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from_date' => $schema->string()->description('Period start (inclusive), Y-m-d. If user gave no dates, omit and the previous calendar month is used.'),
            'to_date' => $schema->string()->description('Period end (inclusive), Y-m-d.'),
            'representative' => $schema->string()->description('Optional representative name, employee id or user id. When omitted all reps are ranked.'),
            'category' => $schema->string()->description('Optional expense category name filter, e.g. "Rickshaw Fare".'),
            'status' => $schema->string()->enum(['posted', 'draft', 'cancelled', 'all'])->description('Expense status filter, default posted.'),
            'limit' => $schema->integer()->description('Max rows in per-rep ranking, default 5.'),
        ];
    }
}
