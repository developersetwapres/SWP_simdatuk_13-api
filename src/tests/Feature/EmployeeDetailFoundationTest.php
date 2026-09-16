<?php

use App\Repositories\EmployeeRepository;
use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/** @return list<string> */
function phaseTwelveTables(): array
{
    return [
        'users',
        'positions',
        'grades',
        'employment_types',
        'echelons',
        'residences',
        'institutions',
    ];
}

/** @return list<string> */
function phaseTwelveProjectedFields(): array
{
    return [
        'id',
        'email',
        'office_email',
        'title_prefix',
        'name',
        'title_suffix',
        'photo_profile',
        'employee_id_number',
        'employee_registration_number',
        'place_of_birth',
        'date_of_birth',
        'religion',
        'gender',
        'marital_status',
        'marriage_date',
        'marriage_description',
        'employment_type_id',
        'grade_id',
        'grade_name',
        'grade_code',
        'grade_effective_date',
        'position_id',
        'position_name',
        'position_type',
        'position_effective_date',
        'echelon_id',
        'echelon_name',
        'echelon_effective_date',
        'institution_id',
        'institution_name',
        'education_level',
        'education_name',
        'education_year',
        'employee_id_card_number',
        'employee_id_card',
        'karisu_number',
        'id_tax',
        'employment_status',
        'quit_date',
        'id_number',
        'family_registration_number',
        'residence_id',
        'residence_name',
        'residence_description',
        'current_address',
        'home_phone_number',
        'mobile_phone',
        'office_address',
        'office_phone_number',
        'emergency_contact',
        'description',
        'type',
        'cpns_effective_date',
        'cpns_years_of_service',
        'years_of_service_total',
        'month_of_service_total',
        'pns_effective_date',
        'pns_years_of_service',
        'years_of_service_rank',
        'month_of_service_rank',
        'retirement_age',
        'retirement_age_years',
        'created_at',
    ];
}

function phaseTwelveConnection(): Connection
{
    return DB::connection('mysql');
}

function phaseTwelveRepository(): EmployeeRepository
{
    return app(EmployeeRepository::class);
}

function phaseTwelveExpectedServicePeriod(string $startDate, string $endDate): string
{
    $start = Carbon::parse($startDate)->startOfDay();
    $end = Carbon::parse($endDate)->startOfDay();
    $years = (int) $start->diffInYears($end);
    $months = (int) $start->diffInMonths($end) % 12;
    $anchor = $start->copy()->addYears($years)->addMonths($months);
    $days = (int) $anchor->diffInDays($end);

    return "{$years} Tahun, {$months} Bulan, {$days} Hari";
}

function phaseTwelveExpectedPositionMerged(object $user): string
{
    $connection = phaseTwelveConnection();
    $position = $connection->table('positions')
        ->where('id', $user->position_id)
        ->first(['id', 'name', 'parent_id']);
    $names = [];

    while ($position !== null) {
        $names[] = $position->name;

        if ($position->parent_id === null) {
            break;
        }

        $position = $connection->table('positions')
            ->where('id', $position->parent_id)
            ->where('entity', 1)
            ->first(['id', 'name', 'parent_id']);
    }

    if ($user->position_type == 2) {
        $names[0] = $names[0].' '.$user->echelon_name;
    }

    foreach ($names as $index => $name) {
        if ($index !== 0) {
            $names[$index] = str_replace('Kepala ', '', $name);
        }
    }

    if (count($names) > 1) {
        $names[array_key_last($names)] = 'Sekretariat Wakil Presiden';
    }

    return implode(', ', $names);
}

it('preserves the Employee index route while its core detail repository remains available', function () {
    $employeeRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => $route->uri() === 'api/employees' && in_array('GET', $route->methods(), true))
        ->map(fn ($route): array => [$route->methods()[0], $route->uri(), $route->getActionName()])
        ->values()
        ->all();

    expect($employeeRoutes)->toBe([[
        'GET',
        'api/employees',
        'App\\Http\\Controllers\\EmployeeController@index',
    ]]);
});

