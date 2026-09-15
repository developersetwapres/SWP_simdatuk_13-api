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
function phaseSeventeenTables(): array
{
    return [
        'users', 'roles', 'permissions', 'role_permissions', 'positions', 'grades',
        'user_leaves', 'user_notes', 'user_credits', 'user_assessments', 'user_competencies', 'user_talents',
    ];
}

function phaseSeventeenConnection(): Connection
{
    return DB::connection('mysql');
}

function phaseSeventeenPrefix(): string
{
    return 'codex_phase17_'.bin2hex(random_bytes(6));
}

/** @param array{create?: bool, read?: bool, update?: bool, delete?: bool} $flags */
function phaseSeventeenActingAs(array $flags): User
{
    $connection = phaseSeventeenConnection();
    $prefix = phaseSeventeenPrefix();
    $roleId = $connection->table('roles')->insertGetIdTs(['name' => $prefix.' Role']);
    $permission = $connection->table('permissions')->where('name', 'Catatan')->firstOrFail(['id']);
    $connection->table('role_permissions')->insertTs([
        'role_id' => $roleId,
        'permission_id' => $permission->id,
        'create' => $flags['create'] ?? false,
        'read' => $flags['read'] ?? false,
        'update' => $flags['update'] ?? false,
        'delete' => $flags['delete'] ?? false,
    ]);
    $userId = $connection->table('users')->insertGetIdTs([
        'role_id' => $roleId,
        'email' => $prefix.'@example.invalid',
        'username' => $prefix,
        'password' => Hash::make('Password@123'),
        'name' => 'Phase 17 Note Giver',
        'status' => true,
    ]);
    $user = User::query()->findOrFail($userId);
    Sanctum::actingAs($user);

    return $user;
}

function phaseSeventeenFixtureUser(): int
{
    $connection = phaseSeventeenConnection();
    $prefix = phaseSeventeenPrefix();

    return $connection->table('users')->insertGetIdTs([
        'email' => $prefix.'@example.invalid',
        'username' => $prefix,
        'password' => Hash::make('Password@123'),
        'name' => 'Phase 17 Employee',
        'status' => true,
        'grade_id' => $connection->table('grades')->value('id'),
        'position_id' => $connection->table('positions')->where('entity', 1)->value('id'),
    ]);
}

it('registers only the active Note API and keeps other ancillary and Employee detail routes absent', function () {
    $routes = collect(Route::getRoutes()->getRoutes());
    $noteRoutes = $routes->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/notes'))->map(fn ($route): array => [
        $route->methods()[0], $route->uri(), $route->getActionName(), $route->middleware(),
    ])->values()->all();

    expect($noteRoutes)->toBe([
        ['GET', 'api/notes/{userid}', 'App\\Http\\Controllers\\NoteController@show', ['api', 'auth:sanctum', 'role.access']],
        ['POST', 'api/notes/{userid}', 'App\\Http\\Controllers\\NoteController@update', ['api', 'auth:sanctum', 'role.access']],
    ]);
    foreach (['leaves', 'credits', 'assessments', 'competencies', 'talents'] as $path) {
        expect($routes->contains(fn ($route): bool => str_starts_with($route->uri(), 'api/'.$path)))->toBeFalse();
    }
    expect($routes->contains(fn ($route): bool => $route->uri() === 'api/employees/{id}'))->toBeFalse();
});

it('keeps the Phase 5 Note and Credit repositories source-equivalent', function () {
    $note = file_get_contents(app_path('Repositories/NoteRepository.php'));
    $credit = file_get_contents(app_path('Repositories/CreditRepository.php'));

    expect($note)->toContain("leftJoin('users as u', 'un.giver_id', '=', 'u.id')")
        ->toContain("DATE_FORMAT(un.created_at, '%d-%m-%Y')")
        ->toContain("orderBy('un.created_at', 'desc')")
        ->and($credit)->toContain("join('users as u', 'uc.user_id', '=', 'u.id')")
        ->toContain("orderBy('year', 'desc')")
        ->toContain("orderBy('period', 'desc')");
});

