<?php

namespace App\Ai\Tools;

use RuntimeException;
use Throwable;
use App\Ai\Concerns\FormatsAgentOutput;
use App\Ai\Concerns\ResolvesUser;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class TeacherLibraryStatsTool implements Tool
{
    use FormatsAgentOutput;
    use ResolvesUser;

    public function description(): Stringable|string
    {
        return 'Shows all-time counts of teachers and libraries (active vs inactive), grouped by district and institution, and how many were ever visited or ever given a specimen. '
            .'Answers "how many teachers do we have", "how many teachers in Dhaka", "which libraries were never assigned specimens", "coverage of rep X". '
            .'This is an all-time tool, no date range needed.';
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            $rep = $this->resolveRepresentative($request);

            if ($error = $this->representativeGuard($rep)) {
                return $error;
            }

            $type = strtolower(trim((string) ($request['type'] ?? '')));

            if ($type === '') {
                $type = 'both';
            }

            if (! in_array($type, ['teachers', 'libraries', 'both'], true)) {
                throw new RuntimeException("Invalid type '{$type}'. Allowed values: teachers, libraries, both.");
            }

            $district = trim((string) ($request['district'] ?? ''));
            $division = trim((string) ($request['division'] ?? ''));

            $repUserId = $rep['type'] === 'match' ? $rep['user_id'] : null;

            $specimenTeacherIds = DB::table('assign_specimens')
                ->whereNotNull('teacher_id')
                ->when($repUserId !== null, fn ($q) => $q->where('user_id', $repUserId))
                ->distinct()
                ->pluck('teacher_id')
                ->map(fn ($id) => (int) $id);

            $specimenLibraryIds = DB::table('assign_specimens')
                ->whereNotNull('library_id')
                ->when($repUserId !== null, fn ($q) => $q->where('user_id', $repUserId))
                ->distinct()
                ->pluck('library_id')
                ->map(fn ($id) => (int) $id);

            $visitedTeacherIds = DB::table('daily_visits')
                ->whereNotNull('teacher_id')
                ->when($repUserId !== null, fn ($q) => $q->where('user_id', $repUserId))
                ->distinct()
                ->pluck('teacher_id')
                ->map(fn ($id) => (int) $id);

            $visitedLibraryIds = DB::table('daily_visits')
                ->whereNotNull('library_id')
                ->when($repUserId !== null, fn ($q) => $q->where('user_id', $repUserId))
                ->distinct()
                ->pluck('library_id')
                ->map(fn ($id) => (int) $id);

            $payload = [
                'ok' => true,
                'as_of' => now()->format('Y-m-d'),
                'coverage_scope' => $repUserId !== null
                    ? "institutions contacted by {$rep['name']} at least once"
                    : 'institutions contacted by any representative at least once',
            ];

            if ($type === 'teachers' || $type === 'both') {
                $payload['teachers'] = $this->statsForTeachers($district, $division, $specimenTeacherIds, $visitedTeacherIds);
            }

            if ($type === 'libraries' || $type === 'both') {
                $payload['libraries'] = $this->statsForLibraries($district, $division, $specimenLibraryIds, $visitedLibraryIds);
            }

            return $this->agentJson($payload);
        } catch (RuntimeException $e) {
            return $this->agentError($e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return $this->agentError('Unexpected error while fetching institution stats: '.$e->getMessage());
        }
    }

    protected function statsForTeachers(string $district, string $division, $specimenIds, $visitedIds): array
    {
        $rows = DB::table('teachers as t')
            ->leftJoin('institutions as ins', 'ins.id', '=', 't.institution_id')
            ->leftJoin('districts as d', 'd.district_id', '=', 'ins.district_id')
            ->leftJoin('divisions as dp', 'dp.id', '=', 'd.district_division_id')
            ->whereNull('t.deleted_at')
            ->when($district !== '', fn ($q) => $q->where('d.district_name', 'like', "%{$district}%"))
            ->when($division !== '', fn ($q) => $q->where('dp.name', 'like', "%{$division}%"))
            ->select('t.id', 't.teacher_name as name', 't.status', DB::raw('ins.institution_name as institution'), DB::raw('d.district_name as district'))
            ->get();

        if ($rows->isEmpty()) {
            return ['note' => ($district !== '' || $division !== '')
                ? 'No teachers matched the given district/division filter.'
                : 'No teachers exist yet.'];
        }

        return $this->aggregateStats($rows, $specimenIds, $visitedIds, 'teacher', 'institution', 'district');
    }

    protected function statsForLibraries(string $district, string $division, $specimenIds, $visitedIds): array
    {
        $rows = DB::table('libraries as l')
            ->leftJoin('districts as d', 'd.district_id', '=', 'l.district_id')
            ->leftJoin('divisions as dp', 'dp.id', '=', 'd.district_division_id')
            ->whereNull('l.deleted_at')
            ->when($district !== '', fn ($q) => $q->where('d.district_name', 'like', "%{$district}%"))
            ->when($division !== '', fn ($q) => $q->where('dp.name', 'like', "%{$division}%"))
            ->select('l.id', 'l.library_name as name', 'l.status', DB::raw('d.district_name as group_context'), DB::raw('d.district_name as district'))
            ->get();

        if ($rows->isEmpty()) {
            return ['note' => ($district !== '' || $division !== '')
                ? 'No libraries matched the given district/division filter.'
                : 'No libraries exist yet.'];
        }

        return $this->aggregateStats($rows, $specimenIds, $visitedIds, 'library', 'district', 'district');
    }

    protected function aggregateStats($rows, $specimenIds, $visitedIds, string $kind, string $contextKey, string $districtKey): array
    {
        $total = $rows->count();
        $active = $rows->filter(fn ($r) => (int) $r->status === 1)->count();
        $withSpecimenCount = 0;
        $visitedCount = 0;

        $rows->each(function ($r) use ($specimenIds, $visitedIds, &$withSpecimenCount, &$visitedCount) {
            if ($specimenIds->contains((int) $r->id)) {
                $withSpecimenCount++;
            }

            if ($visitedIds->contains((int) $r->id)) {
                $visitedCount++;
            }
        });

        $groupRows = fn (string $key) => collect($rows)
            ->groupBy(fn ($r) => trim((string) ($r->{$key} ?? '')) !== '' ? (string) $r->{$key} : 'Unknown')
            ->map(fn ($group, $name) => ['name' => $name, 'count' => $group->count()])
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->all();

        return [
            'kind' => $kind,
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'given_specimen_ever' => $withSpecimenCount,
            'never_given_specimen' => $total - $withSpecimenCount,
            'ever_visited' => $visitedCount,
            'never_visited' => $total - $visitedCount,
            'top_by_context' => $groupRows($contextKey),
            'top_by_district' => $groupRows($districtKey),
            'note' => 'Counts are all-time. Pass a representative to scope coverage to that rep.',
        ];
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(['teachers', 'libraries', 'both'])->description('Which institution type, default both.'),
            'district' => $schema->string()->description('Optional district name filter, e.g. "Dhaka".'),
            'division' => $schema->string()->description('Optional division name filter.'),
            'representative' => $schema->string()->description('Optional representative name/employee id/user id: scope coverage (visit + specimen) to that rep.'),
        ];
    }
}
