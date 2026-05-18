<?php

namespace App\Services;

use App\Models\Advertisement;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class AdvertisementService
{
    public function __construct(
        private AdTargetingService $targeting,
        private AuditLogService    $audit,
    ) {}

    /**
     * List advertisements for the authenticated user.
     * Admins see all; advertisers/broadcasters see only their own.
     */
    public function listForUser(User $user)
    {
        $query = Advertisement::withCount('events')->latest();

        // Admins see everything — filter by creator for other roles
        if (! $user->hasRole('admin') && ! $user->hasRole('superadmin')) {
            $query->where('user_id', $user->id);
        }

        return $query->get();
    }

    /**
     * Create an advertisement from uploaded file or URL.
     */
    public function create(array $data, ?User $user = null): Advertisement
    {
        $payload = [
            'title'       => $data['title'],
            'file_type'   => $data['file_type'],
            'duration'    => $data['duration']    ?? 15,
            'period'      => $data['period']      ?? null,
            'end_date'    => $data['end_date']    ?? null,
            'status'      => 'pending', // active once payment confirmed
            'target_tags' => isset($data['target_tags']) ? (array) $data['target_tags'] : null,
            'user_id'     => $user?->id,
        ];

        // Handle file upload
        if (isset($data['file']) && $data['file'] instanceof \Illuminate\Http\UploadedFile) {
            $path = $data['file']->store('ads', 'public');
            $payload['file_path'] = $path;
        } else {
            $payload['file_path'] = $data['file_path'] ?? '';
        }

        $ad = Advertisement::create($payload);

        $this->audit->log('created', 'advertisements', 'Advertisement created', [
            'ad_id' => $ad->id,
            'title' => $ad->title,
        ], $ad, userId: $user?->id);

        return $ad;
    }

    public function delete(Advertisement $ad, ?User $user = null): void
    {
        // Delete stored file if it exists locally
        if ($ad->file_path && Storage::disk('public')->exists($ad->file_path)) {
            Storage::disk('public')->delete($ad->file_path);
        }

        $id = $ad->id;
        $ad->delete();

        $this->audit->log('deleted', 'advertisements', 'Advertisement deleted', [
            'ad_id' => $id,
        ], userId: $user?->id);
    }

    public function selectForMatch(?string $matchId, ?string $period): ?Advertisement
    {
        return $this->targeting->pickBestAd($matchId, $period);
    }
}
