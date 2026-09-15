<?php

use App\Http\Requests\Employee\CreateEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Requests\Employee\UpdateStatusRequest;
use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/** @return list<string> */
function phaseNineteenTables(): array
{
    return [
        'users', 'roles', 'permissions', 'role_permissions', 'personal_access_tokens', 'user_device_sessions',
        'employment_types', 'institutions', 'positions', 'position_echelons', 'grades', 'echelons', 'residences',
        'user_educations', 'user_families', 'user_leaves', 'user_notes', 'user_credits',
        'user_assessments', 'user_competencies', 'user_talents',
        'position_histories', 'position_history_users', 'grade_histories', 'grade_history_users',
        'training_histories', 'training_history_users', 'recognition_histories', 'recognition_history_users',
        'target_histories', 'target_history_users', 'performance_histories', 'performance_history_users',
        'disciplinary_histories', 'disciplinary_history_users',
    ];
}

function phaseNineteenConnection(): Connection
{
    return DB::connection('mysql');
}

function phaseNineteenPrefix(): string
{
    return 'codex_phase19_'.bin2hex(random_bytes(6));
}

function phaseNineteenFlushEmployeeControllers(): void
{
    collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->getActionName(), 'App\\Http\\Controllers\\EmployeeController@'))
        ->each(fn ($route) => $route->flushController());
}

/** @param array{create?: bool, read?: bool, update?: bool, delete?: bool} $flags */
function phaseNineteenActingAs(int $type, array $flags): User
{
    $connection = phaseNineteenConnection();
    $permissionName = match ($type) {
        2 => 'Data Pegawai - Non ASN',
        3 => 'Data Pegawai - Outsourcing',
        default => 'Data Pegawai - ASN',
    };
    $prefix = phaseNineteenPrefix();
    $roleId = $connection->table('roles')->insertGetIdTs(['name' => $prefix.' Role']);
    $permissionId = $connection->table('permissions')->where('name', $permissionName)->value('id');
    $connection->table('role_permissions')->insertTs([
        'role_id' => $roleId,
        'permission_id' => $permissionId,
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
        'name' => 'Phase 19 Authorization User',
        'status' => true,
    ]);
    $user = User::query()->findOrFail($userId);
    Sanctum::actingAs($user);

    return $user;
}

/** @return array<string, mixed> */
function phaseNineteenEmployeePayload(int $type, ?string $prefix = null): array
{
    $connection = phaseNineteenConnection();
    $prefix ??= phaseNineteenPrefix();
    $digits = '8'.str_pad((string) random_int(0, 999999999999999), 15, '0', STR_PAD_LEFT);
    $employmentTypeId = $connection->table('employment_types')->where('type', $type)->where('status', true)->value('id');
    $payload = [
        'name' => 'Phase 19 Employee '.$type,
        'employee_id_number' => 'P19'.substr(hash('sha256', $prefix), 0, 15),
        'place_of_birth' => 'Jakarta',
        'date_of_birth' => '1990-01-02',
        'religion' => 1,
        'gender' => 1,
        'employment_type_id' => $employmentTypeId,
        'education_level' => 6,
        'id_number' => $digits,
        'office_email' => $prefix.'@office.example.invalid',
        'emergency_contact' => 'Phase 19 Contact',
        'employment_status' => 1,
        'type' => $type,
    ];
    if ($type === 1) {
        $payload['cpns_effective_date'] = '2020-01-01';
        $payload['grade_id'] = $connection->table('grades')->value('id');
        $payload['grade_effective_date'] = '2020-01-01';
    }

    return $payload;
}

function phaseNineteenFixtureEmployee(int $type = 1): int
{
    $payload = phaseNineteenEmployeePayload($type);

    return phaseNineteenConnection()->table('users')->insertGetIdTs($payload);
}

