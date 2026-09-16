<?php

namespace App\Providers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;

class QueryServiceProvider extends ServiceProvider
{
    /** @var list<string> */
    protected static array $methods = ['insertTs', 'insertGetIdTs', 'updateTs', 'deleteTs'];

    protected static function timestampValues(string $functionName, array $columnNames): void
    {
        Builder::macro($functionName, function (array $values, bool $withBy = false) use ($columnNames) {
            $userId = $withBy ? auth()->user()->id : null;

            if (array_key_exists(0, $values) && is_array($values[0])) {
                foreach ($values as &$value) {
                    $value[$columnNames[1]] = date('Y-m-d h:i:s');

                    if ($withBy) {
                        $value[$columnNames[2]] = $userId;
                    }
                }
            } else {
                $values[$columnNames[1]] = date('Y-m-d h:i:s');

                if ($withBy) {
                    $values[$columnNames[2]] = $userId;
                }
            }

            return Builder::{$columnNames[0]}($values);
        });
    }

    protected static function insertTs(): void
    {
        self::timestampValues(__FUNCTION__, ['insert', 'created_at', 'user_id']);
    }

    protected static function insertGetIdTs(): void
    {
        self::timestampValues(__FUNCTION__, ['insertGetId', 'created_at', 'user_id']);
    }

    protected static function updateTs(): void
    {
        Builder::macro(__FUNCTION__, function (array $values, bool $withBy = false) {
            $values['updated_at'] = date('Y-m-d h:i:s');

            if ($withBy) {
                $values['updated_by'] = auth()->user()->id;
            }

            return Builder::update($values);
        });
    }

    protected static function deleteTs(): void
    {
        Builder::macro(__FUNCTION__, function (bool $withBy = false) {
            $values = ['deleted_at' => date('Y-m-d h:i:s')];

            if ($withBy) {
                $values['deleted_by'] = auth()->user()->id;
            }

            return Builder::update($values);
        });
    }

    public function register(): void
    {
        foreach (self::$methods as $method) {
            self::{$method}();
        }
    }
}
