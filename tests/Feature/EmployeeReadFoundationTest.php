<?php

use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/** @return list<string> */
function phaseElevenTables(): array
{
    return [
        'users',
        'positions',
        'grades',
        'employment_types',
        'echelons',
        'roles',
        'permissions',
        'role_permissions',
    ];
}

function phaseElevenConnection(): Connection
{
    return DB::connection('mysql');
}

function phaseElevenPrefix(): string
{
    return 'codex_phase11_'.bin2hex(random_bytes(6));
}

function phaseElevenActingAsEmployeeReader(): User
{
    $connection = phaseElevenConnection();
    $permissionNames = [
        'Data Pegawai - ASN',
        'Data Pegawai - Non ASN',
        'Data Pegawai - Outsourcing',
    ];
    $roleId = $connection->table('role_permissions as rp')
        ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
        ->whereIn('p.name', $permissionNames)
        ->where('rp.read', true)
        ->groupBy('rp.role_id')
        ->havingRaw('COUNT(DISTINCT p.name) = 3')
        ->value('rp.role_id');
    $user = User::query()
        ->where('role_id', $roleId)
        ->where('status', true)
        ->orderBy('id')
        ->firstOrFail();

    Sanctum::actingAs($user);

    return $user;
}

/** @param array<string, bool> $readPermissions */
function phaseElevenActingAsRestrictedReader(array $readPermissions): User
{
    $connection = phaseElevenConnection();
    $prefix = phaseElevenPrefix();
    $roleId = $connection->table('roles')->insertGetIdTs(['name' => $prefix.' Role']);
    $employeePermissions = $connection->table('permissions')
        ->whereIn('name', [
            'Data Pegawai - ASN',
            'Data Pegawai - Non ASN',
            'Data Pegawai - Outsourcing',
        ])
        ->get(['id', 'name']);
    $pivots = $employeePermissions->map(fn (object $permission): array => [
        'role_id' => $roleId,
        'permission_id' => $permission->id,
        'create' => false,
        'read' => $readPermissions[$permission->name] ?? false,
        'update' => false,
        'delete' => false,
    ])->all();
    $connection->table('role_permissions')->insertTs($pivots);
    $userId = $connection->table('users')->insertGetIdTs([
        'role_id' => $roleId,
        'email' => $prefix.'@example.invalid',
        'username' => $prefix,
        'password' => Hash::make('Password@123'),
        'name' => 'Phase 11 Restricted Reader',
        'status' => true,
    ]);
    $user = User::query()->findOrFail($userId);
    Sanctum::actingAs($user);

    return $user;
}

it('preserves the Employee index route introduced in Phase 11', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => $route->uri() === 'api/employees' && in_array('GET', $route->methods(), true))
        ->map(fn ($route): array => [
            'method' => $route->methods()[0],
            'uri' => $route->uri(),
            'action' => $route->getActionName(),
            'middleware' => $route->middleware(),
        ])
        ->values()
        ->all();

    expect($routes)->toBe([[
        'method' => 'GET',
        'uri' => 'api/employees',
        'action' => 'App\Http\Controllers\EmployeeController@index',
        'middleware' => ['api', 'auth:sanctum', 'role.access'],
    ]]);
});

