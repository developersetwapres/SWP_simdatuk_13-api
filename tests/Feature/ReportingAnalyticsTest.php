<?php

use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/** @return list<string> */
function phaseTwentyOneTables(): array
{
    return [
        'users', 'roles', 'permissions', 'role_permissions', 'positions', 'position_echelons',
        'echelons', 'grades', 'employment_types', 'position_history_users',
        'training_histories', 'training_history_users', 'target_history_users',
        'disciplinaries', 'disciplinary_history_users', 'user_notes', 'user_assessments',
        'user_competencies', 'user_credits', 'user_talents',
    ];
}

function phaseTwentyOneConnection(): Connection
{
    return DB::connection('mysql');
}

function phaseTwentyOnePrefix(): string
{
    return 'codex_phase21_'.bin2hex(random_bytes(6));
}

/** @param list<string> $readPermissions */
function phaseTwentyOneActingAs(array $readPermissions = []): User
{
    $connection = phaseTwentyOneConnection();
    $prefix = phaseTwentyOnePrefix();
    $roleId = $connection->table('roles')->insertGetIdTs(['name' => $prefix.' Role']);

    foreach ($readPermissions as $permissionName) {
        $permissionId = $connection->table('permissions')->where('name', $permissionName)->value('id');
        if (! $permissionId) {
            throw new RuntimeException("Clone permission is missing: {$permissionName}");
        }
        $connection->table('role_permissions')->insertTs([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'create' => false,
            'read' => true,
            'update' => false,
            'delete' => false,
        ]);
    }

    $userId = $connection->table('users')->insertGetIdTs([
        'role_id' => $roleId,
        'email' => $prefix.'@example.invalid',
        'username' => $prefix,
        'password' => Hash::make('Password@123'),
        'name' => 'Phase 21 Authorization User',
        'status' => true,
    ]);
    $user = User::query()->findOrFail($userId);
    Sanctum::actingAs($user);

    return $user;
}

function phaseTwentyOneAllReadPermissions(): array
{
    return [
        'Rekapitulasi - Komposisi Pegawai',
        'Rekapitulasi - Pegawai ASN',
        'Rekapitulasi - Pegawai Non ASN',
        'Rekapitulasi - Pegawai Outsourcing',
        'Rekapitulasi - Peta Jabatan',
        'Rekapitulasi - Bandingkan Pegawai',
        'Rekapitulasi - Promosi Pegawai',
    ];
}

it('registers all sixteen Phase 21 routes in Laravel 10 order', function () {
    $prefixes = [
        'api/summaries', 'api/recapitulations', 'api/diagrams', 'api/comparisons',
        'api/promotions', 'api/profile',
    ];
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => collect($prefixes)->contains(fn (string $prefix): bool => str_starts_with($route->uri(), $prefix)))
        ->reject(fn ($route): bool => $route->uri() === 'api/diagrams/export')
        ->map(fn ($route): array => [$route->methods()[0], $route->uri(), $route->getActionName(), $route->middleware()])
        ->values()->all();

    expect($routes)->toBe([
        ['GET', 'api/summaries', 'App\\Http\\Controllers\\SummaryController@index', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/recapitulations', 'App\\Http\\Controllers\\RecapitulationController@index', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/recapitulations/{category}', 'App\\Http\\Controllers\\RecapitulationController@show', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/recapitulations-asn', 'App\\Http\\Controllers\\RecapitulationAsnController@index', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/recapitulations-asn/{category}', 'App\\Http\\Controllers\\RecapitulationAsnController@show', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/recapitulations-nonasn', 'App\\Http\\Controllers\\RecapitulationNonAsnController@index', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/recapitulations-outsource', 'App\\Http\\Controllers\\RecapitulationOutsourceController@index', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/recapitulations-employee', 'App\\Http\\Controllers\\RecapitulationEmployeeController@index', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/diagrams', 'App\\Http\\Controllers\\DiagramController@index', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/comparisons', 'App\\Http\\Controllers\\ComparisonController@index', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/comparisons/detail', 'App\\Http\\Controllers\\ComparisonController@comparison', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/comparisons/detail-promotions', 'App\\Http\\Controllers\\ComparisonController@comparisonPromotion', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/promotions', 'App\\Http\\Controllers\\PromotionController@index', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/promotions/detail', 'App\\Http\\Controllers\\PromotionController@show', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/profile', 'App\\Http\\Controllers\\ProfileController@show', ['api', 'auth:sanctum', 'role.access']],
        ['POST', 'api/profile', 'App\\Http\\Controllers\\ProfileController@update', ['api', 'auth:sanctum', 'role.access']],
    ]);
});

