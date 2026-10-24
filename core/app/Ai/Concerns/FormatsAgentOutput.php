<?php

namespace App\Ai\Concerns;

use Laravel\Ai\Tools\Request;

trait FormatsAgentOutput
{
    protected function agentJson(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false ? 'Output could not be encoded.' : $json;
    }

    protected function agentError(string $message): string
    {
        return $this->agentJson(['ok' => false, 'error' => $message]);
    }

    protected function agentEmpty(string $message, ?array $period = null): string
    {
        $payload = ['ok' => true, 'message' => $message];

        if ($period !== null) {
            $payload['period_used'] = $period;
        }

        return $this->agentJson($payload);
    }

    protected function agentPeriod(array $timeframe): array
    {
        return [
            'from' => $timeframe['from']->toDateString(),
            'to' => $timeframe['to']->toDateString(),
            'note' => $timeframe['note'],
        ];
    }

    protected function agentLimit(Request $request, int $default = 5, int $max = 50): int
    {
        $limit = (int) ($request['limit'] ?? 0);

        if ($limit <= 0) {
            return $default;
        }

        return min($limit, $max);
    }

    protected function agentBool(Request $request, string $key, bool $default = false): bool
    {
        $value = $request[$key] ?? null;

        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (filter_var($value, FILTER_VALIDATE_BOOL) === false) {
            throw new \RuntimeException("Parameter '{$key}' must be a boolean (true or false), got '{$value}'.");
        }

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    protected function agentStatus(Request $request, array $allowed, string $default = 'all'): string
    {
        $status = strtolower(trim((string) ($request['status'] ?? '')));

        if ($status === '') {
            return $default;
        }

        if (in_array($status, $allowed, true)) {
            return $status;
        }

        throw new \RuntimeException(
            'Invalid status \''.($request['status'] ?? '').'\'. Allowed values: '.implode(', ', $allowed).'.'
        );
    }

    protected function agentNumber(mixed $value, int $precision = 2): float|int|null
    {
        if ($value === null) {
            return null;
        }

        return round((float) $value, $precision);
    }

    protected function agentInt(mixed $value): int
    {
        return (int) $value;
    }
}
