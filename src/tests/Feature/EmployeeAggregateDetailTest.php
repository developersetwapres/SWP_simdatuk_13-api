<?php

use App\Models\User;
use App\Repositories\AssessmentRepository;
use App\Repositories\CompetencyRepository;
use App\Repositories\CreditRepository;
use App\Repositories\DisciplinaryRepository;
use App\Repositories\EducationRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\FamilyRepository;
use App\Repositories\GradeRepository;
use App\Repositories\LeaveRepository;
use App\Repositories\NoteRepository;
use App\Repositories\PerformanceRepository;
use App\Repositories\PositionRepository;
use App\Repositories\RecognitionRepository;
use App\Repositories\TalentRepository;
use App\Repositories\TargetRepository;
use App\Repositories\TrainingRepository;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/** @return list<string> */
function phaseEighteenTables(): array
{
    return [
        'users', 'roles', 'permissions', 'role_permissions',
        'positions', 'grades', 'echelons', 'residences', 'institutions',
        'user_educations', 'user_families',
        'position_histories', 'position_history_users', 'position_history_echelons', 'groups',
        'grade_histories', 'grade_history_users', 'decrees',
        'training_histories', 'training_history_users', 'training_levels',
        'recognition_histories', 'recognition_history_users', 'recognitions',
        'target_histories', 'target_history_users',
        'performance_histories', 'performance_history_users',
        'disciplinary_histories', 'disciplinary_history_users', 'disciplinaries',
        'user_leaves', 'user_notes', 'user_credits', 'user_assessments', 'user_competencies', 'user_talents',
    ];
}

function phaseEighteenConnection(): Connection
{
    return DB::connection('mysql');
}

function phaseEighteenPrefix(): string
{
    return 'codex_phase18_'.bin2hex(random_bytes(6));
}

function phaseEighteenActingAsExistingReader(): User
{
    $permissionNames = [
        'Data Pegawai - ASN',
        'Data Pegawai - Non ASN',
        'Data Pegawai - Outsourcing',
    ];
    $roleId = phaseEighteenConnection()->table('role_permissions as rp')
        ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
        ->whereIn('p.name', $permissionNames)
        ->where('rp.read', true)
        ->groupBy('rp.role_id')
        ->havingRaw('COUNT(DISTINCT p.name) = 3')
        ->value('rp.role_id');
    $user = User::query()->where('role_id', $roleId)->where('status', true)->orderBy('id')->firstOrFail();
    Sanctum::actingAs($user);

    return $user;
}

function phaseEighteenActingAsRestrictedReader(string $permissionName): User
{
    $connection = phaseEighteenConnection();
    $prefix = phaseEighteenPrefix();
    $roleId = $connection->table('roles')->insertGetIdTs(['name' => $prefix.' Role']);
    $permissionId = $connection->table('permissions')->where('name', $permissionName)->value('id');
    $connection->table('role_permissions')->insertTs([
        'role_id' => $roleId,
        'permission_id' => $permissionId,
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
    ]);
    $userId = $connection->table('users')->insertGetIdTs([
        'role_id' => $roleId,
        'email' => $prefix.'@example.invalid',
        'username' => $prefix,
        'password' => Hash::make('Password@123'),
        'name' => 'Phase 18 Restricted Reader',
        'status' => true,
    ]);
    $user = User::query()->findOrFail($userId);
    Sanctum::actingAs($user);

    return $user;
}

function phaseEighteenNormalize(mixed $value): mixed
{
    return json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
}

/** @return array<string, mixed> */
function phaseEighteenExpectedAggregate(int $userId, object $employee): array
{
    return [
        'position' => array_reverse((array) app(PositionRepository::class)->getRecursivePosition($employee->position_id)),
        'educations' => app(EducationRepository::class)->getDetail($userId),
        'families' => app(FamilyRepository::class)->getDetail($userId),
        'positions' => app(PositionRepository::class)->getDetail($userId),
        'grades' => app(GradeRepository::class)->getDetail($userId),
        'structurals' => app(TrainingRepository::class)->getDetail($userId, 1),
        'functionals' => app(TrainingRepository::class)->getDetail($userId, 2),
        'technicals' => app(TrainingRepository::class)->getDetail($userId, 3),
        'recognitions' => app(RecognitionRepository::class)->getDetail($userId),
        'targets' => app(TargetRepository::class)->getDetail($userId),
        'performances' => app(PerformanceRepository::class)->getDetail($userId),
        'disciplinaries' => app(DisciplinaryRepository::class)->getDetail($userId),
        'leaves' => app(LeaveRepository::class)->getDetail($userId),
        'notes' => app(NoteRepository::class)->getDetail($userId),
        'credits' => app(CreditRepository::class)->getDetail($userId),
        'assessments' => app(AssessmentRepository::class)->getDetail($userId),
        'competencies' => app(CompetencyRepository::class)->getDetail($userId),
        'talents' => app(TalentRepository::class)->getDetail($userId),
    ];
}