describe('Employee core detail repository on the guarded MySQL clone', function () {
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

        $connection = phaseTwelveConnection();

        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $cloneDatabase) {
            throw new RuntimeException('Refusing MySQL access: clone connection guard failed.');
        }

        $engines = $connection->table('information_schema.tables')
            ->where('table_schema', $cloneDatabase)
            ->whereIn('table_name', phaseTwelveTables())
            ->pluck('ENGINE')
            ->map(fn (?string $engine): string => strtoupper((string) $engine));

        if ($engines->count() !== count(phaseTwelveTables()) || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
            throw new RuntimeException('Refusing MySQL access: every Employee detail table must exist and use InnoDB.');
        }
    });

    afterEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') === '1') {
            DB::disconnect('mysql');
        }
    });

    it('projects the exact core field structure and master joins', function () {
        $connection = phaseTwelveConnection();
        $candidate = $connection->table('users as u')
            ->join('positions as p', 'u.position_id', '=', 'p.id')
            ->join('grades as g', 'u.grade_id', '=', 'g.id')
            ->join('echelons as e', 'u.echelon_id', '=', 'e.id')
            ->join('residences as r', 'u.residence_id', '=', 'r.id')
            ->join('institutions as i', 'u.institution_id', '=', 'i.id')
            ->select(
                'u.id',
                'u.employment_type_id',
                'p.name as position_name',
                'p.type as position_type',
                'g.name as grade_name',
                'g.code as grade_code',
                'e.name as echelon_name',
                'r.name as residence_name',
                'i.name as institution_name',
            )
            ->firstOrFail();

        $detail = phaseTwelveRepository()->getDetail($candidate->id);

        expect(array_keys((array) $detail))->toBe([...phaseTwelveProjectedFields(), 'position_merged'])
            ->and($detail->position_name)->toBe($candidate->position_name)
            ->and($detail->position_type)->toBe($candidate->position_type)
            ->and($detail->grade_name)->toBe($candidate->grade_name)
            ->and($detail->grade_code)->toBe($candidate->grade_code)
            ->and($detail->echelon_name)->toBe($candidate->echelon_name)
            ->and($detail->residence_name)->toBe($candidate->residence_name)
            ->and($detail->institution_name)->toBe($candidate->institution_name)
            ->and($detail->employment_type_id)->toBe($candidate->employment_type_id)
            ->and(property_exists($detail, 'employment_type_name'))->toBeFalse();
    });

    it('preserves null master joins and omits position ancestry without a Position', function () {
        $candidate = phaseTwelveConnection()->table('users')
            ->whereNull('position_id')
            ->whereNull('grade_id')
            ->whereNull('echelon_id')
            ->whereNull('institution_id')
            ->firstOrFail(['id']);

        $detail = phaseTwelveRepository()->getDetail($candidate->id);

        expect($detail->position_name)->toBeNull()
            ->and($detail->position_type)->toBeNull()
            ->and($detail->grade_name)->toBeNull()
            ->and($detail->grade_code)->toBeNull()
            ->and($detail->echelon_name)->toBeNull()
            ->and($detail->institution_name)->toBeNull()
            ->and(property_exists($detail, 'position_merged'))->toBeFalse();
    });

    it('has no orphaned core master references in the clone', function () {
        $connection = phaseTwelveConnection();
        $relations = [
            ['position_id', 'positions'],
            ['grade_id', 'grades'],
            ['echelon_id', 'echelons'],
            ['residence_id', 'residences'],
            ['institution_id', 'institutions'],
            ['employment_type_id', 'employment_types'],
        ];

        foreach ($relations as [$foreignKey, $table]) {
            $orphans = $connection->table('users as u')
                ->leftJoin("{$table} as related", "u.{$foreignKey}", '=', 'related.id')
                ->whereNotNull("u.{$foreignKey}")
                ->whereNull('related.id')
                ->count();

            expect($orphans)->toBe(0);
        }
    });

    it('executes both current and stopped service-period branches', function (bool $stopped) {
        $connection = phaseTwelveConnection();
        $candidate = $connection->table('users')
            ->whereNotNull('cpns_effective_date')
            ->whereNotNull('pns_effective_date')
            ->when($stopped, fn ($query) => $query->whereNotNull('quit_date'))
            ->when(! $stopped, fn ($query) => $query->whereNull('quit_date'))
            ->firstOrFail(['id', 'cpns_effective_date', 'pns_effective_date', 'quit_date']);
        $endDate = $candidate->quit_date ?? $connection->selectOne('SELECT CURRENT_DATE AS value')->value;

        $detail = phaseTwelveRepository()->getDetail($candidate->id);

        expect($detail->cpns_years_of_service)->toBe(phaseTwelveExpectedServicePeriod($candidate->cpns_effective_date, $endDate))
            ->and($detail->pns_years_of_service)->toBe(phaseTwelveExpectedServicePeriod($candidate->pns_effective_date, $endDate));
    })->with([false, true]);

    it('keeps service periods null when their source dates are null', function () {
        $candidate = phaseTwelveConnection()->table('users')
            ->whereNull('cpns_effective_date')
            ->whereNull('pns_effective_date')
            ->firstOrFail(['id']);

        $detail = phaseTwelveRepository()->getDetail($candidate->id);

        expect($detail->cpns_effective_date)->toBeNull()
            ->and($detail->cpns_years_of_service)->toBeNull()
            ->and($detail->pns_effective_date)->toBeNull()
            ->and($detail->pns_years_of_service)->toBeNull();
    });

    it('executes each employee-type retirement branch', function (int $type, int $fixedYears) {
        $connection = phaseTwelveConnection();
        $candidate = $connection->table('users as u')
            ->leftJoin('echelons as e', 'u.echelon_id', '=', 'e.id')
            ->where('u.type', $type)
            ->whereNotNull('u.date_of_birth')
            ->when($type === 1, fn ($query) => $query->whereNotNull('u.echelon_id')->whereNotNull('e.retirement_age'))
            ->select('u.id', 'u.date_of_birth', 'e.retirement_age')
            ->firstOrFail();
        $expectedYears = $type === 1 ? (int) $candidate->retirement_age : $fixedYears;
        $expectedDate = Carbon::parse($candidate->date_of_birth)
            ->addYears($expectedYears)
            ->addMonth()
            ->format('d-m-Y');

        $detail = phaseTwelveRepository()->getDetail($candidate->id);

        expect((int) $detail->retirement_age_years)->toBe($expectedYears)
            ->and($detail->retirement_age)->toBe($expectedDate);
    })->with([
        'ASN uses Echelon retirement age' => [1, 0],
        'Non ASN uses 58 years' => [2, 58],
        'Outsourcing uses 58 years' => [3, 58],
    ]);

    it('keeps retirement date null when the required data is missing', function () {
        $candidate = phaseTwelveConnection()->table('users')
            ->where(fn ($query) => $query->whereNull('date_of_birth')->orWhere(fn ($nested) => $nested->where('type', 1)->whereNull('echelon_id')))
            ->firstOrFail(['id']);

        expect(phaseTwelveRepository()->getDetail($candidate->id)->retirement_age)->toBeNull();
    });

    it('preserves Employee-specific functional Position ancestry formatting', function () {
        $candidate = phaseTwelveConnection()->table('users as u')
            ->join('positions as p', 'u.position_id', '=', 'p.id')
            ->leftJoin('echelons as e', 'u.echelon_id', '=', 'e.id')
            ->where('p.type', 2)
            ->whereNotNull('p.parent_id')
            ->select('u.id', 'u.position_id', 'p.type as position_type', 'e.name as echelon_name')
            ->firstOrFail();

        $detail = phaseTwelveRepository()->getDetail($candidate->id);

        expect($detail->position_merged)->toBe(phaseTwelveExpectedPositionMerged($candidate))
            ->and($detail->position_merged)->toContain((string) $candidate->echelon_name);
    });

    it('transforms only non-null document paths and does not access S3', function () {
        $connection = phaseTwelveConnection();
        $withPhoto = $connection->table('users')->whereNotNull('photo_profile')->firstOrFail(['id', 'photo_profile']);
        $withoutPhoto = $connection->table('users')->whereNull('photo_profile')->firstOrFail(['id']);

        $normalDetail = phaseTwelveRepository()->getDetail($withPhoto->id);
        $exportDetail = phaseTwelveRepository()->getDetail($withPhoto->id, true);
        $nullDetail = phaseTwelveRepository()->getDetail($withoutPhoto->id);

        expect($normalDetail->photo_profile)->toBe(url('/api/image/'.ltrim($withPhoto->photo_profile, '/')))
            ->and($exportDetail->photo_profile)->toBe($normalDetail->photo_profile)
            ->and($nullDetail->photo_profile)->toBeNull()
            ->and($nullDetail->employee_id_card)->toBeNull();
    });

    it('returns null for a nonexistent employee', function () {
        $missingId = ((int) phaseTwelveConnection()->table('users')->max('id')) + 1_000_000;

        expect(phaseTwelveRepository()->getDetail($missingId))->toBeNull();
    });
});
