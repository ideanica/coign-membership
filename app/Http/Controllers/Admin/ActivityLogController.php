<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\MembershipSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $areas = ActivityLog::visibleAreas($request->user());
        $values = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'actor' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', Rule::in(array_keys($areas))],
            'action' => ['nullable', Rule::in(array_keys(ActivityLog::ACTIONS))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
        ]);
        $query = ActivityLog::visibleTo($request->user());
        if (filled($values['q'] ?? null)) {
            $query->where(fn ($builder) => $builder->whereLike('actor_name', '%'.$values['q'].'%')->orWhereLike('actor_email', '%'.$values['q'].'%'));
        }
        if (filled($values['actor'] ?? null)) {
            $query->where(fn ($builder) => $builder->whereLike('actor_name', '%'.$values['actor'].'%')->orWhereLike('actor_email', '%'.$values['actor'].'%'));
        }
        foreach (['area', 'action'] as $field) {
            if (filled($values[$field] ?? null)) {
                $query->where($field, $values[$field]);
            }
        }
        if (filled($values['from'] ?? null)) {
            $query->where('occurred_at', '>=', $values['from'].' 00:00:00');
        }
        if (filled($values['to'] ?? null)) {
            $query->where('occurred_at', '<', Carbon::parse($values['to'])->addDay()->startOfDay());
        }

        return view('admin.activity-logs.index', [
            'logs' => $query->orderByDesc('occurred_at')->orderByDesc('id')->paginate(20)->withQueryString(),
            'areas' => $areas, 'actions' => ActivityLog::ACTIONS,
            'settings' => app(MembershipSettings::class)->all(),
        ]);
    }
}