/** @return array<string, mixed> */
function phaseNineteenUpdatePayload(int $userId, array $overrides = []): array
{
    $user = phaseNineteenConnection()->table('users')->where('id', $userId)->firstOrFail();
    $payload = [
        'name' => 'Phase 19 Updated Employee',
        'employee_id_number' => $user->employee_id_number,
        'place_of_birth' => $user->place_of_birth ?? 'Jakarta',
        'date_of_birth' => (string) ($user->date_of_birth ?? '1990-01-02'),
        'religion' => $user->religion ?? 1,
        'gender' => $user->gender ?? 1,
        'employment_type_id' => $user->employment_type_id,
        'education_level' => $user->education_level ?? 6,
        'id_number' => $user->id_number,
        'office_email' => $user->office_email,
        'emergency_contact' => $user->emergency_contact ?? 'Phase 19 Contact',
        'employment_status' => $user->employment_status ?? 1,
        'type' => $user->type,
        'delete_employee_id_card' => false,
        'delete_photo_profile' => false,
    ];
    if ((int) $user->type === 1) {
        $payload['cpns_effective_date'] = (string) ($user->cpns_effective_date ?? '2020-01-01');
        $payload['grade_id'] = $user->grade_id ?? phaseNineteenConnection()->table('grades')->value('id');
        $payload['grade_effective_date'] = (string) ($user->grade_effective_date ?? '2020-01-01');
    }

    return array_replace($payload, $overrides);
}

/** @return list<string> */
function phaseNineteenChildTables(): array
{
    return [
        'user_educations', 'user_families', 'position_history_users', 'grade_history_users',
        'training_history_users', 'recognition_history_users', 'target_history_users',
        'performance_history_users', 'disciplinary_history_users', 'user_leaves', 'user_notes',
        'user_credits', 'user_assessments', 'user_competencies', 'user_talents',
    ];
}

function phaseNineteenSeedAllChildren(int $userId, int $giverId): void
{
    $connection = phaseNineteenConnection();
    $connection->table('user_educations')->insertTs(['user_id' => $userId, 'name' => 'Phase 19 Education']);
    $connection->table('user_families')->insertTs(['user_id' => $userId, 'name' => 'Phase 19 Family']);
    $connection->table('position_history_users')->insertTs([
        'position_history_id' => $connection->table('position_histories')->value('id'), 'user_id' => $userId,
    ]);
    $connection->table('grade_history_users')->insertTs([
        'grade_history_id' => $connection->table('grade_histories')->value('id'), 'user_id' => $userId,
    ]);
    $connection->table('training_history_users')->insertTs([
        'training_history_id' => $connection->table('training_histories')->value('id'), 'user_id' => $userId,
    ]);
    $connection->table('recognition_history_users')->insertTs([
        'recognition_history_id' => $connection->table('recognition_histories')->value('id'), 'user_id' => $userId,
    ]);
    $connection->table('target_history_users')->insertTs([
        'target_history_id' => $connection->table('target_histories')->value('id'), 'user_id' => $userId,
    ]);
    $connection->table('performance_history_users')->insertTs([
        'performance_history_id' => $connection->table('performance_histories')->value('id'), 'user_id' => $userId,
    ]);
    $connection->table('disciplinary_history_users')->insertTs([
        'disciplinary_history_id' => $connection->table('disciplinary_histories')->value('id'), 'user_id' => $userId,
    ]);
    $connection->table('user_leaves')->insertTs(['user_id' => $userId, 'description' => 'Phase 19 Leave']);
    $connection->table('user_notes')->insertTs(['user_id' => $userId, 'giver_id' => $giverId, 'description' => 'Phase 19 Note']);
    $connection->table('user_credits')->insertTs(['user_id' => $userId, 'score' => 10]);
    $connection->table('user_assessments')->insertTs(['user_id' => $userId, 'point' => 1]);
    $connection->table('user_competencies')->insertTs(['user_id' => $userId, 'point' => 1]);
    $connection->table('user_talents')->insertTs(['user_id' => $userId, 'point' => 1]);
}

it('registers the active Employee mutation routes in Laravel 10 declaration order', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/employees'))
        ->reject(fn ($route): bool => $route->uri() === 'api/employees/synchronization')
        ->map(fn ($route): array => [$route->methods()[0], $route->uri(), $route->getActionName(), $route->middleware()])
        ->values()->all();

    expect($routes)->toBe([
        ['GET', 'api/employees', 'App\\Http\\Controllers\\EmployeeController@index', ['api', 'auth:sanctum', 'role.access']],
        ['POST', 'api/employees', 'App\\Http\\Controllers\\EmployeeController@create', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/employees/{id}', 'App\\Http\\Controllers\\EmployeeController@show', ['api', 'auth:sanctum', 'role.access']],
        ['POST', 'api/employees/{id}', 'App\\Http\\Controllers\\EmployeeController@update', ['api', 'auth:sanctum', 'role.access']],
        ['DELETE', 'api/employees/{id}', 'App\\Http\\Controllers\\EmployeeController@delete', ['api', 'auth:sanctum', 'role.access']],
        ['PUT', 'api/employees/status', 'App\\Http\\Controllers\\EmployeeController@status', ['api', 'auth:sanctum', 'role.access']],
    ]);
});

