<?php

use App\Console\Commands\CleanExpiredSessions;
use App\Helpers\Responser;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDeviceSession;
use App\Repositories\CreditRepository;
use App\Repositories\NoteRepository;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

it('preserves the shared response envelope', function () {
    $responder = new class
    {
        use Responser;
    };

    $response = $responder->response(201, 'created', ['id' => 10]);

    expect($response->getStatusCode())->toBe(201)
        ->and($response->getData(true))->toBe([
            'code' => 201,
            'message' => 'created',
            'data' => ['id' => 10],
        ]);
});

it('preserves the legacy pagination envelope and link shape', function () {
    $responder = new class
    {
        use Responser;
    };
    $paginator = new LengthAwarePaginator(
        items: [['id' => 11], ['id' => 12]],
        total: 5,
        perPage: 2,
        currentPage: 2,
        options: ['path' => 'https://simdatuk.test/api/items'],
    );

    $response = $responder->paginateResponse(data: $paginator);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true))->toBe([
            'code' => 200,
            'message' => 'success',
            'data' => [['id' => 11], ['id' => 12]],
            'pagination' => [
                'total' => 5,
                'count' => 4,
                'per_page' => 2,
                'current_page' => 2,
                'total_pages' => 3,
                'links' => [
                    'first_page' => 'https://simdatuk.test/api/items?page=1',
                    'last_page' => 'https://simdatuk.test/api/items?page=3',
                    'next_page' => 'https://simdatuk.test/api/items?page=3',
                    'prev_page' => 'https://simdatuk.test/api/items?page=1',
                ],
            ],
        ]);
});

it('preserves core model fillable fields casts and relationships', function () {
    $user = new User;
    $role = new Role;
    $permission = new Permission;
    $deviceSession = new UserDeviceSession(['last_activity_at' => '2026-09-14 10:30:00']);

    expect($user->getFillable())->toBe(['name', 'email', 'username', 'password', 'role_id'])
        ->and($user->getHidden())->toBe(['password', 'remember_token'])
        ->and(method_exists($user, 'createToken'))->toBeTrue()
        ->and($user->role())->toBeInstanceOf(BelongsTo::class)
        ->and($user->deviceSessions())->toBeInstanceOf(HasMany::class)
        ->and($role->users())->toBeInstanceOf(HasMany::class)
        ->and($role->permissions())->toBeInstanceOf(BelongsToMany::class)
        ->and($role->permissions()->getTable())->toBe('role_permissions')
        ->and($role->permissions()->getPivotColumns())->toContain('create', 'read', 'update', 'delete')
        ->and($permission->roles())->toBeInstanceOf(BelongsToMany::class)
        ->and($permission->roles()->getTable())->toBe('role_permissions')
        ->and($deviceSession->user())->toBeInstanceOf(BelongsTo::class)
        ->and($deviceSession->last_activity_at)->toBeInstanceOf(Carbon::class);
});

it('preserves deterministic device identifiers without activating device sessions', function () {
    config()->set('app.key', 'base64:phase-five-test-key');

    expect(UserDeviceSession::generateDeviceIdentifier('SIMDATUK Client', '203.0.113.9'))
        ->toBe(hash('sha256', 'SIMDATUK Client|203.0.113.9|base64:phase-five-test-key'));
});

it('compiles representative repository queries without connecting to the database', function () {
    $queries = DB::connection()->pretend(function (): void {
        app(NoteRepository::class)->getDetail(17);
        app(NoteRepository::class)->getDetailBulkUser([17, 18]);
        app(CreditRepository::class)->getDetail(17);
        app(CreditRepository::class)->getDetailBulkUser([17, 18]);
    });
    $sql = collect($queries)->pluck('query')->map(strtolower(...));

    expect($queries)->toHaveCount(4)
        ->and($sql[0])->toContain('"user_notes" as "un"')
        ->and($sql[0])->toContain('date_format(un.created_at')
        ->and($sql[1])->toContain('"un"."user_id" in (17, 18)')
        ->and($sql[2])->toContain('"user_credits" as "uc"')
        ->and($sql[2])->toContain('order by "year" desc, "period" desc')
        ->and($sql[3])->toContain('"uc"."user_id" in (17, 18)');
});

it('executes timestamp macro code paths only in database pretend mode', function () {
    $queries = DB::connection()->pretend(function (): void {
        DB::table('phase_five_probe')->insertTs(['name' => 'insert']);
        DB::table('phase_five_probe')->insertGetIdTs(['name' => 'insert id']);
        DB::table('phase_five_probe')->where('id', 1)->updateTs(['name' => 'update']);
        DB::table('phase_five_probe')->where('id', 2)->deleteTs();
    });

    expect($queries)->toHaveCount(4)
        ->and($queries[0]['query'])->toContain('insert into "phase_five_probe"')
        ->and($queries[0]['bindings'])->toHaveCount(2)
        ->and($queries[1]['query'])->toContain('insert into "phase_five_probe"')
        ->and($queries[1]['bindings'])->toHaveCount(2)
        ->and($queries[2]['query'])->toContain('update "phase_five_probe"')
        ->and($queries[2]['bindings'])->toHaveCount(3)
        ->and($queries[3]['query'])->toContain('update "phase_five_probe"')
        ->and($queries[3]['bindings'])->toHaveCount(2);
});

it('registers cleanup command and its schedule without executing cleanup', function () {
    $command = Artisan::all()['sessions:clean'] ?? null;
    $scheduledCommands = collect(app(Schedule::class)->events())
        ->map(fn ($event) => $event->command)
        ->filter();

    expect($command)->toBeInstanceOf(CleanExpiredSessions::class)
        ->and($command->getDefinition()->getOption('days')->getDefault())->toBe('30')
        ->and($scheduledCommands->contains(
            fn (string $command): bool => str_contains($command, 'sessions:clean'),
        ))->toBeTrue();
});