describe('Phase 21 reporting APIs on the guarded MySQL clone', function () {
    beforeEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            $this->markTestSkipped('MySQL clone verification is opt-in.');
        }
        $database = (string) env('SIMDATUK_CLONE_DATABASE');
        if ($database !== 'SWP_simdatuk_test13') {
            throw new RuntimeException('Refusing Phase 21 clone access: exact database name mismatch.');
        }
        config()->set('app.debug', false);
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', $database);
        DB::purge('mysql');
        $connection = phaseTwentyOneConnection();
        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $database) {
            throw new RuntimeException('Refusing Phase 21 clone access: MySQL/database guard failed.');
        }
        $engines = $connection->table('information_schema.tables')->where('table_schema', $database)
            ->whereIn('table_name', phaseTwentyOneTables())->pluck('ENGINE')
            ->map(fn (?string $engine): string => strtoupper((string) $engine));
        if ($engines->count() !== count(phaseTwentyOneTables()) || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
            throw new RuntimeException('Refusing Phase 21 clone access: every guarded table must exist and use InnoDB.');
        }
        $connection->beginTransaction();
    });

    afterEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            return;
        }
        $connection = phaseTwentyOneConnection();
        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
        DB::disconnect('mysql');
    });

    it('matches Summary and composition totals to independent clone counts', function () {
        phaseTwentyOneActingAs(phaseTwentyOneAllReadPermissions());

        $summary = $this->getJson('/api/summaries?month=1')->assertOk()
            ->assertJsonPath('code', 200)->assertJsonPath('message', 'success')
            ->assertJsonStructure(['data' => ['users', 'total_government_employees', 'gender_employees', 'total_non_government_employees', 'work_unit', 'education_employees', 'generations']])
            ->json('data');
        $activeAsn = phaseTwentyOneConnection()->table('users')->where('type', 1)->whereIn('employment_status', [1, 6, 10])->count();
        $allAsn = phaseTwentyOneConnection()->table('users')->where('type', 1)->whereIn('employment_status', [1, 6, 7, 8, 9, 10])->count();
        expect((int) $summary['total_government_employees']['active'])->toBe($activeAsn)
            ->and((int) $summary['total_government_employees']['all'])->toBe($allAsn)
            ->and(array_column($summary['education_employees'], 'name'))->toBe([
                'Strata III', 'Strata II', 'Diploma IV / Strata I', 'Akademi / Diploma III / Sarjana Muda',
                'Diploma I / II', 'SLTA / Sederajat', 'SLTP / Sederajat', 'SD / Sederajat',
            ]);

        $recap = $this->getJson('/api/recapitulations')->assertOk()->assertJsonPath('message', 'success')->json('data');
        $expectedTotal = phaseTwentyOneConnection()->table('users')->where(function ($query): void {
            $query->where(fn ($q) => $q->where('type', 1)->whereIn('employment_status', [1, 6, 7, 8, 9, 10]))
                ->orWhere(fn ($q) => $q->where('type', 2)->whereIn('employment_status', [1, 6, 10])->where('employment_type_id', '!=', 16))
                ->orWhere(fn ($q) => $q->where('type', 3)->whereIn('employment_status', [1, 6, 10])->where('employment_type_id', 19));
        })->count();
        expect((int) $recap['total'])->toBe($expectedTotal)
            ->and(array_column($recap['cards'][0]['cards'], 'id'))->toBe([1, 2, 3, 4]);

        foreach ([1, 2, 3, 4] as $category) {
            $this->getJson("/api/recapitulations/{$category}")->assertOk()->assertJsonPath('data.id', $category);
        }
    });

    it('runs every specialized recapitulation and representative drill-down query', function () {
        phaseTwentyOneActingAs(phaseTwentyOneAllReadPermissions());

        $asn = $this->getJson('/api/recapitulations-asn')->assertOk()->json('data');
        expect($asn['name'])->toBe('Rekapitulasi Pegawai ASN')
            ->and(array_column($asn['cards'], 'id'))->toBe([1, 2, 3, 4, 5, 6, 7]);
        foreach ([1, 2, 3] as $category) {
            $this->getJson("/api/recapitulations-asn/{$category}")->assertOk()->assertJsonPath('data.id', $category);
        }

        $this->getJson('/api/recapitulations-nonasn')->assertOk()
            ->assertJsonPath('data.name', 'Rekapitulasi Pegawai Non ASN');
        $this->getJson('/api/recapitulations-outsource')->assertOk()
            ->assertJsonPath('data.name', 'Rekapitulasi Pegawai Outsourcing');

        $drillDown = $this->getJson('/api/recapitulations-employee?page=nonasn&section_id=3&card_id=6')->assertOk()
            ->assertJsonStructure(['data' => ['total', 'items']])->json('data');
        expect($drillDown['total'])->toBe(count($drillDown['items']));
    });

    it('runs Diagram, Comparison, and Promotion queries with source contracts', function () {
        phaseTwentyOneActingAs(phaseTwentyOneAllReadPermissions());

        $this->getJson('/api/diagrams')->assertOk()->assertJsonPath('message', 'success')
            ->assertJsonStructure(['data']);

        $comparisonResponse = $this->getJson('/api/comparisons?limit=2&page=1')->assertOk()
            ->assertJsonPath('message', 'success')
            ->assertJsonStructure(['data', 'pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages', 'links']]);
        expect($comparisonResponse->json('data'))->toHaveCount(2);

        $ids = phaseTwentyOneConnection()->table('users')->where('type', 1)
            ->whereIn('employment_status', [1, 6, 10])->orderBy('id')->limit(2)->pluck('id')->all();
        expect($ids)->toHaveCount(2);
        $query = http_build_query(['user_id' => $ids]);
        $detail = $this->getJson('/api/comparisons/detail?'.$query)->assertOk()->json('data');
        expect($detail)->toHaveCount(2)
            ->and($detail[0])->toHaveKeys(['positions', 'structurals', 'functionals', 'technicals', 'targets', 'disciplinaries', 'notes', 'assessments', 'competencies', 'talents']);
        $this->getJson('/api/comparisons/detail-promotions?'.$query)->assertOk()
            ->assertJsonCount(2, 'data');

        $promotion = $this->getJson('/api/promotions')->assertOk()->json('data');
        expect($promotion)->toHaveCount(13)
            ->and($promotion[0]['name'])->toBe('Jabatan Pimpinan Tinggi');
        $echelonId = phaseTwentyOneConnection()->table('position_echelons')->orderBy('id')->value('echelon_id');
        $this->getJson('/api/promotions/detail?echelon_id='.$echelonId)->assertOk()
            ->assertJsonPath('message', 'success')->assertJsonStructure(['data']);

    });

    it('preserves Comparison and Promotion inline validation messages', function () {
        phaseTwentyOneActingAs(phaseTwentyOneAllReadPermissions());
        $this->getJson('/api/comparisons?page=abc')->assertStatus(422)
            ->assertJsonPath('data.page.0', 'Page harus berupa angka.');
        $this->getJson('/api/promotions/detail')->assertStatus(422)
            ->assertJsonPath('data.echelon_id.0', 'ID eselon harus dikirim');
    });

    it('preserves mapped prefix collision and unmapped fall-through authorization', function () {
        phaseTwentyOneActingAs(['Rekapitulasi - Pegawai ASN']);
        $this->getJson('/api/recapitulations-asn')->assertOk();
        $this->getJson('/api/recapitulations-asn/1')->assertStatus(403)
            ->assertJsonPath('message', "Access denied. You don't have permission to read Rekapitulasi - Komposisi Pegawai.");

        $user = phaseTwentyOneActingAs([]);
        $this->getJson('/api/summaries?month=1')->assertOk();
        $this->getJson('/api/profile')->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.role_name', phaseTwentyOneConnection()->table('roles')->where('id', $user->role_id)->value('name'));
    });

    it('preserves Profile password and fake-upload success behavior', function () {
        Storage::fake('s3');
        $user = phaseTwentyOneActingAs([]);

        $newUsername = phaseTwentyOnePrefix();
        $newEmail = $newUsername.'@example.invalid';
        $this->post('/api/profile', [
            'username' => $newUsername,
            'email' => $newEmail,
            'old_password' => 'Password@123',
            'password' => 'Replacement@123',
            'password_confirmation' => 'Replacement@123',
            'password_confirmation_confirmation' => 'Replacement@123',
            'photo_profile' => UploadedFile::fake()->image('profile.png', 10, 10),
        ])->assertOk()->assertExactJson(['code' => 200, 'message' => 'Profil berhasil diupdate.', 'data' => null]);
        $row = phaseTwentyOneConnection()->table('users')->where('id', $user->id)->firstOrFail();
        expect($row->username)->toBe($newUsername)
            ->and($row->email)->toBe($newEmail)
            ->and(Hash::check('Replacement@123', $row->password))->toBeTrue()
            ->and($row->photo_profile)->not->toBeNull();
        Storage::disk('s3')->assertExists($row->photo_profile);
    });

    it('preserves Profile validation messages', function () {
        phaseTwentyOneActingAs([]);
        $this->postJson('/api/profile', ['username' => 'x', 'email' => 'bad'])
            ->assertStatus(422)
            ->assertJsonPath('data.username.0', 'Username tidak boleh kurang dari 5 karakter')
            ->assertJsonPath('data.email.0', 'Format email tidak sesuai.');
    });

    it('preserves Profile partial update and open transaction on wrong current password', function () {
        $user = phaseTwentyOneActingAs([]);
        $newEmail = $user->email;

        $partialUsername = phaseTwentyOnePrefix();
        $this->postJson('/api/profile', [
            'username' => $partialUsername,
            'email' => $newEmail,
            'old_password' => 'wrong-password',
            'password' => 'AnotherPass@123',
            'password_confirmation' => 'AnotherPass@123',
            'password_confirmation_confirmation' => 'AnotherPass@123',
        ])->assertStatus(400)->assertExactJson([
            'code' => 400, 'message' => 'Password saat ini tidak sesuai.', 'data' => null,
        ]);
        expect(phaseTwentyOneConnection()->table('users')->where('id', $user->id)->value('username'))->toBe($partialUsername)
            ->and(phaseTwentyOneConnection()->transactionLevel())->toBe(2);
    });

    it('preserves the undefined-data failure for an unmapped recapitulation drill-down combination', function () {
        phaseTwentyOneActingAs(phaseTwentyOneAllReadPermissions());
        $this->getJson('/api/recapitulations-employee?page=unknown')->assertStatus(400)
            ->assertJsonPath('code', 400)
            ->assertJsonPath('message', 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
    });
});
