<?php

namespace App\Ai\Concerns;

use Illuminate\Support\Facades\DB;
use Laravel\Ai\Tools\Request;

trait ResolvesUser
{
    use FormatsAgentOutput;

    protected function representativeBaseQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('marketing_representatives as mr')
            ->join('users as u', 'u.id', '=', 'mr.user_id')
            ->whereNull('mr.deleted_at')
            ->select(
                'mr.id as mr_id',
                'mr.user_id',
                'mr.employee_id',
                DB::raw("COALESCE(NULLIF(u.name, ''), NULLIF(mr.employee_id, ''), CONCAT('User #', mr.user_id)) as name")
            );
    }

    protected function resolveRepresentative(Request $request, string $key = 'representative'): array
    {
        $value = trim((string) ($request[$key] ?? ''));

        if ($value === '') {
            return ['type' => 'none'];
        }

        $rows = $this->representativeBaseQuery()->get();

        $needle = mb_strtolower($value);

        $matches = $rows->filter(function ($row) use ($needle, $value) {
            if (ctype_digit($value) && (int) $row->user_id === (int) $value) {
                return true;
            }

            $name = mb_strtolower(trim((string) $row->name));
            $employeeId = mb_strtolower(trim((string) $row->employee_id));

            return $employeeId === $needle
                || $name === $needle
                || str_contains($name, $needle)
                || str_contains($employeeId, $needle);
        });

        if ($matches->isEmpty()) {
            return [
                'type' => 'not_found',
                'message' => "No representative matched '{$value}'. Call list_representatives first to see valid names.",
            ];
        }

        $users = $matches->groupBy('user_id');

        if ($users->count() > 1) {
            return [
                'type' => 'ambiguous',
                'message' => "Multiple representatives matched '{$value}'. Ask the user to pick one. Matches:",
                'candidates' => $users->map(function ($group) {
                    return [
                        'user_id' => (int) $group->first()->user_id,
                        'name' => $group->first()->name,
                        'employee_ids' => $group->pluck('employee_id')->unique()->values()->all(),
                    ];
                })->values()->all(),
            ];
        }

        $group = $users->first();
        $first = $group->first();

        return [
            'type' => 'match',
            'user_id' => (int) $first->user_id,
            'name' => (string) $first->name,
            'mr_ids' => $group->pluck('mr_id')->map(fn ($id) => (int) $id)->unique()->values()->all(),
        ];
    }

    protected function representativeGuard(array $resolved): ?string
    {
        if ($resolved['type'] === 'not_found') {
            return $this->agentError($resolved['message']);
        }

        if ($resolved['type'] === 'ambiguous') {
            return $this->agentJson([
                'ok' => false,
                'error' => $resolved['message'],
                'candidates' => $resolved['candidates'],
            ]);
        }

        return null;
    }

    protected function repNameExpression(string $userAlias, string $idExpr): string
    {
        return "COALESCE(NULLIF(MAX({$userAlias}.name), ''), CONCAT('User #', {$idExpr})) as rep_name";
    }

    protected function repNameRaw(string $userAlias, string $idExpr): string
    {
        return "COALESCE(NULLIF({$userAlias}.name, ''), CONCAT('User #', {$idExpr}))";
    }
}
