<?php

use App\Repositories\EducationRepository;
use App\Repositories\FamilyRepository;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

function phaseFourteenConnection(): Connection
{
    return DB::connection('mysql');
}

it('does not invent Education or Family routes', function () {
    $uris = collect(Route::getRoutes()->getRoutes())->pluck('uri');

    expect($uris->contains(fn (string $uri): bool => str_contains($uri, 'education')))->toBeFalse()
        ->and($uris->contains(fn (string $uri): bool => str_contains($uri, 'famil')))->toBeFalse();
});

describe('Education and Family repositories on the guarded MySQL clone', function () {
    beforeEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            $this->markTestSkipped('MySQL clone verification is opt-in.');
        }

        $cloneDatabase = (string) env('SIMDATUK_CLONE_DATABASE');

        if ($cloneDatabase !== 'SWP_simdatuk_test13') {
            throw new RuntimeException('Refusing MySQL access: expected the authorized SWP_simdatuk_test13 clone.');
        }

        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', $cloneDatabase);
        DB::purge('mysql');
        $connection = phaseFourteenConnection();

        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $cloneDatabase) {
            throw new RuntimeException('Refusing MySQL access: clone connection guard failed.');
        }

        $engines = $connection->table('information_schema.tables')
            ->where('table_schema', $cloneDatabase)
            ->whereIn('table_name', ['users', 'user_educations', 'user_families'])
            ->pluck('ENGINE')
            ->map(fn (?string $engine): string => strtoupper((string) $engine));

        if ($engines->count() !== 3 || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
            throw new RuntimeException('Refusing MySQL access: every Phase 14 table must exist and use InnoDB.');
        }
    });

    afterEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') === '1') {
            DB::disconnect('mysql');
        }
    });

    it('returns the exact Education projection in level and graduation-year order', function () {
        $connection = phaseFourteenConnection();
        $userId = $connection->table('user_educations')
            ->select('user_id')
            ->groupBy('user_id')
            ->orderByRaw('COUNT(*) DESC')
            ->value('user_id');
        $educations = app(EducationRepository::class)->getDetail($userId);

        expect($educations)->toHaveCount($connection->table('user_educations')->where('user_id', $userId)->count())
            ->and(array_keys((array) $educations->first()))->toBe([
                'id', 'level', 'name', 'study_area', 'accreditation', 'faculty', 'major',
                'year_of_graduation', 'description', 'degree_document',
                'study_assignment_letter', 'academic_title_letter',
            ])
            ->and($educations->every(fn (object $education): bool => str_starts_with($education->degree_document, config('app.url'))
                && str_starts_with($education->study_assignment_letter, config('app.url'))
                && str_starts_with($education->academic_title_letter, config('app.url'))))->toBeTrue();

        $keys = $educations->map(fn (object $education): array => [
            $education->level === null ? PHP_INT_MIN : -(int) $education->level,
            $education->year_of_graduation === null ? PHP_INT_MIN : -(int) $education->year_of_graduation,
        ])->all();
        $sorted = $keys;
        usort($sorted, fn (array $left, array $right): int => $left <=> $right);
        expect($keys)->toBe($sorted);
    });

    it('formats a real Education document path and preserves null-document fallback', function () {
        $connection = phaseFourteenConnection();
        $stored = $connection->table('user_educations')->whereNotNull('degree_document')->firstOrFail(['user_id', 'degree_document']);
        $storedResult = app(EducationRepository::class)->getDetail($stored->user_id)->first(fn (object $education): bool => str_contains($education->degree_document, '/api/image/'));
        $nullUser = $connection->table('user_educations')->whereNull('degree_document')->value('user_id');
        $nullResult = app(EducationRepository::class)->getDetail($nullUser)->first();

        expect($storedResult->degree_document)->toBe(url('/api/image/'.ltrim($stored->degree_document, '/')))
            ->and($nullResult->degree_document)->toBe(asset('img/profile.jpg'));
    });

    it('returns the exact Family projection and MySQL FIELD relationship ordering', function () {
        $connection = phaseFourteenConnection();
        $userId = $connection->table('user_families')
            ->select('user_id')
            ->groupBy('user_id')
            ->orderByRaw('COUNT(*) DESC')
            ->value('user_id');
        $families = app(FamilyRepository::class)->getDetail($userId);

        expect($families)->toHaveCount($connection->table('user_families')->where('user_id', $userId)->count())
            ->and(array_keys((array) $families->first()))->toBe([
                'id', 'card_number', 'name', 'id_number', 'gender', 'religion', 'date_of_birth',
                'place_of_birth', 'name_of_father', 'name_of_mother', 'relationship_status', 'education',
                'occupation', 'occupation_description', 'marital_status', 'marriage_other_notes',
                'mobile_phone', 'sequence_number',
            ]);

        $order = [2 => 1, 3 => 2, 4 => 3, 7 => 4, 8 => 5, 1 => 6, 5 => 7, 6 => 8, 9 => 9, 10 => 10, 11 => 11];
        $ranks = $families->map(fn (object $family): int => $order[$family->relationship_status] ?? 0)->all();
        $sorted = $ranks;
        sort($sorted);
        expect($ranks)->toBe($sorted);
    });

    it('formats real Family birth dates and preserves null dates', function () {
        $connection = phaseFourteenConnection();
        $dated = $connection->table('user_families')->whereNotNull('date_of_birth')->firstOrFail(['user_id', 'date_of_birth']);
        $nullUser = $connection->table('user_families')->whereNull('date_of_birth')->value('user_id');

        $datedResult = app(FamilyRepository::class)->getDetail($dated->user_id)->first(fn (object $family): bool => $family->date_of_birth !== null);
        $nullResult = app(FamilyRepository::class)->getDetail($nullUser)->first(fn (object $family): bool => $family->date_of_birth === null);

        expect($datedResult->date_of_birth)->toBe(date('d-m-Y', strtotime($dated->date_of_birth)))
            ->and($nullResult->date_of_birth)->toBeNull();
    });

    it('returns Employee-detail-compatible empty Collections for missing personal histories', function () {
        $connection = phaseFourteenConnection();
        $withoutEducation = $connection->table('users as u')
            ->leftJoin('user_educations as ue', 'u.id', '=', 'ue.user_id')
            ->whereNull('ue.id')
            ->value('u.id');
        $withoutFamily = $connection->table('users as u')
            ->leftJoin('user_families as uf', 'u.id', '=', 'uf.user_id')
            ->whereNull('uf.id')
            ->value('u.id');

        expect(app(EducationRepository::class)->getDetail($withoutEducation))->toBeEmpty()
            ->and(app(FamilyRepository::class)->getDetail($withoutFamily))->toBeEmpty();
    });

    it('confirms both personal-history tables have valid cascading Employee references', function () {
        $connection = phaseFourteenConnection();

        foreach (['user_educations', 'user_families'] as $table) {
            $orphans = $connection->table("{$table} as history")
                ->leftJoin('users as u', 'history.user_id', '=', 'u.id')
                ->whereNull('u.id')
                ->count();
            $deleteRule = $connection->table('information_schema.referential_constraints')
                ->where('constraint_schema', $connection->getDatabaseName())
                ->where('table_name', $table)
                ->value('delete_rule');

            expect($orphans)->toBe(0)
                ->and(strtoupper((string) $deleteRule))->toBe('CASCADE');
        }
    });
});
