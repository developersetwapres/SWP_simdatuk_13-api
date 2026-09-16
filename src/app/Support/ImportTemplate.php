<?php

namespace App\Support;

final class ImportTemplate
{
    /** @var array<string, string> */
    private static array $paths = [];

    private static bool $cleanupRegistered = false;

    public static function path(string $name): string
    {
        if (isset(self::$paths[$name]) && is_file(self::$paths[$name])) {
            return self::$paths[$name];
        }

        $encodedPath = resource_path("import-templates/{$name}.xlsx.base64");
        $encoded = file_get_contents($encodedPath);
        $binary = $encoded === false ? false : base64_decode(trim($encoded), true);

        if ($binary === false) {
            throw new \RuntimeException("SIMDATUK import template {$name} is unavailable or invalid.");
        }

        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'simdatuk-import-'.hash('sha256', $name.$encoded).'.xlsx';
        if (! is_file($path) && file_put_contents($path, $binary) === false) {
            throw new \RuntimeException("SIMDATUK import template {$name} could not be materialized.");
        }

        self::$paths[$name] = $path;
        if (! self::$cleanupRegistered) {
            register_shutdown_function([self::class, 'cleanup']);
            self::$cleanupRegistered = true;
        }

        return $path;
    }

    public static function cleanup(): void
    {
        foreach (self::$paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        self::$paths = [];
    }
}