it('preserves Employee read routes in Laravel 10 relative order', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => in_array($route->uri(), ['api/employees', 'api/employees/{id}'], true) && in_array('GET', $route->methods(), true))
        ->map(fn ($route): array => [$route->methods()[0], $route->uri(), $route->getActionName(), $route->middleware()])
        ->values()
        ->all();

    expect($routes)->toBe([
        ['GET', 'api/employees', 'App\\Http\\Controllers\\EmployeeController@index', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/employees/{id}', 'App\\Http\\Controllers\\EmployeeController@show', ['api', 'auth:sanctum', 'role.access']],
    ]);
});

it('keeps the source repository call and aggregate assignment order', function () {
    $controller = file_get_contents(app_path('Http/Controllers/EmployeeController.php'));
    $needles = [
        '$this->employeeRepository->getDetail', '$this->educationRepository->getDetail',
        '$this->familyRepository->getDetail', '$this->positionRepository->getDetail',
        '$this->gradeRepository->getDetail', '$this->trainingRepository->getDetail($this->request->id, 1)',
        '$this->trainingRepository->getDetail($this->request->id, 2)', '$this->trainingRepository->getDetail($this->request->id, 3)',
        '$this->recognitionRepository->getDetail', '$this->targetRepository->getDetail',
        '$this->performanceRepository->getDetail', '$this->disciplinaryRepository->getDetail',
        '$this->leaveRepository->getDetail', '$this->noteRepository->getDetail',
        '$this->creditRepository->getDetail', '$this->assessmentRepository->getDetail',
        '$this->competencyRepository->getDetail', '$this->talentRepository->getDetail',
        '$this->positionRepository->getRecursivePosition',
    ];
    $offsets = array_map(fn (string $needle): int|false => strpos($controller, $needle), $needles);

    expect($offsets)->not->toContain(false)
        ->and($offsets)->toBe(collect($offsets)->sort()->values()->all());
});

