<?php

namespace App\Support;

final class ExportLogo
{
    private static bool $cleanupRegistered = false;

    public static function dataUri(): string
    {
        return 'data:image/png;base64,'.self::encoded();
    }

    public static function path(): string
    {
        $encoded = self::encoded();
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'simdatuk-setneg-logo-'.substr(hash('sha256', $encoded), 0, 16).'.png';

        if (! is_file($path)) {
            file_put_contents($path, base64_decode($encoded, true));
        }

        if (! self::$cleanupRegistered) {
            register_shutdown_function([self::class, 'cleanup']);
            self::$cleanupRegistered = true;
        }

        return $path;
    }

    public static function cleanup(): void
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'simdatuk-setneg-logo-'.substr(hash('sha256', self::encoded()), 0, 16).'.png';

        if (is_file($path)) {
            @unlink($path);
        }
    }

    private static function encoded(): string
    {
        $encoded = file_get_contents(resource_path('export-assets/setneg-logo.png.base64'));

        if ($encoded === false || base64_decode(trim($encoded), true) === false) {
            throw new \RuntimeException('SIMDATUK export logo asset is unavailable or invalid.');
        }

        return trim($encoded);
    }
}
