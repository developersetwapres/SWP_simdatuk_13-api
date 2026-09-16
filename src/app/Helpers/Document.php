<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait Document
{
    public function uploadDocument(UploadedFile $file, string $directory, ?string $filename = null): string
    {
        $directory = '/'.$directory.'/';
        $extension = '.'.$file->getClientOriginalExtension();
        $filename = is_null($filename) ? Str::random(32).$extension : $filename.$extension;

        Storage::disk('s3')->putFileAs($directory, $file, $filename);

        return $directory.$filename;
    }

    public function getDocument(mixed $path = null, bool $status = false, bool $export = false): string
    {
        if (is_null($path)) {
            return asset('img/profile.jpg');
        }

        return url('/api/image/'.ltrim((string) $path, '/'));
    }
}