it('retains type-specific and nested source validation contracts', function () {
    $createAsn = CreateEmployeeRequest::create('/api/employees', 'POST', ['type' => 1]);
    $createNonAsn = CreateEmployeeRequest::create('/api/employees', 'POST', ['type' => 2]);
    $createOutsource = CreateEmployeeRequest::create('/api/employees', 'POST', ['type' => 3]);

    expect($createAsn->rules()['cpns_effective_date'])->toBe('required|date')
        ->and($createNonAsn->rules()['cpns_effective_date'])->toBe('nullable|date')
        ->and(array_key_exists('grade_id', $createOutsource->rules()))->toBeFalse()
        ->and($createAsn->rules())->toHaveKeys([
            'educations.*.level', 'families.*.name', 'leaves.*.start_date', 'notes.*.description',
            'credits.*.score', 'assessments.*.point', 'competencies.*.point', 'talents.*.point',
        ])
        ->and((new UpdateEmployeeRequest)->rules())->toHaveKeys([
            'positions.*.id', 'grades.*.id', 'structurals.*.id', 'functionals.*.id', 'technicals.*.id',
            'targets.*.id', 'performances.*.id', 'disciplinaries.*.id',
        ])
        ->and(array_key_exists('recognitions.*.id', (new UpdateEmployeeRequest)->rules()))->toBeFalse()
        ->and((new UpdateStatusRequest)->messages()['quit_date.required_if'])->toBe('Tanggal berhenti bekerja tidak boleh kosong.');
});

