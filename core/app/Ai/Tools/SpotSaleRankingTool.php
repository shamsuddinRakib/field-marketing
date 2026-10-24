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

class SpotSaleRankingTool implements Tool
{
    use FormatsAgentOutput;
    use ResolvesTimeframe;
    use ResolvesUser;

    public function description(): Stringable|string
    {
        return 'Ranks marketing representatives by spot sale amount (and sale count) in a period. '
            .'Answers "which representative made the most spot sales" and rep-specific sales totals. '
            .'By default pending and approved sales are both counted; when the user gives no date range it defaults to the previous calendar month. Always tell the user the period used.';
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

            $rows = DB::table('spot_sales as ss')
                ->join('users as u', 'u.id', '=', 'ss.user_id')
                ->whereBetween('ss.created_at', [$frame['from'], $frame['to']])
                ->when($status !== 'all', fn ($q) => $q->where('ss.status', $status))
                ->when($rep['type'] === 'match', fn ($q) => $q->where('ss.user_id', $rep['user_id']))
                ->groupBy('ss.user_id')
                ->select(
                    'ss.user_id',
                    DB::raw($this->repNameExpression('u', 'ss.user_id')),
                    DB::raw('COUNT(*) as sales_count'),
                    DB::raw('SUM(ss.total) as total_amount'),
                    DB::raw('AVG(ss.total) as avg_sale_value')
                )
                ->orderByDesc('total_amount')
                ->orderBy('rep_name')
                ->get();

            if ($rows->isEmpty()) {
                $scope = $rep['type'] === 'match' ? " for {$rep['name']}" : '';

                return $this->agentEmpty(
                    "No spot sales found {$scope} in period {$period['from']} to {$period['to']}".($status !== 'all' ? " with status '{$status}'" : '').'.',
                    $period
                );
            }

            $mapRow = fn ($row, $index = null) => array_merge(
                $index === null ? [] : ['rank' => $index + 1],
                [
                    'user_id' => (int) $row->user_id,
                    'rep_name' => (string) $row->rep_name,
                    'sales_count' => (int) $row->sales_count,
                    'total_amount' => $this->agentNumber($row->total_amount),
                    'avg_sale_value' => $this->agentNumber($row->avg_sale_value),
                    'currency' => 'Tk',
                ]
            );

            if ($rep['type'] === 'match') {
                return $this->agentJson([
                    'ok' => true,
                    'period_used' => $period,
                    'status_filter' => $status,
                    'representative' => $mapRow($rows->first()),
                ]);
            }

            $limit = $this->agentLimit($request);

            return $this->agentJson([
                'ok' => true,
                'period_used' => $period,
                'status_filter' => $status,
                'metric' => 'total spot sale amount per representative, highest first',
                'ranking' => $rows->take($limit)->values()->map($mapRow)->all(),
                'note' => $rows->count() > $limit
                    ? ($rows->count() - $limit).' more representatives outside the requested limit.'
                    : null,
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->agentError($e->getMessage() ?: 'Unexpected error while building the spot sale ranking.');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from_date' => $schema->string()->description('Period start (inclusive), Y-m-d. If user gave no dates, omit and the previous calendar month is used.'),
            'to_date' => $schema->string()->description('Period end (inclusive), Y-m-d.'),
            'representative' => $schema->string()->description('Optional representative name, employee id or user id. When provided only that rep\'s totals are returned.'),
            'status' => $schema->string()->enum(['pending', 'approved', 'all'])->description('Sale status filter, default all.'),
            'limit' => $schema->integer()->description('Max rows in the ranking, default 5.'),
        ];
    }
}
