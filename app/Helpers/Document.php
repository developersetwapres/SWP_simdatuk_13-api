<?php

namespace App\Helpers;

trait Document
{
    public function getDocument(mixed $path = null, bool $status = false, bool $export = false): string
    {
        if (is_null($path)) {
            return asset('img/profile.jpg');
        }

        return url('/api/image/'.ltrim((string) $path, '/'));
    }
}