describe('Employee index on the guarded MySQL clone', function () {
    beforeEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            $this->markTestSkipped('MySQL clone verification is opt-in.');
        }

        $cloneDatabase = (string) env('SIMDATUK_CLONE_DATABASE');

        if ($cloneDatabase !== 'SWP_simdatuk_test13') {
            throw new RuntimeException('Refusing MySQL access: expected the authorized SWP_simdatuk_test13 clone.');
        }

        config()->set('app.debug', false);
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', $cloneDatabase);
        DB::purge('mysql');

        $connection = phaseElevenConnection();

        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $cloneDatabase) {
            throw new RuntimeException('Refusing MySQL access: clone connection guard failed.');
        }

        $engines = $connection->table('information_schema.tables')
            ->where('table_schema', $cloneDatabase)
            ->whereIn('table_name', phaseElevenTables())
            ->pluck('ENGINE')
            ->map(fn (?string $engine): string => strtoupper((string) $engine));

        if ($engines->count() !== count(phaseElevenTables()) || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
            throw new RuntimeException('Refusing MySQL access: every Employee index table must exist and use InnoDB.');
        }

        $connection->beginTransaction();
    });

    afterEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            return;
        }

        $connection = phaseElevenConnection();

        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }

        DB::disconnect('mysql');
    });

    it('lists and paginates each supported employee type in source ordering', function (int $type) {
        $connection = phaseElevenConnection();
        phaseElevenActingAsEmployeeReader();
        $expectedTotal = $connection->table('users')->where('type', $type)->count();

        $response = $this->getJson("/api/employees?type={$type}&limit=7&page=2");

        $response->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'success')
            ->assertJsonPath('pagination.total', $expectedTotal)
            ->assertJsonPath('pagination.per_page', 7)
            ->assertJsonPath('pagination.current_page', 2)
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'photo_profile',
                    'name',
                    'employee_id_number',
                    'employee_registration_number',
                    'position_name',
                    'echelon_name',
                    'echelon_effective_date',
                    'grade_name',
                    'grade_effective_date',
                    'employment_type',
                    'description',
                ]],
                'pagination',
            ]);

        $responseIds = collect($response->json('data'))->pluck('id')->all();
        $usersById = $connection->table('users')
            ->whereIn('id', $responseIds)
            ->get(['id', 'employment_status', 'echelon_id', 'position_id'])
            ->keyBy('id');
        $orderingKeys = collect($responseIds)->map(function (int $id) use ($usersById): array {
            $user = $usersById->get($id);

            return collect([$user->employment_status, $user->echelon_id, $user->position_id])
                ->map(fn ($value): int => $value === null ? PHP_INT_MIN : (int) $value)
                ->all();
        })->all();
        $sortedOrderingKeys = $orderingKeys;
        usort($sortedOrderingKeys, fn (array $left, array $right): int => $left <=> $right);

        expect($responseIds)->toHaveCount(7)
            ->and($orderingKeys)->toBe($sortedOrderingKeys)
            ->and($connection->table('users')->whereIn('id', $responseIds)->pluck('type')->unique()->all())->toBe([$type]);
    })->with([1, 2, 3]);

    it('searches by employee number and preserves MySQL title date and join expressions', function () {
        $connection = phaseElevenConnection();
        phaseElevenActingAsEmployeeReader();
        $employee = $connection->table('users as u')
            ->leftJoin('positions as p', 'u.position_id', '=', 'p.id')
            ->leftJoin('grades as g', 'u.grade_id', '=', 'g.id')
            ->leftJoin('employment_types as et', 'u.employment_type_id', '=', 'et.id')
            ->leftJoin('echelons as e', 'u.echelon_id', '=', 'e.id')
            ->whereNotNull('u.employee_id_number')
            ->where('u.employee_id_number', '!=', '')
            ->whereNotNull('u.position_id')
            ->whereNotNull('u.grade_id')
            ->whereNotNull('u.echelon_id')
            ->whereNotNull('u.employment_type_id')
            ->orderBy('u.id')
            ->select('u.*', 'p.name as expected_position', 'g.name as grade_name', 'g.code as grade_code', 'et.name as expected_employment_type', 'e.name as expected_echelon')
            ->firstOrFail();

        $response = $this->getJson('/api/employees?search='.rawurlencode($employee->employee_id_number));

        $response->assertOk()->assertJsonCount(1, 'data');
        $data = $response->json('data.0');
        $expectedName = match (true) {
            $employee->title_prefix === null && $employee->title_suffix === null => $employee->name,
            $employee->title_prefix !== null && $employee->title_suffix === null => $employee->title_prefix.' '.$employee->name,
            $employee->title_prefix === null && $employee->title_suffix !== null => $employee->name.' '.$employee->title_suffix,
            default => $employee->title_prefix.' '.$employee->name.' '.$employee->title_suffix,
        };

        expect($data)->toBe([
            'id' => $employee->id,
            'photo_profile' => is_null($employee->photo_profile)
                ? asset('img/profile.jpg')
                : url('/api/image/'.ltrim($employee->photo_profile, '/')),
            'name' => $expectedName,
            'employee_id_number' => $employee->employee_id_number,
            'employee_registration_number' => $employee->employee_registration_number,
            'position_name' => $employee->expected_position,
            'echelon_name' => $employee->expected_echelon,
            'echelon_effective_date' => $employee->echelon_effective_date === null ? null : Carbon::parse($employee->echelon_effective_date)->format('d-m-Y'),
            'grade_name' => $employee->grade_name.' '.$employee->grade_code,
            'grade_effective_date' => $employee->grade_effective_date === null ? null : Carbon::parse($employee->grade_effective_date)->format('d-m-Y'),
            'employment_type' => $employee->expected_employment_type,
            'description' => $employee->description,
        ]);
    });

    it('combines every major Employee list filter against a real clone row', function () {
        $connection = phaseElevenConnection();
        phaseElevenActingAsEmployeeReader();
        $employee = $connection->table('users')
            ->whereNotNull('employee_id_number')
            ->where('employee_id_number', '!=', '')
            ->whereNotNull('type')
            ->whereNotNull('position_id')
            ->whereNotNull('grade_id')
            ->whereNotNull('echelon_id')
            ->whereNotNull('employment_type_id')
            ->whereNotNull('religion')
            ->whereNotNull('employment_status')
            ->whereNotNull('date_of_birth')
            ->whereNotNull('gender')
            ->whereNotNull('education_level')
            ->whereDate('date_of_birth', '<=', now()->subYear()->format('Y-m-d'))
            ->whereDate('date_of_birth', '>=', now()->subYears(120)->format('Y-m-d'))
            ->orderBy('id')
            ->firstOrFail();
        $query = http_build_query([
            'search' => $employee->employee_id_number,
            'type' => $employee->type,
            'position_id' => $employee->position_id,
            'grade_id' => $employee->grade_id,
            'echelon_id' => $employee->echelon_id,
            'employment_type_id' => $employee->employment_type_id,
            'religion' => $employee->religion,
            'employment_status' => $employee->employment_status,
            'month_of_birth' => Carbon::parse($employee->date_of_birth)->month,
            'gender' => $employee->gender,
            'min_age' => 1,
            'max_age' => 120,
            'education_level' => $employee->education_level,
            'limit' => 5,
        ]);

        $this->getJson('/api/employees?'.$query)
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.id', $employee->id);
    });

    it('renders both default and stored photo URLs without accessing S3', function (bool $withStoredPhoto) {
        $connection = phaseElevenConnection();
        phaseElevenActingAsEmployeeReader();
        $employeeQuery = $connection->table('users')
            ->whereNotNull('employee_id_number')
            ->where('employee_id_number', '!=', '');
        $withStoredPhoto
            ? $employeeQuery->whereNotNull('photo_profile')
            : $employeeQuery->whereNull('photo_profile');
        $employee = $employeeQuery->orderBy('id')->firstOrFail();
        $expectedUrl = $withStoredPhoto
            ? url('/api/image/'.ltrim($employee->photo_profile, '/'))
            : asset('img/profile.jpg');

        $this->getJson('/api/employees?search='.rawurlencode($employee->employee_id_number))
            ->assertOk()
            ->assertJsonPath('data.0.id', $employee->id)
            ->assertJsonPath('data.0.photo_profile', $expectedUrl);
    })->with([false, true]);

    it('returns the legacy empty result envelope', function () {
        phaseElevenActingAsEmployeeReader();

        $this->getJson('/api/employees?search='.phaseElevenPrefix())
            ->assertExactJson([
                'code' => 200,
                'message' => 'Mohon maaf, data tidak ditemukan.',
                'data' => [],
            ]);
    });

    it('preserves inline page and limit validation messages', function () {
        phaseElevenActingAsEmployeeReader();

        $this->getJson('/api/employees?page=0&limit=0')
            ->assertUnprocessable()
            ->assertExactJson([
                'code' => 422,
                'message' => 'Page minimal harus 1 atau lebih.',
                'data' => [
                    'page' => ['Page minimal harus 1 atau lebih.'],
                    'limit' => ['Limit minimal harus 1 atau lebih.'],
                ],
            ]);
    });

    it('defaults missing type authorization to ASN while leaving the list unfiltered', function () {
        $connection = phaseElevenConnection();
        phaseElevenActingAsRestrictedReader(['Data Pegawai - ASN' => true]);

        $this->getJson('/api/employees?limit=3')
            ->assertOk()
            ->assertJsonPath('pagination.total', $connection->table('users')->count())
            ->assertJsonCount(3, 'data');
    });

    it('allows each valid type only through its matching permission', function (int $type, string $permissionName) {
        $connection = phaseElevenConnection();
        phaseElevenActingAsRestrictedReader([$permissionName => true]);

        $this->getJson("/api/employees?type={$type}&limit=2")
            ->assertOk()
            ->assertJsonPath('pagination.total', $connection->table('users')->where('type', $type)->count());
    })->with([
        [1, 'Data Pegawai - ASN'],
        [2, 'Data Pegawai - Non ASN'],
        [3, 'Data Pegawai - Outsourcing'],
    ]);

    it('denies a valid type when only a different employee permission is readable', function () {
        phaseElevenActingAsRestrictedReader(['Data Pegawai - ASN' => true]);

        $this->getJson('/api/employees?type=2')
            ->assertForbidden()
            ->assertExactJson([
                'code' => 403,
                'message' => "Access denied. You don't have permission to read Data Pegawai - Non ASN.",
                'data' => null,
            ]);
    });

    it('uses ASN permission for invalid type and then returns the query empty result', function () {
        phaseElevenActingAsRestrictedReader(['Data Pegawai - ASN' => true]);

        $this->getJson('/api/employees?type=999999')
            ->assertExactJson([
                'code' => 200,
                'message' => 'Mohon maaf, data tidak ditemukan.',
                'data' => [],
            ]);
    });

    it('denies invalid type when ASN permission is missing even if Non ASN is readable', function () {
        phaseElevenActingAsRestrictedReader(['Data Pegawai - Non ASN' => true]);

        $this->getJson('/api/employees?type=999999')
            ->assertForbidden()
            ->assertExactJson([
                'code' => 403,
                'message' => "Access denied. You don't have permission to read Data Pegawai - ASN.",
                'data' => null,
            ]);
    });
});
