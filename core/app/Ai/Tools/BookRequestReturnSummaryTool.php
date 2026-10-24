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

class BookRequestReturnSummaryTool implements Tool
{
    use FormatsAgentOutput;
    use ResolvesTimeframe;
    use ResolvesUser;

    public function description(): Stringable|string
    {
        return 'Summarizes book requests and book returns in a period per representative: requested quantity by status (pending/approved/rejected), issued vs returned quantity, return rate and pending returns. '
            .'Answers "how many books did rep X request and return", "which rep has pending returns". '
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

            $requests = DB::table('book_requests as br')
                ->join('marketing_representatives as mr', 'mr.id', '=', 'br.marketing_representative_id')
                ->join('users as u', 'u.id', '=', 'mr.user_id')
                ->whereNull('mr.deleted_at')
                ->whereBetween('br.request_date', [$frame['from']->toDateString(), $frame['to']->toDateString()])
                ->when($scoped, fn ($q) => $q->whereIn('br.marketing_representative_id', $rep['mr_ids']))
                ->groupBy('mr.user_id')
                ->select(
                    'mr.user_id',
                    DB::raw($this->repNameExpression('u', 'mr.user_id')),
                    DB::raw('COUNT(*) as request_count'),
                    DB::raw('SUM(br.quantity) as total_qty'),
                    DB::raw("SUM(CASE WHEN br.status = 'pending' THEN br.quantity ELSE 0 END) as pending_qty"),
                    DB::raw("SUM(CASE WHEN br.status = 'approved' THEN br.quantity ELSE 0 END) as approved_qty"),
                    DB::raw("SUM(CASE WHEN br.status = 'rejected' THEN br.quantity ELSE 0 END) as rejected_qty")
                )
                ->orderByDesc('total_qty')
                ->orderBy('rep_name')
                ->get();

            $returns = DB::table('book_returns as bret')
                ->join('marketing_representatives as mr', 'mr.id', '=', 'bret.marketing_representative_id')
                ->join('users as u', 'u.id', '=', 'mr.user_id')
                ->whereNull('mr.deleted_at')
                ->whereRaw(
                    "COALESCE(bret.received_date, DATE(bret.created_at)) BETWEEN ? AND ?",
                    [$frame['from']->toDateString(), $frame['to']->toDateString()]
                )
                ->when($scoped, fn ($q) => $q->whereIn('bret.marketing_representative_id', $rep['mr_ids']))
                ->groupBy('mr.user_id')
                ->select(
                    'mr.user_id',
                    DB::raw($this->repNameExpression('u', 'mr.user_id')),
                    DB::raw('COUNT(*) as return_docs'),
                    DB::raw('SUM(bret.issued_quantity) as issued_qty'),
                    DB::raw('SUM(bret.returned_quantity) as returned_qty'),
                    DB::raw("SUM(CASE WHEN bret.status = 'pending' THEN bret.issued_quantity - bret.returned_quantity ELSE 0 END) as pending_qty")
                )
                ->orderByDesc('returned_qty')
                ->orderBy('rep_name')
                ->get();

            $receivedBy = DB::table('book_returns as bret')
                ->whereRaw(
                    "COALESCE(bret.received_date, DATE(bret.created_at)) BETWEEN ? AND ?",
                    [$frame['from']->toDateString(), $frame['to']->toDateString()]
                )
                ->when($scoped, fn ($q) => $q->whereIn('bret.marketing_representative_id', $rep['mr_ids']))
                ->groupBy('bret.received_by')
                ->select(
                    'bret.received_by',
                    DB::raw('SUM(bret.issued_quantity) as issued_qty'),
                    DB::raw('SUM(bret.returned_quantity) as returned_qty')
                )
                ->orderByDesc('returned_qty')
                ->get();

            if ($requests->isEmpty() && $returns->isEmpty()) {
                $scope = $scoped ? " for {$rep['name']}" : '';

                return $this->agentEmpty(
                    "No book requests or returns found {$scope} in period {$period['from']} to {$period['to']}.",
                    $period
                );
            }

            return $this->agentJson([
                'ok' => true,
                'period_used' => $period,
                'book_requests' => $requests->isEmpty() ? null : $requests->values()->map(fn ($r) => [
                    'user_id' => (int) $r->user_id,
                    'rep_name' => (string) $r->rep_name,
                    'request_count' => (int) $r->request_count,
                    'total_qty' => (int) $r->total_qty,
                    'pending_qty' => (int) $r->pending_qty,
                    'approved_qty' => (int) $r->approved_qty,
                    'rejected_qty' => (int) $r->rejected_qty,
                ])->all(),
                'book_returns' => $returns->isEmpty() ? null : $returns->values()->map(function ($r) {
                    $returnRate = (int) $r->issued_qty > 0
                        ? $this->agentNumber($r->returned_qty / $r->issued_qty * 100)
                        : null;

                    return [
                        'user_id' => (int) $r->user_id,
                        'rep_name' => (string) $r->rep_name,
                        'return_docs' => (int) $r->return_docs,
                        'issued_qty' => (int) $r->issued_qty,
                        'returned_qty' => (int) $r->returned_qty,
                        'return_rate_percent' => $returnRate,
                        'pending_return_qty' => (int) $r->pending_qty,
                    ];
                })->all(),
                'received_by_summary' => $receivedBy->isEmpty() ? null : $receivedBy->values()->map(fn ($r) => [
                    'received_by' => (string) $r->received_by,
                    'issued_qty' => (int) $r->issued_qty,
                    'returned_qty' => (int) $r->returned_qty,
                ])->all(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->agentError($e->getMessage() ?: 'Unexpected error while summarizing book requests and returns.');
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
