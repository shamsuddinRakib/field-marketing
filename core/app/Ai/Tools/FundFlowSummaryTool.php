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

class FundFlowSummaryTool implements Tool
{
    use FormatsAgentOutput;
    use ResolvesTimeframe;
    use ResolvesUser;

    public function description(): Stringable|string
    {
        return 'Summarizes fund flow in a period: fund requests per rep grouped by status (pending/approved/rejected), distributions delivered vs pending per rep, and global ledger IN/OUT totals. '
            .'Answers "how much did we disburse this month" and "who requested funds". '
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
            $scoped = $rep['type'] === 'match';

            $requests = DB::table('fund_requests as fr')
                ->join('users as u', 'u.id', '=', 'fr.user_id')
                ->whereBetween('fr.created_at', [$frame['from'], $frame['to']])
                ->when($scoped, fn ($q) => $q->where('fr.user_id', $rep['user_id']))
                ->groupBy('fr.user_id', 'fr.status')
                ->select('fr.user_id', 'fr.status', DB::raw($this->repNameExpression('u', 'fr.user_id')), DB::raw('COUNT(*) as cnt'), DB::raw('SUM(fr.amount) as amount'))
                ->orderBy('fr.user_id')
                ->get();

            $distributions = DB::table('fund_distributions as fd')
                ->join('users as u', 'u.id', '=', 'fd.user_id')
                ->whereBetween('fd.created_at', [$frame['from'], $frame['to']])
                ->when($scoped, fn ($q) => $q->where('fd.user_id', $rep['user_id']))
                ->groupBy('fd.user_id')
                ->select(
                    'fd.user_id',
                    DB::raw($this->repNameExpression('u', 'fd.user_id')),
                    DB::raw("SUM(CASE WHEN fd.status = 'delivered' THEN 1 ELSE 0 END) as delivered_count"),
                    DB::raw("SUM(CASE WHEN fd.status = 'delivered' THEN fd.amount ELSE 0 END) as delivered_amount"),
                    DB::raw("SUM(CASE WHEN fd.status = 'pending' THEN 1 ELSE 0 END) as pending_count"),
                    DB::raw("SUM(CASE WHEN fd.status = 'pending' THEN fd.amount ELSE 0 END) as pending_amount")
                )
                ->orderByDesc('delivered_amount')
                ->get();

            $ledger = DB::table('fund_transfer_ledgers as ftl')
                ->groupBy('ftl.ref_type')
                ->select(
                    'ftl.ref_type',
                    DB::raw("COALESCE(SUM(CASE WHEN ftl.direction = 'IN' THEN ftl.amount ELSE 0 END), 0) as credited"),
                    DB::raw("COALESCE(SUM(CASE WHEN ftl.direction = 'OUT' THEN ftl.amount ELSE 0 END), 0) as debited")
                )
                ->orderByDesc('debited')
                ->get();

            if ($requests->isEmpty() && $distributions->isEmpty() && $ledger->isEmpty()) {
                $scope = $scoped ? " for {$rep['name']}" : '';

                return $this->agentEmpty("No fund activity found {$scope} in period {$period['from']} to {$period['to']}.", $period);
            }

            $reqByRep = $requests->groupBy('user_id');

            return $this->agentJson([
                'ok' => true,
                'period_used' => $period,
                'scope' => $scoped ? $rep['name'] : 'all representatives',
                'currency' => 'Tk',
                'fund_requests' => [
                    'per_rep' => $reqByRep->map(function ($rows, $userId) {
                        $byStatus = [];
                        $totalRequested = 0;

                        foreach ($rows as $row) {
                            $byStatus[(string) $row->status] = [
                                'count' => (int) $row->cnt,
                                'amount' => $this->agentNumber($row->amount),
                            ];
                            $totalRequested += (float) $row->amount;
                        }

                        return [
                            'user_id' => (int) $userId,
                            'rep_name' => (string) $rows->first()->rep_name,
                            'status_breakdown' => $byStatus,
                            'total_requested_amount' => $this->agentNumber($totalRequested),
                        ];
                    })->values()->all(),
                    'null_note' => $requests->isEmpty() ? 'No fund requests in the period.' : null,
                ],
                'fund_distributions' => $distributions->values()->map(fn ($d) => [
                    'user_id' => (int) $d->user_id,
                    'rep_name' => (string) $d->rep_name,
                    'delivered' => [
                        'count' => (int) $d->delivered_count,
                        'amount' => $this->agentNumber($d->delivered_amount),
                    ],
                    'pending' => [
                        'count' => (int) $d->pending_count,
                        'amount' => $this->agentNumber($d->pending_amount),
                    ],
                ])->all(),
                'ledger_totals' => $ledger->values()->map(fn ($l) => [
                    'ref_type' => (string) $l->ref_type,
                    'credit_in' => $this->agentNumber($l->credited),
                    'debit_out' => $this->agentNumber($l->debited),
                ])->all(),
                'note' => 'Ledger totals are global (not per rep) because fund_transfer_ledgers has no per-rep column.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->agentError($e->getMessage() ?: 'Unexpected error while summarizing fund flow.');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'from_date' => $schema->string()->description('Period start (inclusive), Y-m-d. If user gave no dates, omit and the previous calendar month is used.'),
            'to_date' => $schema->string()->description('Period end (inclusive), Y-m-d.'),
            'representative' => $schema->string()->description('Optional representative name, employee id or user id to scope results to one rep.'),
        ];
    }
}
