<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the body the Rust media server posts from its periodic metrics
 * tick (api/mod.rs -> ApiClient::report_views(), every METRICS_REPORT_INTERVAL_MS).
 *
 * The server sends `stream_key`, not `match_id` — the media server's stream
 * key *is* the match id (see api/mod.rs: "streamkey is the match_id"), so
 * this form request accepts that name and the controller maps it onto the
 * `match_id` column when writing the MatchViews row.
 */
class StoreStreamViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stream_key' => ['required', 'string', 'exists:matches,id'],
            // Laravel-assigned stream_sessions.id, echoed back for
            // traceability. Optional: create_session_awaited() on the Rust
            // side currently expects an integer id and stream_sessions.id
            // is a UUID, so this is often absent until that's reconciled.
            'session_id' => ['sometimes', 'nullable', 'string'],
            'platform'   => ['required', 'string', 'max:100'],
            'view_count' => ['required', 'integer', 'min:0'],
        ];
    }
}
