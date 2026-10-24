<?php

namespace App\Ai\Tools;

use Throwable;
use App\Ai\Concerns\FormatsAgentOutput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListRepresentativesTool implements Tool
{
    use FormatsAgentOutput;

    public function description(): Stringable|string
    {
        return 'Lists all marketing representatives with rep id, user_id, name, employee id, mobile, email, branch, district, division, upazila and status. '
            .'Use this first to resolve representative names before running other data tools, or when the user asks who the representatives are.';
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            $search = trim((string) ($request['search'] ?? ''));
            $limit = $this->agentLimit($request, 30, 50);

            $query = DB::table('marketing_representatives as mr')
                ->leftJoin('users as u', 'u.id', '=', 'mr.user_id')
                ->leftJoin('branches as b', 'b.id', '=', 'mr.branch_id')
                ->leftJoin('districts as d', 'd.district_id', '=', 'mr.district_id')
                ->leftJoin('divisions as dp', 'dp.id', '=', 'mr.division_id')
                ->leftJoin('upazilas as up', 'up.id', '=', 'mr.upazila_id')
                ->whereNull('mr.deleted_at')
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($w) use ($search) {
                        $w->where('u.name', 'like', "%{$search}%")
                            ->orWhere('mr.employee_id', 'like', "%{$search}%")
                            ->orWhere('mr.mobile', 'like', "%{$search}%");
                    });
                })
                ->orderByRaw("COALESCE(NULLIF(u.name, ''), mr.employee_id) IS NULL ASC")
                ->orderByRaw("COALESCE(NULLIF(u.name, ''), mr.employee_id) ASC")
                ->select(
                    'mr.id as rep_id',
                    'mr.user_id',
                    'mr.employee_id',
                    'mr.mobile',
                    DB::raw("COALESCE(u.email, mr.email) as email"),
                    DB::raw("COALESCE(NULLIF(u.name, ''), mr.employee_id) as name"),
                    DB::raw('COALESCE(b.name, NULL) AS branch'),
                    'd.district_name as district',
                    'dp.name as division',
                    'up.upazila_name as upazila',
                    'mr.status'
                )
                ->limit($limit);

            $rows = $query->get();

            if ($rows->isEmpty()) {
                return $this->agentEmpty($search === ''
                    ? 'No representatives exist yet.'
                    : "No representative matched search '{$search}'.");
            }

            return $this->agentJson([
                'ok' => true,
                'count' => $rows->count(),
                'representatives' => $rows->map(fn ($row) => [
                    'rep_id' => (int) $row->rep_id,
                    'user_id' => (int) $row->user_id,
                    'name' => (string) $row->name,
                    'employee_id' => (string) $row->employee_id,
                    'mobile' => $row->mobile ? (string) $row->mobile : null,
                    'email' => $row->email ? (string) $row->email : null,
                    'branch' => $row->branch ? (string) $row->branch : null,
                    'district' => $row->district ? (string) $row->district : null,
                    'division' => $row->division ? (string) $row->division : null,
                    'upazila' => $row->upazila ? (string) $row->upazila : null,
                    'status' => (int) $row->status === 1 ? 'active' : 'inactive',
                ])->all(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->agentError($e->getMessage() ?: 'Unexpected error while listing representatives.');
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Optional filter: part of a name, employee id or mobile.'),
        ];
    }
}
