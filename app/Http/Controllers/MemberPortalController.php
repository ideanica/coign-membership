<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\MemberTracking;
use App\Models\TrackingStatus;
use App\Services\MembershipSettings;
use Illuminate\Http\Request;

class MemberPortalController extends Controller
{
    public function tracking(Request $request)
    {
        $summary = [
            'total' => $request->user()->trackings()->count(),
            'active' => $request->user()->trackings()->whereNull('closed_at')->count(),
            'closed' => $request->user()->trackings()->whereNotNull('closed_at')->count(),
        ];
        $query = $request->user()->trackings()->with(['status', 'service', 'bill', 'latestEvent'])->withCount('events')->latest();
        if ($request->filled('q')) {
            $search = '%'.mb_substr((string) $request->query('q'), 0, 100).'%';
            $query->where(fn ($builder) => $builder->whereLike('reference', $search)->orWhereLike('public_code', $search)->orWhereLike('title', $search));
        }
        if ($request->filled('status')) {
            $query->where('tracking_status_id', $request->integer('status'));
        }

        return view('membership.tracking.index', ['summary' => $summary, 'trackings' => $query->paginate(12)->withQueryString(), 'statuses' => TrackingStatus::orderBy('sort_order')->get(), 'settings' => app(MembershipSettings::class)->all()]);
    }

    public function trackingDetail(Request $request, MemberTracking $tracking)
    {
        abort_unless($tracking->user_id === $request->user()->id, 404);

        return view('membership.tracking.show', ['tracking' => $tracking->load(['status', 'service', 'events', 'bill.user', 'bill.service', 'bill.tracking', 'bill.transaction.pointEntry']), 'settings' => app(MembershipSettings::class)->all()]);
    }

    public function bills(Request $request)
    {
        $query = $request->user()->bills()->with('tracking')->latest();
        if (in_array($request->query('status'), array_keys(Bill::STATUS_LABELS), true)) {
            $query->where('status', $request->query('status'));
        }

        return view('membership.bills.index', ['bills' => $query->paginate(15)->withQueryString(), 'settings' => app(MembershipSettings::class)->all()]);
    }

    public function billDetail(Request $request, Bill $bill)
    {
        abort_unless($bill->user_id === $request->user()->id, 404);

        return view('shared.bill', ['bill' => $bill->load(['user', 'service', 'tracking', 'transaction.pointEntry']), 'settings' => app(MembershipSettings::class)->all()]);
    }
}