describe('Employee aggregate detail on the guarded MySQL clone', function () {
    beforeEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            $this->markTestSkipped('MySQL clone verification is opt-in.');
        }
        $database = (string) env('SIMDATUK_CLONE_DATABASE');
        if ($database !== 'SWP_simdatuk_test13') {
            throw new RuntimeException('Refusing MySQL access: expected the authorized SWP_simdatuk_test13 clone.');
        }
        config()->set('app.debug', false);
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', $database);
        DB::purge('mysql');
        $connection = phaseEighteenConnection();
        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $database) {
            throw new RuntimeException('Refusing MySQL access: clone connection guard failed.');
        }
        $engines = $connection->table('information_schema.tables')->where('table_schema', $database)
            ->whereIn('table_name', phaseEighteenTables())->pluck('ENGINE')->map(fn (?string $engine): string => strtoupper((string) $engine));
        if ($engines->count() !== count(phaseEighteenTables()) || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
            throw new RuntimeException('Refusing MySQL access: every Phase 18 table must exist and use InnoDB.');
        }
        $connection->beginTransaction();
    });

    afterEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            return;
        }
        $connection = phaseEighteenConnection();
        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
        DB::disconnect('mysql');
    });

    it('assembles the rich ASN aggregate exactly from every direct repository output', function () {
        $userId = 382;
        phaseEighteenActingAsExistingReader();
        $employee = app(EmployeeRepository::class)->getDetail($userId);
        expect($employee)->not->toBeNull();
        $expected = phaseEighteenExpectedAggregate($userId, $employee);

        $response = $this->getJson("/api/employees/{$userId}?type=1");
        $response->assertOk()->assertJsonPath('code', 200)->assertJsonPath('message', 'success');
        $json = $response->json();
        expect(array_keys($json))->toBe(['code', 'message', 'data'])
            ->and((int) $json['data']['id'])->toBe($userId)
            ->and(array_slice(array_keys($json['data']), -18))->toBe(array_keys($expected));

        foreach ((array) $employee as $field => $value) {
            expect($json['data'][$field])->toBe(phaseEighteenNormalize($value));
        }
        foreach ($expected as $key => $value) {
            expect($json['data'][$key])->toBe(phaseEighteenNormalize($value));
        }
    });

    it('returns representative ASN, Non ASN, and Outsourcing aggregates with array histories', function (int $userId, int $type) {
        phaseEighteenActingAsExistingReader();
        $response = $this->getJson("/api/employees/{$userId}?type={$type}");
        $response->assertOk()->assertJsonPath('data.id', $userId)->assertJsonPath('data.type', $type);
        $data = $response->json('data');

        foreach ([
            'position', 'educations', 'families', 'positions', 'grades', 'structurals', 'functionals',
            'technicals', 'recognitions', 'targets', 'performances', 'disciplinaries', 'leaves', 'notes',
            'credits', 'assessments', 'competencies', 'talents',
        ] as $key) {
            expect($data[$key])->toBeArray();
        }
    })->with([
        'ASN with dense history' => [382, 1],
        'Non ASN' => [792, 2],
        'Outsourcing' => [1577, 3],
    ]);

    it('preserves sparse Employee null joins, empty histories, and null Position ancestry', function () {
        phaseEighteenActingAsExistingReader();
        $response = $this->getJson('/api/employees/2?type=1');
        $response->assertOk()->assertJsonPath('data.id', 2)->assertJsonPath('data.position_id', null)
            ->assertJsonPath('data.position', []);
        $data = $response->json('data');
        foreach (['educations', 'families', 'structurals', 'functionals', 'technicals', 'recognitions', 'targets', 'performances', 'disciplinaries', 'leaves', 'notes', 'credits', 'assessments', 'competencies', 'talents'] as $key) {
            expect($data[$key])->toBeArray();
        }
    });

    it('returns the exact 404 envelope and skips every downstream history query', function () {
        phaseEighteenActingAsExistingReader();
        $missingId = ((int) phaseEighteenConnection()->table('users')->max('id')) + 100000;
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->getJson("/api/employees/{$missingId}?type=1");
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $response->assertStatus(404)->assertExactJson([
            'code' => 404,
            'message' => 'Pegawai tidak ditemukan.',
            'data' => null,
        ]);
        expect($queries)->toHaveCount(2)
            ->and($queries->filter(fn (string $query): bool => str_contains($query, ' from `users` as `u`')))->toHaveCount(1)
            ->and($queries->contains(fn (string $query): bool => preg_match('/user_(educations|families|leaves|notes|credits|assessments|competencies|talents)/', $query) === 1))->toBeFalse();
    });

    it('selects authorization solely from requested type, including missing and invalid defaults', function (?string $requestedType, string $permission, int $userId) {
        phaseEighteenActingAsRestrictedReader($permission);
        $query = is_null($requestedType) ? '' : '?type='.$requestedType;

        $this->getJson("/api/employees/{$userId}{$query}")->assertOk();
    })->with([
        'type 1' => ['1', 'Data Pegawai - ASN', 382],
        'type 2' => ['2', 'Data Pegawai - Non ASN', 792],
        'type 3' => ['3', 'Data Pegawai - Outsourcing', 1577],
        'missing defaults to ASN' => [null, 'Data Pegawai - ASN', 382],
        'invalid defaults to ASN' => ['invalid', 'Data Pegawai - ASN', 382],
    ]);

    it('allows a type 2 permission to read a stored type 1 Employee when request type is 2', function () {
        phaseEighteenActingAsRestrictedReader('Data Pegawai - Non ASN');

        $this->getJson('/api/employees/382?type=2')
            ->assertOk()
            ->assertJsonPath('data.id', 382)
            ->assertJsonPath('data.type', 1);
    });

    it('denies the same stored Employee when the requested type permission is absent', function () {
        phaseEighteenActingAsRestrictedReader('Data Pegawai - Non ASN');

        $this->getJson('/api/employees/382?type=1')->assertStatus(403)->assertExactJson([
            'code' => 403,
            'message' => "Access denied. You don't have permission to read Data Pegawai - ASN.",
            'data' => null,
        ]);
    });

    it('characterizes aggregate query count and Leave ancestry repetition without optimizing it', function () {
        $connection = phaseEighteenConnection();
        $userId = 440;
        phaseEighteenActingAsExistingReader();
        $leaveCount = $connection->table('user_leaves')->where('user_id', $userId)->count();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $startedAt = hrtime(true);

        $this->getJson("/api/employees/{$userId}?type=1")->assertOk();

        $elapsedMilliseconds = (hrtime(true) - $startedAt) / 1_000_000;
        $queries = collect(DB::getQueryLog());
        DB::disableQueryLog();
        $recursiveQueries = $queries->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'with recursive hierarchy'));

        expect($queries->count())->toBe(21 + $leaveCount)
            ->and($recursiveQueries)->toHaveCount(2 + $leaveCount)
            ->and($elapsedMilliseconds)->toBeGreaterThan(0);
    });
});
