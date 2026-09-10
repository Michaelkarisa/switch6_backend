<?php

namespace App\Services;

use App\Models\Lineup;
use App\Models\MatchModel;
use App\Models\ShareLineup;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShareLineupService
{
    public function __construct(private AuditLogService $audit) {}

    /**
     * Share a saved lineup (identified by match_id + club_id, the team the
     * sender manages in that match) with another broadcaster.
     */
    public function shareLineup(User $user, array $data): ShareLineup
    {
        $hasLineup = Lineup::where('match_id', $data['match_id'])
            ->where('club_id', $data['club_id'])
            ->exists();

        if (! $hasLineup) {
            throw ValidationException::withMessages([
                'match_id' => ['No saved lineup found for this club on this match yet. Save the lineup first.'],
            ]);
        }

        $recipient = User::findOrFail($data['recepient_id']);

        if (! $recipient->hasRole('broadcaster')) {
            throw ValidationException::withMessages([
                'recepient_id' => ['Lineups can only be shared with broadcasters.'],
            ]);
        }

        $shared = ShareLineup::create([
            'club_id'       => $data['club_id'],
            'match_id'      => $data['match_id'],
            'sender_id'     => $user->id,
            'recepient_id'  => $recipient->id,
            'status'        => 'pending',
        ]);

        $this->audit->log('shared', 'share_lineup', 'Lineup shared with another broadcaster', [
            'share_id'     => $shared->id,
            'recepient_id' => $recipient->id,
        ], userId: $user->id);

        return $shared->load(['sender', 'recepient', 'club', 'match']);
    }

    /**
     * Lineups shared with me that I haven't imported yet — backs the
     * "Shared lineup" page on the matches page.
     */
    public function listForRecipient(User $user): Collection
    {
        return ShareLineup::with(['sender', 'club', 'match.homeClub', 'match.awayClub'])
            ->where('recepient_id', $user->id)
            ->latest()
            ->get();
    }

    /** Lineups I've shared with others. */
    public function listSent(User $user): Collection
    {
        return ShareLineup::with(['recepient', 'club', 'match.homeClub', 'match.awayClub'])
            ->where('sender_id', $user->id)
            ->latest()
            ->get();
    }

    /** Preview the players in a shared lineup before importing. */
    public function preview(ShareLineup $share): Collection
    {
        return Lineup::with('player')
            ->where('match_id', $share->match_id)
            ->where('club_id', $share->club_id)
            ->get();
    }

    /**
     * Import a shared lineup into one of the recipient's own matches, for
     * the same club. Prefills (upserts) the target match+club lineup with
     * the shared players/positions.
     */
    public function import(User $user, ShareLineup $share, string $targetMatchId): array
    {
        if ($share->recepient_id !== $user->id) {
            throw ValidationException::withMessages([
                'share' => ['This shared lineup was not sent to you.'],
            ]);
        }

        if ($share->status === 'imported') {
            throw ValidationException::withMessages([
                'share' => ['This shared lineup has already been imported.'],
            ]);
        }

        $targetMatch = MatchModel::findOrFail($targetMatchId);

        if (! in_array($share->club_id, [$targetMatch->home_club_id, $targetMatch->away_club_id], true)) {
            throw ValidationException::withMessages([
                'match_id' => ['The shared club is not part of the selected match.'],
            ]);
        }

        $sourceLineups = $this->preview($share);

        if ($sourceLineups->isEmpty()) {
            throw ValidationException::withMessages([
                'share' => ['The shared lineup no longer has any players saved.'],
            ]);
        }

        return DB::transaction(function () use ($user, $share, $targetMatch, $sourceLineups) {
            $now = now();

            $rows = $sourceLineups->map(fn ($lineup) => [
                'id'         => (string) Str::uuid(),
                'match_id'   => $targetMatch->id,
                'club_id'    => $share->club_id,
                'player_id'  => $lineup->player_id,
                'position'   => $lineup->position,
                'is_starter' => $lineup->is_starter,
                'minute_in'  => $lineup->minute_in,
                'minute_out' => $lineup->minute_out,
                'author_id'  => $user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            Lineup::upsert(
                $rows,
                ['match_id', 'player_id'],
                ['club_id', 'position', 'is_starter', 'minute_in', 'minute_out', 'updated_at']
            );

            $share->update([
                'status'                  => 'imported',
                'imported_at'             => $now,
                'imported_into_match_id'  => $targetMatch->id,
            ]);

            $this->audit->log('imported', 'share_lineup', 'Shared lineup imported into match', [
                'share_id'          => $share->id,
                'target_match_id'   => $targetMatch->id,
            ], userId: $user->id);

            return Lineup::with(['player', 'club'])
                ->where('match_id', $targetMatch->id)
                ->where('club_id', $share->club_id)
                ->get()
                ->all();
        });
    }

    public function destroy(User $user, ShareLineup $share): void
    {
        if (! in_array($user->id, [$share->sender_id, $share->recepient_id], true)) {
            throw ValidationException::withMessages([
                'share' => ['You are not part of this shared lineup.'],
            ]);
        }

        $share->delete();
    }

    /** Search broadcasters by name/email, for the "share with" picker. */
    public function searchBroadcasters(string $query, ?string $excludeUserId = null): Collection
    {
        return User::role('broadcaster')
            ->when($excludeUserId, fn ($q) => $q->where('id', '!=', $excludeUserId))
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($inner) use ($query) {
                    $inner->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                });
            })
            ->limit(20)
            ->get(['id', 'name', 'email']);
    }
}
