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

class FundStatusTool implements Tool
{
    use FormatsAgentOutput;
    use ResolvesUser;

    public function description(): Stringable|string
    {
        return 'Shows the CURRENT fund status: current credit balance per representative from current funds, plus pending fund requests and pending fund distributions. '
            .'Answers "current fund status" and "how much money does rep X have now". This is a present-state tool, no date range needed.';
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            $rep = $this->resolveRepresentative($request);

            if ($error = $this->representativeGuard($rep)) {
                return $error;
            }

            $scoped = $rep['type'] === 'match';
            $repName = $scoped ? $rep['name'] : null;

            $balances = DB::table('current_funds as cf')
                ->join('users as u', 'u.id', '=', 'cf.user_id')
                ->when($scoped, fn ($q) => $q->where('cf.user_id', $rep['user_id']))
                ->groupBy('cf.user_id')
                ->select('cf.user_id', DB::raw($this->repNameExpression('u', 'cf.user_id')), DB::raw('SUM(cf.balance) as balance'))
                ->orderByDesc('balance')
                ->orderBy('rep_name')
                ->get();

            if ($balances->isEmpty()) {
                $scope = $scoped ? " for {$repName}" : '';

                return $this->agentEmpty("No fund balances found {$scope}.");
            }

            $pendingRequests = DB::table('fund_requests as fr')
                ->when($scoped, fn ($q) => $q->where('fr.user_id', $rep['user_id']))
                ->where('fr.status', 'pending')
                ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(fr.amount), 0) as amount')
                ->first();

            $pendingDistributions = DB::table('fund_distributions as fd')
                ->when($scoped, fn ($q) => $q->where('fd.user_id', $rep['user_id']))
                ->where('fd.status', 'pending')
                ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(fd.amount), 0) as amount')
                ->first();

            $totalBalance = $this->agentNumber($balances->sum('balance'));

            $payload = [
                'ok' => true,
                'as_of' => now()->format('Y-m-d H:i'),
                'currency' => 'Tk',
                'total_balance' => $totalBalance,
                'balances' => $balances->values()->map(fn ($b) => [
                    'user_id' => (int) $b->user_id,
                    'rep_name' => (string) $b->rep_name,
                    'balance' => $this->agentNumber($b->balance),
                ])->all(),
                'pending_fund_requests' => [
                    'count' => (int) $pendingRequests->cnt,
                    'amount' => $this->agentNumber($pendingRequests->amount),
                ],
                'pending_fund_distributions' => [
                    'count' => (int) $pendingDistributions->cnt,
                    'amount' => $this->agentNumber($pendingDistributions->amount),
                ],
            ];

            if (!$scoped && count($payload['balances']) > 20) {
                $payload['note'] = 'Showing all '.count($payload['balances']).' balances; balances highest first.';
            }

            return $this->agentJson($payload);
        } catch (Throwable $e) {
            report($e);

            return $this->agentError($e->getMessage() ?: 'Unexpected error while fetching fund status.');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'representative' => $schema->string()->description('Optional representative name, employee id or user id. When omitted balances for all reps are returned.'),
        ];
    }
}
