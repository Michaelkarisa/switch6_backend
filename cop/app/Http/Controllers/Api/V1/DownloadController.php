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

    /** GET /download/Switch6.apk */
    public function downloadApk(): BinaryFileResponse|JsonResponse
    {
        return $this->download('Switch6.apk');
    }
}
