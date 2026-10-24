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

class MostVisitedInstitutionsTool implements Tool
{
    use FormatsAgentOutput;
    use ResolvesTimeframe;
    use ResolvesUser;

    public function description(): Stringable|string
    {
        return 'Ranks the most-visited teachers and libraries (daily visits) in a period, optionally per representative, or lists institutions with zero visits when unvisited_only is true. '
            .'Answers "which teachers does rep X visit most" and "which libraries are untouched". '
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

            $type = strtolower(trim((string) ($request['type'] ?? 'both')));

            if (! in_array($type, ['teachers', 'libraries', 'both'], true)) {
                throw new RuntimeException("Invalid type '{$type}'. Allowed values: teachers, libraries, both.");
            }

            $unvisitedOnly = $this->agentBool($request, 'unvisited_only');
            $limit = $this->agentLimit($request);
            $period = $this->agentPeriod($frame);

            if ($unvisitedOnly) {
                return $this->unvisitedOnly($request, $frame, $rep, $type, $limit, $period);
            }

            $rows = collect();

            if ($type === 'teachers' || $type === 'both') {
                $rows = $rows->merge($this->institutionRows($request, $frame, $rep, 'teacher'));
            }

            if ($type === 'libraries' || $type === 'both') {
                $rows = $rows->merge($this->institutionRows($request, $frame, $rep, 'library'));
            }

            $rows = $rows->sortByDesc('visit_count')->values();

            if ($rows->isEmpty()) {
                $scope = $rep['type'] === 'match' ? " by {$rep['name']}" : '';

                return $this->agentEmpty(
                    "No teacher or library visits found {$scope} in period {$period['from']} to {$period['to']}.",
                    $period
                );
            }

            return $this->agentJson([
                'ok' => true,
                'period_used' => $period,
                'institutions' => $rows->take($limit)->values()->map(function ($row, $index) {
                    return array_merge(['rank' => $index + 1], $row);
                })->all(),
                'note' => $rows->count() > $limit
                    ? ($rows->count() - $limit).' more institutions outside the requested limit.'
                    : null,
            ]);
        } catch (RuntimeException $e) {
            return $this->agentError($e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return $this->agentError('Unexpected error while ranking visited institutions: '.$e->getMessage());
        }
    }

    protected function institutionRows(Request $request, array $frame, array $rep, string $kind): \Illuminate\Support\Collection
    {
        $isTeacher = $kind === 'teacher';
        $kindTable = $isTeacher ? 'teachers' : 'libraries';

        $query = DB::table('daily_visits as dv')
            ->join("{$kindTable} as t", 't.id', '=', $isTeacher ? 'dv.teacher_id' : 'dv.library_id')
            ->whereBetween('dv.created_at', [$frame['from'], $frame['to']])
            ->when($rep['type'] === 'match', fn ($q) => $q->where('dv.user_id', $rep['user_id']))
            ->groupBy('t.id')
            ->select(
                DB::raw("t.id as {$kind}_id"),
                DB::raw($isTeacher ? '`t`.`teacher_name` as name' : '`t`.`library_name` as name'),
                DB::raw('COUNT(*) as visit_count'),
                DB::raw('COUNT(DISTINCT dv.user_id) as distinct_reps'),
                DB::raw('MAX(dv.created_at) as last_visit_at')
            )
            ->orderByDesc('visit_count')
            ->limit(100);

        return $query->get()->map(function ($row) use ($kind) {
            return [
                'type' => $kind,
                'name' => (string) $row->name,
                "{$kind}_id" => (int) $row->{"{$kind}_id"},
                'visit_count' => (int) $row->visit_count,
                'distinct_reps' => (int) $row->distinct_reps,
                'last_visit_at' => (string) $row->last_visit_at,
            ];
        });
    }

    protected function unvisitedOnly(Request $request, array $frame, array $rep, string $type, int $limit, array $period): string
    {
        $visited = ['teacher_ids' => collect(), 'library_ids' => collect()];

        if ($type === 'teachers' || $type === 'both') {
            $visited['teacher_ids'] = DB::table('daily_visits as dv')
                ->whereBetween('dv.created_at', [$frame['from'], $frame['to']])
                ->whereNotNull('dv.teacher_id')
                ->when($rep['type'] === 'match', fn ($q) => $q->where('dv.user_id', $rep['user_id']))
                ->pluck('dv.teacher_id');
        }

        if ($type === 'libraries' || $type === 'both') {
            $visited['library_ids'] = DB::table('daily_visits as dv')
                ->whereBetween('dv.created_at', [$frame['from'], $frame['to']])
                ->whereNotNull('dv.library_id')
                ->when($rep['type'] === 'match', fn ($q) => $q->where('dv.user_id', $rep['user_id']))
                ->pluck('dv.library_id');
        }

        $rows = collect();

        if ($type === 'teachers' || $type === 'both') {
            $rows = $rows->merge(
                DB::table('teachers as t')
                    ->leftJoin('institutions as ins', 'ins.id', '=', 't.institution_id')
                    ->whereNull('t.deleted_at')
                    ->whereNotIn('t.id', $visited['teacher_ids']->all() ?: [0])
                    ->select(DB::raw("'teacher' as type"), DB::raw('t.teacher_name as name'), 'ins.institution_name as context')
                    ->limit($limit)
                    ->get()
            );
        }

        if ($type === 'libraries' || $type === 'both') {
            $rows = $rows->merge(
                DB::table('libraries as l')
                    ->leftJoin('districts as d', 'd.district_id', '=', 'l.district_id')
                    ->whereNull('l.deleted_at')
                    ->whereNotIn('l.id', $visited['library_ids']->all() ?: [0])
                    ->select(DB::raw("'library' as type"), DB::raw('l.library_name as name'), 'd.district_name as context')
                    ->limit($limit)
                    ->get()
            );
        }

        if ($rows->isEmpty()) {
            return $this->agentEmpty(
                "All institutions of the requested type were visited in period {$period['from']} to {$period['to']}.",
                $period
            );
        }

        return $this->agentJson([
            'ok' => true,
            'period_used' => $period,
            'unvisited_institutions' => $rows->take($limit)->values()->all(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from_date' => $schema->string()->description('Period start (inclusive), Y-m-d. If user gave no dates, omit and the previous calendar month is used.'),
            'to_date' => $schema->string()->description('Period end (inclusive), Y-m-d.'),
            'representative' => $schema->string()->description('Optional representative name, employee id or user id to scope results to one rep.'),
            'type' => $schema->string()->enum(['teachers', 'libraries', 'both'])->description('Which institution type, default both.'),
            'unvisited_only' => $schema->string()->enum(['true', 'false'])->description('When "true", lists institutions with zero visits in the period instead of rankings.'),
            'limit' => $schema->integer()->description('Max rows, default 5.'),
        ];
    }
}
