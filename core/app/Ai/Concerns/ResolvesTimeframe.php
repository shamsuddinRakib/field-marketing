<?php

namespace App\Ai\Concerns;

use Illuminate\Support\Carbon;
use Laravel\Ai\Tools\Request;

trait ResolvesTimeframe
{
    use FormatsAgentOutput;

    protected function resolveTimeframe(Request $request): array|string
    {
        $fromRaw = trim((string) ($request['from_date'] ?? ''));
        $toRaw = trim((string) ($request['to_date'] ?? ''));

        if ($fromRaw === '' && $toRaw === '') {
            $from = now()->subMonthNoOverflow()->startOfMonth()->startOfDay();
            $to = now()->subMonthNoOverflow()->endOfMonth()->endOfDay();

            return [
                'from' => $from,
                'to' => $to,
                'is_default' => true,
                'note' => "Default period applied: previous calendar month ({$from->toDateString()} to {$to->toDateString()}). Pass from_date/to_date to query a different range, and always tell the user which period was used.",
            ];
        }

        if ($fromRaw === '') {
            $fromRaw = $toRaw;
        }

        if ($toRaw === '') {
            $toRaw = $fromRaw;
        }

        $from = $this->parseAgentDate($fromRaw);

        if ($from === null) {
            return $this->agentError("Invalid from_date '{$fromRaw}'. Expected format Y-m-d, example: 2026-09-01.");
        }

        $to = $this->parseAgentDate($toRaw);

        if ($to === null) {
            return $this->agentError("Invalid to_date '{$toRaw}'. Expected format Y-m-d, example: 2026-09-30.");
        }

        $from = $from->startOfDay();
        $to = $to->endOfDay();

        if ($from->greaterThan($to)) {
            return $this->agentError(
                'from_date ('.$from->toDateString().') must be before or equal to to_date ('.$to->toDateString().').'
            );
        }

        return [
            'from' => $from,
            'to' => $to,
            'is_default' => false,
            'note' => 'Explicit period: '.$from->toDateString().' to '.$to->toDateString().'.',
        ];
    }

    protected function parseAgentDate(string $raw): ?Carbon
    {
        try {
            $date = Carbon::createFromFormat('Y-m-d', $raw);
        } catch (\Throwable) {
            return null;
        }

        if ($date === false) {
            return null;
        }

        if ($date->format('Y-m-d') !== $raw) {
            return null;
        }

        return $date;
    }

    protected function resolveExpenseStatus(Request $request): string
    {
        return $this->agentStatus($request, ['posted', 'draft', 'cancelled', 'all'], 'posted');
    }
}