it('has every unconditional Employee show repository dependency while keeping activation separate', function () {
    $dependencies = [
        'employee' => EmployeeRepository::class,
        'educations' => EducationRepository::class,
        'families' => FamilyRepository::class,
        'positions' => PositionRepository::class,
        'grades' => GradeRepository::class,
        'structurals/functionals/technicals' => TrainingRepository::class,
        'recognitions' => RecognitionRepository::class,
        'targets' => TargetRepository::class,
        'performances' => PerformanceRepository::class,
        'disciplinaries' => DisciplinaryRepository::class,
        'leaves' => LeaveRepository::class,
        'notes' => NoteRepository::class,
        'credits' => CreditRepository::class,
        'assessments' => AssessmentRepository::class,
        'competencies' => CompetencyRepository::class,
        'talents' => TalentRepository::class,
    ];

    expect(array_keys($dependencies))->toBe([
        'employee', 'educations', 'families', 'positions', 'grades', 'structurals/functionals/technicals',
        'recognitions', 'targets', 'performances', 'disciplinaries', 'leaves', 'notes', 'credits',
        'assessments', 'competencies', 'talents',
    ]);
    foreach ($dependencies as $repository) {
        expect(class_exists($repository))->toBeTrue()
            ->and(method_exists($repository, 'getDetail'))->toBeTrue();
    }
});

