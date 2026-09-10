<?php

namespace App\Services\Admin;

use App\Models\AdEvent;
use App\Models\Payment;
use App\Models\Advertisement;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAdvertisementService
{
    public function __construct(private AuditLogService $audit) {}

    public function paginate(Request $request): LengthAwarePaginator
    {
        $showDeleted = filter_var($request->query('deleted'), FILTER_VALIDATE_BOOLEAN);

        return Advertisement::query()
            ->when($showDeleted, fn ($q) => $q->onlyTrashed())
            ->when($request->query('status'),    fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('file_type'), fn ($q, $v) => $q->where('file_type', $v))
            ->withCount('events')
            ->latest()
            ->paginate((int) $request->query('per_page', 20));
    }

    public function update(Advertisement $ad, array $data, ?Request $request = null): Advertisement
    {
        $ad->update($data);

        $this->audit->log('admin_ad_updated', 'advertisements', 'Admin updated advertisement',
            ['ad_id' => $ad->id], $ad, $request);

        return $ad->fresh();
    }

    public function setStatus(Advertisement $ad, string $status, ?Request $request = null): Advertisement
    {
        $ad->update(['status' => $status]);

        $this->audit->log('admin_ad_status_changed', 'advertisements', 'Admin changed ad status',
            ['ad_id' => $ad->id, 'status' => $status], $ad, $request);

        return $ad->fresh();
    }

    public function delete(Advertisement $ad, ?Request $request = null): void
    {
        $id = $ad->id;
        $ad->delete(); // soft delete

        $this->audit->log('admin_ad_deleted', 'advertisements', 'Admin soft-deleted advertisement',
            ['ad_id' => $id], null, $request);
    }

    public function restore(Advertisement $ad, ?Request $request = null): Advertisement
    {
        $ad->restore();

        $this->audit->log('admin_ad_restored', 'advertisements', 'Admin restored advertisement',
            ['ad_id' => $ad->id], $ad, $request);

        return $ad->fresh();
    }

    public function forceDelete(Advertisement $ad, ?Request $request = null): void
    {
        $id = $ad->id;
        $ad->forceDelete();

        $this->audit->log('admin_ad_force_deleted', 'advertisements', 'Admin permanently deleted advertisement',
            ['ad_id' => $id], null, $request);
    }

    public function analytics(Advertisement $ad): array
    {
        $events = AdEvent::where('advertisement_id', $ad->id)->get();

        return [
            'advertisement_id' => $ad->id,
            'title'            => $ad->title,
            'impressions'      => $events->where('event_type', 'injected')->count(),
            'plays'            => $events->where('event_type', 'played')->count(),
            'completions'      => $events->where('event_type', 'completed')->count(),
            'total_play_time'  => $events->sum('play_time'),
            'avg_viewers'      => round($events->avg('viewer_count') ?? 0),
            'by_period'        => $events->groupBy('period')->map->count(),
            'by_platform'      => $events->groupBy('platform')->map->count(),
        ];
    }

    public function paginatePayments(Request $request): LengthAwarePaginator
    {
        return Payment::with(['user:id,name,email', 'advertisement:id,title'])
            ->when($request->query('status'),  fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('user_id'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->query('from'),    fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->query('to'),      fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->paginate((int) $request->query('per_page', 20));
    }

    public function confirmPayment(Payment $payment, string $transactionCode, ?Request $request = null): Payment
    {
        if ($payment->status === 'completed') {
            abort(response()->json([
                'success' => false,
                'message' => 'Payment is already completed.',
                'data'    => null,
            ], 409));
        }

        $payment->markPaid($transactionCode);

        $this->audit->log('admin_payment_confirmed', 'payments', 'Admin confirmed ad payment',
            ['payment_id' => $payment->id, 'transaction_code' => $transactionCode],
            $payment, $request);

        return $payment->fresh()->load(['user', 'advertisement']);
    }

    public function refundPayment(Payment $payment, ?Request $request = null): Payment
    {
        $payment->update(['status' => 'refunded']);

        $this->audit->log('admin_payment_refunded', 'payments', 'Admin refunded ad payment',
            ['payment_id' => $payment->id, 'amount' => $payment->amount],
            $payment, $request);

        return $payment->fresh();
    }

    public function revenueBreakdown(): array
    {
        $base = Payment::where('status', 'completed');

        return [
            'total'      => (clone $base)->sum('amount'),
            'today'      => (clone $base)->whereDate('paid_at', today())->sum('amount'),
            'this_month' => (clone $base)->whereMonth('paid_at', now()->month)->sum('amount'),
            'currency'   => 'KES',
            'by_method'      => (clone $base)
                ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('payment_method')->get(),
            'pending_total'  => Payment::where('status', 'pending')->sum('amount'),
            'pending_count'  => Payment::where('status', 'pending')->count(),
        ];
    }
}
