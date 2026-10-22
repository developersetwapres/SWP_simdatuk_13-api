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

    public function getDocumentDataUri(string $path): string
    {
        $path = ltrim($path, '/');
        $disk = Storage::disk('s3');
        $contents = $disk->get($path);
        $mimeType = $disk->mimeType($path);

        if (! is_string($contents) || ! is_string($mimeType) || ! str_starts_with($mimeType, 'image/')) {
            throw new \RuntimeException("Unable to embed image document [{$path}] in PDF.");
        }

        return 'data:'.$mimeType.';base64,'.base64_encode($contents);
    }
}
