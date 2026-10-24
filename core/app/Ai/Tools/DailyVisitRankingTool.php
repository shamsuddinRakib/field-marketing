<?php

namespace App\Ai\Tools;

use RuntimeException;
use Throwable;
use App\Ai\Concerns\FormatsAgentOutput;
use App\Ai\Concerns\ResolvesTimeframe;
use App\Ai\Concerns\ResolvesUser;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class DailyVisitRankingTool implements Tool
{
    use FormatsAgentOutput;
    use ResolvesTimeframe;
    use ResolvesUser;

    public function description(): Stringable|string
    {
        return 'Ranks marketing representatives by number of daily visits in a period. '
            .'Answers "top five reps by visits", "who made the most visits" and "how many visits did rep X make". '
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

            $query = DB::table('daily_visits as dv')
                ->join('users as u', 'u.id', '=', 'dv.user_id')
                ->whereBetween('dv.created_at', [$frame['from'], $frame['to']])
                ->when($status !== 'all', fn ($q) => $q->where('dv.status', $status))
                ->when($rep['type'] === 'match', fn ($q) => $q->where('dv.user_id', $rep['user_id']))
                ->groupBy('dv.user_id')
                ->select(
                    'dv.user_id',
                    DB::raw($this->repNameExpression('u', 'dv.user_id')),
                    DB::raw('COUNT(*) as total_visits'),
                    DB::raw('SUM(CASE WHEN dv.teacher_id IS NOT NULL THEN 1 ELSE 0 END) as teacher_visits'),
                    DB::raw('SUM(CASE WHEN dv.library_id IS NOT NULL THEN 1 ELSE 0 END) as library_visits'),
                    DB::raw('COUNT(DISTINCT dv.teacher_id) as distinct_teachers'),
                    DB::raw('COUNT(DISTINCT dv.library_id) as distinct_libraries')
                )
                ->orderByDesc('total_visits')
                ->orderBy('rep_name');

            $rows = $query->get();

            $period = $this->agentPeriod($frame);

            if ($rows->isEmpty()) {
                $scope = $rep['type'] === 'match' ? " for {$rep['name']}" : '';

                return $this->agentEmpty(
                    "No daily visits found {$scope} in period {$period['from']} to {$period['to']}".($status !== 'all' ? " with status '{$status}'" : '').'.',
                    $period
                );
            }

            if ($rep['type'] === 'match') {
                $row = $rows->first();

                return $this->agentJson([
                    'ok' => true,
                    'period_used' => $period,
                    'status_filter' => $status,
                    'representative' => [
                        'user_id' => (int) $row->user_id,
                        'name' => (string) $row->rep_name,
                        'total_visits' => (int) $row->total_visits,
                        'teacher_visits' => (int) $row->teacher_visits,
                        'library_visits' => (int) $row->library_visits,
                        'distinct_teachers' => (int) $row->distinct_teachers,
                        'distinct_libraries' => (int) $row->distinct_libraries,
                    ],
                ]);
            }

            $limit = $this->agentLimit($request);

            return $this->agentJson([
                'ok' => true,
                'period_used' => $period,
                'status_filter' => $status,
                'metric' => 'total_visits per representative, highest first',
                'ranking' => $rows->take($limit)->values()->map(function ($row, $index) {
                    return [
                        'rank' => $index + 1,
                        'user_id' => (int) $row->user_id,
                        'rep_name' => (string) $row->rep_name,
                        'total_visits' => (int) $row->total_visits,
                        'teacher_visits' => (int) $row->teacher_visits,
                        'library_visits' => (int) $row->library_visits,
                        'distinct_teachers' => (int) $row->distinct_teachers,
                        'distinct_libraries' => (int) $row->distinct_libraries,
                    ];
                })->all(),
                'note' => $rows->count() > $limit
                    ? ($rows->count() - $limit).' more representatives outside the requested limit.'
                    : null,
            ]);
        } catch (RuntimeException $e) {
            return $this->agentError($e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return $this->agentError('Unexpected error while building the daily visit ranking: '.$e->getMessage());
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from_date' => $schema->string()->description('Period start (inclusive), Y-m-d. If user gave no dates, omit and the previous calendar month is used.'),
            'to_date' => $schema->string()->description('Period end (inclusive), Y-m-d.'),
            'representative' => $schema->string()->description('Optional representative name, employee id or user id. When provided only that rep\'s stats are returned.'),
            'status' => $schema->string()->enum(['pending', 'approved', 'all'])->description('Visit status filter, default all.'),
            'limit' => $schema->integer()->description('Max rows in the ranking, default 5.'),
        ];
    }
}
