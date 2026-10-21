<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\MembershipTransaction;
use App\Models\MemberTracking;
use App\Models\User;
use App\Services\MembershipSettings;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', [
            'stats' => ['members' => User::where('is_admin', false)->count(), 'active_tracking' => MemberTracking::whereNull('closed_at')->count(), 'unpaid_bills' => Bill::where('status', 'unpaid')->count(), 'revenue' => (int) MembershipTransaction::sum('amount') + (int) Bill::whereNull('user_id')->where('status', 'paid')->sum('total')],
            'trackings' => MemberTracking::with(['user', 'status', 'service'])->latest()->latest('id')->limit(5)->get(),
            'bills' => Bill::with('user')->where('status', 'unpaid')->latest()->limit(5)->get(),
            'settings' => app(MembershipSettings::class)->all(),
        ]);
    }
}