describe('Employee mutations on the guarded MySQL clone', function () {
    beforeEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            $this->markTestSkipped('MySQL clone verification is opt-in.');
        }
        $database = (string) env('SIMDATUK_CLONE_DATABASE');
        if ($database !== 'SWP_simdatuk_test13') {
            throw new RuntimeException('Refusing MySQL writes: expected the authorized SWP_simdatuk_test13 clone.');
        }
        config()->set('app.debug', false);
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', $database);
        DB::purge('mysql');
        $connection = phaseNineteenConnection();
        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $database) {
            throw new RuntimeException('Refusing MySQL writes: clone connection guard failed.');
        }
        $engines = $connection->table('information_schema.tables')->where('table_schema', $database)
            ->whereIn('table_name', phaseNineteenTables())->pluck('ENGINE')->map(fn (?string $engine): string => strtoupper((string) $engine));
        if ($engines->count() !== count(phaseNineteenTables()) || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
            throw new RuntimeException('Refusing MySQL writes: every Phase 19 table must exist and use InnoDB.');
        }
        Storage::fake('s3');
        $connection->beginTransaction();
    });

    afterEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            return;
        }
        $connection = phaseNineteenConnection();
        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
        DB::disconnect('mysql');
    });

    it('creates each supported Employee type with the exact success contract', function (int $type) {
        phaseNineteenActingAs($type, ['create' => true]);
        $payload = phaseNineteenEmployeePayload($type);
        $payload['type'] = (string) $type;
        phaseNineteenFlushEmployeeControllers();

        $this->postJson('/api/employees', $payload)->assertOk()->assertExactJson([
            'code' => 200, 'message' => 'Pegawai berhasil ditambah.', 'data' => null,
        ]);
        $employee = phaseNineteenConnection()->table('users')->where('employee_id_number', $payload['employee_id_number'])->firstOrFail();
        expect((int) $employee->type)->toBe($type)
            ->and((int) $employee->employment_type_id)->toBe((int) $payload['employment_type_id']);
    })->with(['ASN' => [1], 'Non ASN' => [2], 'Outsourcing' => [3]]);

    it('creates the eight nested domains actually handled by Employee create', function () {
        $actor = phaseNineteenActingAs(1, ['create' => true]);
        $payload = array_merge(phaseNineteenEmployeePayload(1), [
            'educations' => [['level' => 6, 'name' => 'Phase 19 University', 'year_of_graduation' => '2020']],
            'families' => [['name' => 'Phase 19 Family', 'gender' => 1]],
            'leaves' => [['start_date' => '2026-01-01', 'end_date' => '2026-01-02', 'type' => 6]],
            'notes' => [['description' => 'Phase 19 Note']],
            'credits' => [['position' => 'Phase 19 Position', 'period' => 1, 'year' => '2026', 'score' => 10]],
            'assessments' => [['event_date' => '2026-01-03', 'point' => 3, 'organizer' => 'Phase 19']],
            'competencies' => [['event_date' => '2026-01-04', 'point' => 1, 'organizer' => 'Phase 19']],
            'talents' => [['event_date' => '2026-01-05', 'point' => 9, 'organizer' => 'Phase 19']],
        ]);

        $this->postJson('/api/employees', $payload)->assertOk();
        $userId = phaseNineteenConnection()->table('users')->where('employee_id_number', $payload['employee_id_number'])->value('id');
        foreach (['user_educations', 'user_families', 'user_leaves', 'user_notes', 'user_credits', 'user_assessments', 'user_competencies', 'user_talents'] as $table) {
            expect(phaseNineteenConnection()->table($table)->where('user_id', $userId)->count())->toBe(1);
        }
        expect((int) phaseNineteenConnection()->table('user_notes')->where('user_id', $userId)->value('giver_id'))->toBe($actor->id);
    });

    it('updates the core row and clears every omitted nested domain exactly as source', function () {
        $actor = phaseNineteenActingAs(1, ['create' => true, 'update' => false]);
        $userId = phaseNineteenFixtureEmployee();
        phaseNineteenSeedAllChildren($userId, $actor->id);

        $this->postJson("/api/employees/{$userId}", phaseNineteenUpdatePayload($userId))
            ->assertOk()->assertExactJson(['code' => 200, 'message' => 'Pegawai berhasil diubah.', 'data' => null]);

        expect(phaseNineteenConnection()->table('users')->where('id', $userId)->value('name'))->toBe('Phase 19 Updated Employee');
        foreach (phaseNineteenChildTables() as $table) {
            expect(phaseNineteenConnection()->table($table)->where('user_id', $userId)->count(), $table)->toBe(0);
        }
    });

    it('preserves update replacement, insertion, deletion, and unscoped child id behavior', function () {
        phaseNineteenActingAs(1, ['create' => true]);
        $firstUserId = phaseNineteenFixtureEmployee();
        $secondUserId = phaseNineteenFixtureEmployee();
        $firstEducationId = phaseNineteenConnection()->table('user_educations')->insertGetIdTs(['user_id' => $firstUserId, 'name' => 'First child']);
        $foreignEducationId = phaseNineteenConnection()->table('user_educations')->insertGetIdTs(['user_id' => $secondUserId, 'name' => 'Foreign child']);
        $payload = phaseNineteenUpdatePayload($firstUserId, ['educations' => [
            [
                'id' => $foreignEducationId, 'name' => 'Cross employee update',
                'delete_degree_document' => false, 'delete_study_assignment_letter' => false,
                'delete_academic_title_letter' => false,
            ],
            [
                'name' => 'Inserted child', 'delete_degree_document' => false,
                'delete_study_assignment_letter' => false, 'delete_academic_title_letter' => false,
            ],
        ]]);

        $this->postJson("/api/employees/{$firstUserId}", $payload)->assertOk();

        expect(phaseNineteenConnection()->table('user_educations')->where('id', $firstEducationId)->exists())->toBeFalse()
            ->and(phaseNineteenConnection()->table('user_educations')->where('id', $foreignEducationId)->value('name'))->toBe('Cross employee update')
            ->and((int) phaseNineteenConnection()->table('user_educations')->where('name', 'Inserted child')->value('user_id'))->toBe($firstUserId);
    });

    it('updates status without a transaction and nulls Position for inactive statuses', function () {
        phaseNineteenActingAs(1, ['update' => true]);
        $userId = phaseNineteenFixtureEmployee();
        $positionId = phaseNineteenConnection()->table('positions')->value('id');
        phaseNineteenConnection()->table('users')->where('id', $userId)->update(['position_id' => $positionId]);

        $this->putJson('/api/employees/status', [
            'id' => $userId, 'employment_status' => 2, 'quit_date' => '2026-01-01', 'type' => 1,
        ])->assertOk()->assertExactJson(['code' => 200, 'message' => 'Pegawai berhasil diupdate.', 'data' => null]);

        $employee = phaseNineteenConnection()->table('users')->where('id', $userId)->firstOrFail();
        expect((int) $employee->employment_status)->toBe(2)->and($employee->position_id)->toBeNull();
    });

    it('deletes only an isolated Employee, relies on FK cascades, leaves its Sanctum token orphaned, and removes its device session', function () {
        $actor = phaseNineteenActingAs(1, ['delete' => true]);
        $userId = phaseNineteenFixtureEmployee();
        phaseNineteenSeedAllChildren($userId, $actor->id);
        $employee = User::query()->findOrFail($userId);
        $token = $employee->createToken('phase19-delete');
        phaseNineteenConnection()->table('user_device_sessions')->insertTs([
            'user_id' => $userId, 'device_identifier' => phaseNineteenPrefix(),
            'sanctum_token_id' => (string) $token->accessToken->id, 'last_activity_at' => now(),
        ]);
        $parentCounts = collect(['position_histories', 'grade_histories', 'training_histories', 'recognition_histories', 'target_histories', 'performance_histories', 'disciplinary_histories'])
            ->mapWithKeys(fn (string $table): array => [$table => phaseNineteenConnection()->table($table)->count()]);

        $this->deleteJson("/api/employees/{$userId}?type=1")->assertOk()->assertExactJson([
            'code' => 200, 'message' => 'Pegawai berhasil dihapus.', 'data' => null,
        ]);

        expect(phaseNineteenConnection()->table('users')->where('id', $userId)->exists())->toBeFalse()
            ->and(phaseNineteenConnection()->table('user_device_sessions')->where('user_id', $userId)->exists())->toBeFalse()
            ->and(phaseNineteenConnection()->table('personal_access_tokens')->where('id', $token->accessToken->id)->exists())->toBeTrue();
        foreach (phaseNineteenChildTables() as $table) {
            expect(phaseNineteenConnection()->table($table)->where('user_id', $userId)->exists(), $table)->toBeFalse();
        }
        foreach ($parentCounts as $table => $count) {
            expect(phaseNineteenConnection()->table($table)->count(), $table)->toBe($count);
        }
    });

    it('preserves validation messages and POST to create permission authorization', function () {
        phaseNineteenActingAs(1, ['create' => true, 'update' => false]);
        $this->postJson('/api/employees', ['type' => 1])->assertStatus(422)
            ->assertJsonPath('message', 'Nama tidak boleh kosong.');

        $userId = phaseNineteenFixtureEmployee();
        $this->postJson("/api/employees/{$userId}", phaseNineteenUpdatePayload($userId))->assertOk();
    });

    it('preserves the numeric JSON type authorization mismatch and explicit denial', function () {
        phaseNineteenActingAs(1, ['create' => true]);
        $payload = phaseNineteenEmployeePayload(2);

        $this->postJson('/api/employees', $payload)->assertOk();
        expect((int) phaseNineteenConnection()->table('users')->where('employee_id_number', $payload['employee_id_number'])->value('type'))->toBe(2);
    });

    it('denies Employee POST when the mapped create flag is false even if update is true', function () {
        phaseNineteenActingAs(1, ['create' => false, 'update' => true]);
        $payload = phaseNineteenEmployeePayload(1);

        $this->postJson('/api/employees', $payload)->assertStatus(403)->assertExactJson([
            'code' => 403,
            'message' => "Access denied. You don't have permission to create Data Pegawai - ASN.",
            'data' => null,
        ]);
        expect(phaseNineteenConnection()->table('users')->where('employee_id_number', $payload['employee_id_number'])->exists())->toBeFalse();
    });

    it('preserves pre-transaction lookup failures and an open transaction after create Position early return', function () {
        phaseNineteenActingAs(1, ['create' => true]);
        $invalidEmployment = phaseNineteenEmployeePayload(1, phaseNineteenPrefix());
        $invalidEmployment['employment_type_id'] = ((int) phaseNineteenConnection()->table('employment_types')->max('id')) + 100000;
        $levelBefore = phaseNineteenConnection()->transactionLevel();

        $this->postJson('/api/employees', $invalidEmployment)->assertStatus(404)->assertExactJson([
            'code' => 404, 'message' => 'Jenis pegawai tidak ditemukan.', 'data' => null,
        ]);
        expect(phaseNineteenConnection()->transactionLevel())->toBe($levelBefore);

        phaseNineteenFlushEmployeeControllers();
        $invalidPosition = phaseNineteenEmployeePayload(1, phaseNineteenPrefix());
        $invalidPosition['position_id'] = ((int) phaseNineteenConnection()->table('positions')->max('id')) + 100000;
        $this->postJson('/api/employees', $invalidPosition)->assertStatus(404)->assertExactJson([
            'code' => 404, 'message' => 'Jabatan tidak ditemukan.', 'data' => null,
        ]);
        expect(phaseNineteenConnection()->transactionLevel())->toBe($levelBefore + 1);
    });

    it('returns exact missing contracts and preserves an open transaction after update early return', function () {
        phaseNineteenActingAs(1, ['create' => true, 'delete' => true, 'update' => true]);
        $missingId = ((int) phaseNineteenConnection()->table('users')->max('id')) + 100000;
        $levelBefore = phaseNineteenConnection()->transactionLevel();

        $this->postJson("/api/employees/{$missingId}", phaseNineteenEmployeePayload(1) + [
            'delete_employee_id_card' => false, 'delete_photo_profile' => false,
        ])->assertStatus(404)->assertExactJson(['code' => 404, 'message' => 'Pegawai tidak ditemukan.', 'data' => null]);
        expect(phaseNineteenConnection()->transactionLevel())->toBe($levelBefore + 1);

        $this->deleteJson("/api/employees/{$missingId}?type=1")->assertStatus(404)->assertExactJson([
            'code' => 404, 'message' => 'Pegawai tidak ditemukan.', 'data' => null,
        ]);
    });

    it('uses fake S3, persists generated paths, and leaves objects orphaned when DB fields are cleared', function () {
        phaseNineteenActingAs(1, ['create' => true]);
        $payload = array_merge(phaseNineteenEmployeePayload(1), [
            'photo_profile' => UploadedFile::fake()->image('profile.jpg', 350, 500)->size(100),
            'employee_id_card' => UploadedFile::fake()->create('card.pdf', 100, 'application/pdf'),
            'educations' => [[
                'name' => 'Phase 19 File Education',
                'degree_document' => UploadedFile::fake()->create('degree.pdf', 100, 'application/pdf'),
            ]],
        ]);

        $this->withHeader('Accept', 'application/json')->post('/api/employees', $payload)->assertOk();
        $employee = phaseNineteenConnection()->table('users')->where('employee_id_number', $payload['employee_id_number'])->firstOrFail();
        $degreePath = phaseNineteenConnection()->table('user_educations')->where('user_id', $employee->id)->value('degree_document');
        expect(Storage::disk('s3')->exists($employee->photo_profile))->toBeTrue()
            ->and(Storage::disk('s3')->exists($employee->employee_id_card))->toBeTrue()
            ->and(Storage::disk('s3')->exists($degreePath))->toBeTrue();

        $update = phaseNineteenUpdatePayload($employee->id, [
            'delete_photo_profile' => true, 'delete_employee_id_card' => true,
        ]);
        $this->postJson("/api/employees/{$employee->id}", $update)->assertOk();
        $updated = phaseNineteenConnection()->table('users')->where('id', $employee->id)->firstOrFail();
        expect($updated->photo_profile)->toBeNull()->and($updated->employee_id_card)->toBeNull()
            ->and(Storage::disk('s3')->exists($employee->photo_profile))->toBeTrue()
            ->and(Storage::disk('s3')->exists($employee->employee_id_card))->toBeTrue()
            ->and(Storage::disk('s3')->exists($degreePath))->toBeTrue();
    });

    it('rolls back a failed create but preserves a fake S3 object uploaded before the DB error', function () {
        phaseNineteenActingAs(1, ['create' => true]);
        $payload = array_merge(phaseNineteenEmployeePayload(1), [
            'photo_profile' => UploadedFile::fake()->image('profile.jpg', 350, 500)->size(100),
            'phase19_unknown_column' => 'force database error',
        ]);
        $expectedPath = '/photo_profile/'.$payload['employee_id_number'].'.jpg';
        $levelBefore = phaseNineteenConnection()->transactionLevel();

        $this->withHeader('Accept', 'application/json')->post('/api/employees', $payload)
            ->assertStatus(400)->assertExactJson([
                'code' => 400,
                'message' => 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!',
                'data' => null,
            ]);

        expect(phaseNineteenConnection()->transactionLevel())->toBe($levelBefore)
            ->and(phaseNineteenConnection()->table('users')->where('employee_id_number', $payload['employee_id_number'])->exists())->toBeFalse()
            ->and(Storage::disk('s3')->exists($expectedPath))->toBeTrue();
    });
});
