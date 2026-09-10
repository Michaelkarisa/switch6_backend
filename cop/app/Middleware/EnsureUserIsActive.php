<?php
namespace App\Middleware;
use Closure;
use Illuminate\Http\Request;
class EnsureUserIsActive {
    public function handle(Request $request, Closure $next) {
        if (($request->user()?->status ?? null) !== 'active') {
            return response()->json(['success' => false, 'message' => 'Inactive account', 'data' => null], 403);
        }
        return $next($request);
    }
}
