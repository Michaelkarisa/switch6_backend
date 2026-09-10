<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ApiResponseService as Api;
use App\Services\DownloadService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadController extends Controller
{
    public function __construct(private DownloadService $downloads) {}

    /** GET /v1/download/{filename} */
    public function download(string $filename): BinaryFileResponse|JsonResponse
    {
        $file = $this->downloads->findPublicApk($filename);

        if (! $file) {
            return Api::notFound('File not found');
        }

        return response()->download($file['path'], $file['filename'], [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }

    /** GET /v1/download/{filename?} — defaults to the current app build if omitted */
    public function downloadApk(?string $filename = null): BinaryFileResponse|JsonResponse
    {
        return $this->download($filename ?: 'Switch6.apk');
    }
}
