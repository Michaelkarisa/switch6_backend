<?php

namespace App\Services;

class DownloadService
{
    public function findPublicApk(string $filename): ?array
    {
        $safe = basename($filename);
        $path = public_path($safe);

        if (! is_file($path)) return null;

        return ['path' => $path, 'filename' => $safe];
    }
}
