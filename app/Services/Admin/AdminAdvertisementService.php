<?php

namespace App\Services\Admin;

use App\Models\AdEvent;
use App\Models\AdPayment;
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
        return Advertisement::query()
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
        $ad->delete();

        $this->audit->log('admin_ad_deleted', 'advertisements', 'Admin deleted advertisement',
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
        return AdPayment::with(['user:id,name,email', 'advertisement:id,title'])
            ->when($request->query('status'),  fn ($q, $v) => $q->where('status', $v))
            ->when($request->query('user_id'), fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->query('from'),    fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->query('to'),      fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()
            ->paginate((int) $request->query('per_page', 20));
    }

    public function confirmPayment(AdPayment $payment, string $transactionCode, ?Request $request = null): AdPayment
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

    public function refundPayment(AdPayment $payment, ?Request $request = null): AdPayment
    {
        $payment->update(['status' => 'refunded']);

        $this->audit->log('admin_payment_refunded', 'payments', 'Admin refunded ad payment',
            ['payment_id' => $payment->id, 'amount_kes' => $payment->amount_kes],
            $payment, $request);

        return $payment->fresh();
    }

    public function revenueBreakdown(): array
    {
        $base = AdPayment::where('status', 'completed');

        return [
            'total_kes'      => (clone $base)->sum('amount_kes'),
            'today_kes'      => (clone $base)->whereDate('paid_at', today())->sum('amount_kes'),
            'this_month_kes' => (clone $base)->whereMonth('paid_at', now()->month)->sum('amount_kes'),
            'by_method'      => (clone $base)
                ->selectRaw('payment_method, SUM(amount_kes) as total, COUNT(*) as count')
                ->groupBy('payment_method')->get(),
            'pending_total'  => AdPayment::where('status', 'pending')->sum('amount_kes'),
            'pending_count'  => AdPayment::where('status', 'pending')->count(),
        ];
    }
}