describe('Phase 17 ancillary histories on the guarded MySQL clone', function () {
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
        $connection = phaseSeventeenConnection();
        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $database) {
            throw new RuntimeException('Refusing MySQL access: clone connection guard failed.');
        }
        $engines = $connection->table('information_schema.tables')->where('table_schema', $database)
            ->whereIn('table_name', phaseSeventeenTables())->pluck('ENGINE')->map(fn (?string $engine): string => strtoupper((string) $engine));
        if ($engines->count() !== count(phaseSeventeenTables()) || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
            throw new RuntimeException('Refusing MySQL access: every Phase 17 table must exist and use InnoDB.');
        }
        $connection->beginTransaction();
    });

    afterEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            return;
        }
        $connection = phaseSeventeenConnection();
        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
        DB::disconnect('mysql');
    });

    it('confirms actual table population, FK map, and document population', function () {
        $connection = phaseSeventeenConnection();
        expect($connection->table('user_leaves')->count())->toBe(75)
            ->and($connection->table('user_notes')->count())->toBe(1)
            ->and($connection->table('user_credits')->count())->toBe(0)
            ->and($connection->table('user_assessments')->count())->toBe(1)
            ->and($connection->table('user_competencies')->count())->toBe(1)
            ->and($connection->table('user_talents')->count())->toBe(0);

        $foreignKeyTables = $connection->table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', 'SWP_simdatuk_test13')
            ->whereIn('TABLE_NAME', ['user_leaves', 'user_notes', 'user_credits', 'user_assessments', 'user_competencies', 'user_talents'])
            ->whereNotNull('REFERENCED_TABLE_NAME')->pluck('TABLE_NAME')->countBy();
        expect($foreignKeyTables->get('user_leaves'))->toBe(1)
            ->and($foreignKeyTables->get('user_notes'))->toBe(2)
            ->and($foreignKeyTables->get('user_credits'))->toBe(1)
            ->and($foreignKeyTables->get('user_assessments'))->toBe(1)
            ->and($foreignKeyTables->get('user_competencies'))->toBe(1)
            ->and($foreignKeyTables->get('user_talents'))->toBe(1);

        expect($connection->table('user_leaves')->whereNotNull('letter')->count()
            + $connection->table('user_assessments')->whereNotNull('assessment_document')->count()
            + $connection->table('user_competencies')->whereNotNull('competency_document')->count()
            + $connection->table('user_talents')->whereNotNull('talent_document')->count())->toBe(0);
    });

    it('returns Leave projection, current Grade, recursive Position text, date order, and document URL', function () {
        $connection = phaseSeventeenConnection();
        $userId = $connection->table('user_leaves as ul')->join('users as u', 'u.id', '=', 'ul.user_id')
            ->whereNotNull('u.position_id')->select('ul.user_id')->groupBy('ul.user_id')->orderByRaw('COUNT(*) DESC')->value('ul.user_id');
        $items = app(LeaveRepository::class)->getDetail($userId);
        expect($items)->toHaveCount($connection->table('user_leaves')->where('user_id', $userId)->count())
            ->and(array_keys((array) $items->first()))->toBe(['id', 'grade', 'position_id', 'start_date', 'end_date', 'type', 'number', 'description', 'letter', 'position_merged'])
            ->and($items->every(fn (object $item): bool => str_starts_with($item->letter, config('app.url')) && is_string($item->position_merged)))->toBeTrue();
        $dates = $items->pluck('start_date')->filter()->all();
        $sorted = $dates;
        rsort($sorted);
        expect(array_values($dates))->toBe(array_values($sorted));
    });

    it('reuses NoteRepository with exact real MySQL projection and ordering', function () {
        $connection = phaseSeventeenConnection();
        $row = $connection->table('user_notes')->firstOrFail();
        $items = app(NoteRepository::class)->getDetail($row->user_id);
        expect($items)->toHaveCount($connection->table('user_notes')->where('user_id', $row->user_id)->count())
            ->and(array_keys((array) $items->first()))->toBe(['id', 'description', 'giver_name', 'created_at'])
            ->and($items->first()->created_at)->toMatch('/^\d{2}-\d{2}-\d{4}$/');
    });

    it('reuses CreditRepository against an isolated clone fixture because the source table is empty', function () {
        $connection = phaseSeventeenConnection();
        $userId = phaseSeventeenFixtureUser();
        $firstId = $connection->table('user_credits')->insertGetIdTs([
            'user_id' => $userId, 'position' => 'First', 'period' => 1, 'year' => '2025', 'score' => 10.5, 'start_month' => 1, 'end_month' => 6,
        ]);
        $secondId = $connection->table('user_credits')->insertGetIdTs([
            'user_id' => $userId, 'position' => 'Second', 'period' => 2, 'year' => '2026', 'score' => 20.5, 'start_month' => 7, 'end_month' => 12,
        ]);
        $items = app(CreditRepository::class)->getDetail($userId);
        expect($items->pluck('id')->map(fn ($id): int => (int) $id)->all())->toBe([$secondId, $firstId])
            ->and(array_keys((array) $items->first()))->toBe(['id', 'position', 'period', 'year', 'score', 'start_month', 'end_month']);
    });

    it('returns exact Assessment and Competency projections from real clone rows', function (string $table, string $repositoryClass, string $document) {
        $connection = phaseSeventeenConnection();
        $row = $connection->table($table)->firstOrFail();
        $items = app($repositoryClass)->getDetail($row->user_id);
        $eventDate = $items->first()->event_date;
        expect(array_keys((array) $items->first()))->toBe(['id', 'event_date', 'point', 'organizer', $document])
            ->and($eventDate === null || preg_match('/^\d{2}-\d{2}-\d{4}$/', $eventDate) === 1)->toBeTrue()
            ->and($items->first()->{$document})->toStartWith(config('app.url'));
    })->with([
        'Assessment' => ['user_assessments', AssessmentRepository::class, 'assessment_document'],
        'Competency' => ['user_competencies', CompetencyRepository::class, 'competency_document'],
    ]);

    it('returns exact Talent projection from an isolated fixture because the source table is empty', function () {
        $connection = phaseSeventeenConnection();
        $userId = phaseSeventeenFixtureUser();
        $id = $connection->table('user_talents')->insertGetIdTs([
            'user_id' => $userId, 'event_date' => '2026-09-15', 'point' => 9,
            'organizer' => 'Phase 17', 'talent_document' => null,
        ]);
        $items = app(TalentRepository::class)->getDetail($userId);
        expect((int) $items->first()->id)->toBe($id)
            ->and(array_keys((array) $items->first()))->toBe(['id', 'event_date', 'point', 'organizer', 'talent_document'])
            ->and($items->first()->event_date)->toBe('15-09-2026')
            ->and($items->first()->talent_document)->toStartWith(config('app.url'));
    });

    it('returns empty Collections for every ancillary repository when data is absent', function () {
        $userId = phaseSeventeenFixtureUser();
        foreach ([
            new LeaveRepository, new NoteRepository, new CreditRepository,
            new AssessmentRepository, new CompetencyRepository, new TalentRepository,
        ] as $repository) {
            expect($repository->getDetail($userId))->toBeEmpty();
        }
    });

    it('shows Notes with raw timestamps and nonexistent Employees as empty success', function () {
        $connection = phaseSeventeenConnection();
        phaseSeventeenActingAs(['read' => true]);
        $row = $connection->table('user_notes')->firstOrFail();
        $this->getJson('/api/notes/'.$row->user_id)->assertOk()->assertJsonPath('message', 'success')
            ->assertJsonStructure(['data' => [['id', 'giver_id', 'giver_name', 'description', 'created_at']]]);
        $missing = ((int) $connection->table('users')->max('id')) + 1_000_000;
        $this->getJson('/api/notes/'.$missing)->assertOk()->assertJsonPath('message', 'success')->assertJsonCount(0, 'data');
    });

    it('creates, updates, deletes, and overwrites Note giver/user IDs without a transaction', function () {
        $connection = phaseSeventeenConnection();
        $giver = phaseSeventeenActingAs(['create' => true, 'read' => true]);
        $targetId = phaseSeventeenFixtureUser();
        $otherTargetId = phaseSeventeenFixtureUser();
        $foreignNoteId = $connection->table('user_notes')->insertGetIdTs([
            'user_id' => $otherTargetId, 'giver_id' => $giver->id, 'description' => 'foreign isolated note',
        ]);

        $this->postJson('/api/notes/'.$targetId, ['notes' => [[
            'id' => $foreignNoteId, 'description' => 'reparented by source behavior',
        ], [
            'id' => null, 'description' => 'new note',
        ]]])->assertOk()->assertJsonPath('message', 'Catatan berhasil diupdate.');

        $reparented = $connection->table('user_notes')->where('id', $foreignNoteId)->first();
        expect((int) $reparented->user_id)->toBe($targetId)
            ->and((int) $reparented->giver_id)->toBe($giver->id)
            ->and($connection->table('user_notes')->where('user_id', $targetId)->count())->toBe(2);

        collect(Route::getRoutes()->getRoutes())
            ->first(fn ($route): bool => $route->uri() === 'api/notes/{userid}' && in_array('POST', $route->methods(), true))
            ->flushController();
        $this->postJson('/api/notes/'.$targetId, [])->assertOk()->assertJsonPath('message', 'Catatan berhasil diupdate.');
        expect($connection->table('user_notes')->where('user_id', $targetId)->count())->toBe(0);
    });

    it('preserves Note validation and POST authorization uses create permission', function () {
        $targetId = phaseSeventeenFixtureUser();
        phaseSeventeenActingAs(['read' => true, 'create' => false, 'update' => true]);
        $this->getJson('/api/notes/'.$targetId)->assertOk();
        $this->postJson('/api/notes/'.$targetId, ['notes' => [['id' => 'bad']]])->assertForbidden()
            ->assertJsonPath('message', "Access denied. You don't have permission to create Catatan.");
    });

    it('returns the exact Note ID validation message when create permission is present', function () {
        $targetId = phaseSeventeenFixtureUser();
        phaseSeventeenActingAs(['create' => true]);
        $response = $this->postJson('/api/notes/'.$targetId, ['notes' => [['id' => 'bad']]])->assertUnprocessable();
        expect($response->json('data')['notes.0.id'][0])->toBe('Id harus berupa angka.');
    });

    it('leaves no Phase 17 fixture residue after explicit rollback', function () {
        $connection = phaseSeventeenConnection();
        $prefix = phaseSeventeenPrefix();
        $userId = $connection->table('users')->insertGetIdTs([
            'email' => $prefix.'@example.invalid', 'username' => $prefix, 'password' => 'x', 'name' => $prefix, 'status' => true,
        ]);
        $connection->table('user_credits')->insertGetIdTs(['user_id' => $userId]);
        $connection->table('user_talents')->insertGetIdTs(['user_id' => $userId]);
        $connection->rollBack();
        expect($connection->table('users')->where('username', $prefix)->exists())->toBeFalse()
            ->and($connection->table('user_credits')->where('user_id', $userId)->exists())->toBeFalse()
            ->and($connection->table('user_talents')->where('user_id', $userId)->exists())->toBeFalse();
        $connection->beginTransaction();
    });
});
