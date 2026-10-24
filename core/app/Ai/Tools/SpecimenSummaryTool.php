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

class SpecimenSummaryTool implements Tool
{
    use FormatsAgentOutput;
    use ResolvesTimeframe;
    use ResolvesUser;

    public function description(): Stringable|string
    {
        return 'Summarizes specimen assignments to teachers and libraries in a period per representative: document counts, total quantity, distinct teachers/libraries covered and top assigned products. '
            .'Answers "how many specimens has rep X assigned", "where do we distribute most specimens". '
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

            $scope = strtolower(trim((string) ($request['scope'] ?? '')));

            if ($scope === '') {
                $scope = 'both';
            }

            if (! in_array($scope, ['teacher', 'library', 'both'], true)) {
                throw new RuntimeException("Invalid scope '{$scope}'. Allowed values: teacher, library, both.");
            }

            $period = $this->agentPeriod($frame);

            $rows = DB::table('assign_specimens as asp')
                ->join('users as u', 'u.id', '=', 'asp.user_id')
                ->whereBetween('asp.created_at', [$frame['from'], $frame['to']])
                ->when($rep['type'] === 'match', fn ($q) => $q->where('asp.user_id', $rep['user_id']))
                ->when($scope === 'teacher', fn ($q) => $q->whereNotNull('asp.teacher_id'))
                ->when($scope === 'library', fn ($q) => $q->whereNotNull('asp.library_id'))
                ->groupBy('asp.user_id')
                ->select(
                    'asp.user_id',
                    DB::raw($this->repNameExpression('u', 'asp.user_id')),
                    DB::raw('COUNT(DISTINCT asp.id) as doc_count'),
                    DB::raw('COUNT(DISTINCT CASE WHEN asp.teacher_id IS NOT NULL THEN asp.teacher_id END) as distinct_teachers'),
                    DB::raw('COUNT(DISTINCT CASE WHEN asp.library_id IS NOT NULL THEN asp.library_id END) as distinct_libraries')
                )
                ->orderByDesc('doc_count')
                ->orderBy('rep_name')
                ->get();

            if ($rows->isEmpty()) {
                $scopeText = $rep['type'] === 'match' ? " by {$rep['name']}" : '';

                return $this->agentEmpty(
                    "No specimen assignments found {$scopeText} in period {$period['from']} to {$period['to']}.",
                    $period
                );
            }

            $itemRows = DB::table('assign_specimen_items as asi')
                ->join('assign_specimens as asp', 'asp.id', '=', 'asi.assign_specimen_id')
                ->leftJoin('products as p', 'p.id', '=', 'asi.product_id')
                ->whereBetween('asp.created_at', [$frame['from'], $frame['to']])
                ->when($rep['type'] === 'match', fn ($q) => $q->where('asp.user_id', $rep['user_id']))
                ->when($scope === 'teacher', fn ($q) => $q->whereNotNull('asp.teacher_id'))
                ->when($scope === 'library', fn ($q) => $q->whereNotNull('asp.library_id'))
                ->groupBy('asp.user_id', 'asi.product_id')
                ->select(
                    'asp.user_id',
                    'asi.product_id',
                    DB::raw("COALESCE(NULLIF(p.name, ''), CONCAT('Product #', asi.product_id)) as product_name"),
                    DB::raw('SUM(asi.quantity) as qty')
                )
                ->orderByDesc('qty')
                ->get()
                ->groupBy(fn ($r) => (int) $r->user_id);

            $docTypeRows = DB::table('assign_specimens as asp')
                ->whereBetween('asp.created_at', [$frame['from'], $frame['to']])
                ->when($rep['type'] === 'match', fn ($q) => $q->where('asp.user_id', $rep['user_id']))
                ->when($scope === 'teacher', fn ($q) => $q->whereNotNull('asp.teacher_id'))
                ->when($scope === 'library', fn ($q) => $q->whereNotNull('asp.library_id'))
                ->groupBy('asp.user_id')
                ->select(
                    'asp.user_id',
                    DB::raw("SUM(CASE WHEN asp.teacher_id IS NOT NULL THEN 1 ELSE 0 END) as teacher_docs"),
                    DB::raw("SUM(CASE WHEN asp.library_id IS NOT NULL THEN 1 ELSE 0 END) as library_docs")
                )
                ->get()
                ->keyBy(fn ($r) => (int) $r->user_id);

            $limit = $this->agentLimit($request);
            $topPerRep = $rep['type'] === 'match' ? 10 : 3;

            return $this->agentJson([
                'ok' => true,
                'period_used' => $period,
                'scope' => $scope,
                'note' => 'quantities are units, not money',
                'summaries' => $rows->take($limit)->values()->map(function ($row) use ($itemRows, $docTypeRows, $topPerRep) {
                    $itemAgg = $itemRows->get((int) $row->user_id, collect());

                    return [
                        'user_id' => (int) $row->user_id,
                        'rep_name' => (string) $row->rep_name,
                        'doc_count' => (int) $row->doc_count,
                        'teacher_docs' => (int) ($docTypeRows->get((int) $row->user_id)?->teacher_docs ?? 0),
                        'library_docs' => (int) ($docTypeRows->get((int) $row->user_id)?->library_docs ?? 0),
                        'distinct_teachers_covered' => (int) $row->distinct_teachers,
                        'distinct_libraries_covered' => (int) $row->distinct_libraries,
                        'top_assigned_products' => $itemAgg->take($topPerRep)->values()->map(fn ($t) => [
                            'product_name' => (string) $t->product_name,
                            'qty' => (int) $t->qty,
                        ])->all(),
                    ];
                })->all(),
                'list_note' => $rows->count() > $limit
                    ? ($rows->count() - $limit).' more representatives outside the requested limit.'
                    : null,
            ]);
        } catch (RuntimeException $e) {
            return $this->agentError($e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return $this->agentError('Unexpected error while summarizing specimens: '.$e->getMessage());
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from_date' => $schema->string()->description('Period start (inclusive), Y-m-d. If user gave no dates, omit and the previous calendar month is used.'),
            'to_date' => $schema->string()->description('Period end (inclusive), Y-m-d.'),
            'representative' => $schema->string()->description('Optional representative name, employee id or user id to scope results to one rep.'),
            'scope' => $schema->string()->enum(['teacher', 'library', 'both'])->description('Which assignment target, default both.'),
            'limit' => $schema->integer()->description('Max reps in the summary, default 5.'),
        ];
    }
}
