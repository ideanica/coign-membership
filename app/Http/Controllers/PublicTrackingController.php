<?php

namespace App\Http\Controllers;

use App\Models\MemberTracking;
use App\Services\MembershipSettings;
use Illuminate\Http\Request;

class PublicTrackingController extends Controller
{
    public function index()
    {
        return $this->page();
    }

    public function lookup(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'max:100']]);
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        $values = $request->validate(['code' => ['required', 'string', 'max:24', 'regex:/^ORD-[A-Z0-9]{16}$/']]);
        $tracking = MemberTracking::where('public_code', $values['code'])->where('public_enabled', true)->with(['status', 'service', 'events'])->first();
        $result = $tracking ? [
            'title' => $tracking->title,
            'service' => $tracking->service?->name ?? 'Layanan khusus',
            'status' => $tracking->status->label,
            'color' => $tracking->status->color,
            'due_date' => $tracking->due_date,
            'closed_at' => $tracking->closed_at,
            'events' => $tracking->events->map(fn ($event) => ['label' => $event->status_label, 'color' => $event->status_color, 'date' => $event->created_at]),
        ] : null;

        return $this->page(['searched' => true, 'result' => $result, 'code' => $values['code']]);
    }

    private function page(array $data = [])
    {
        return response()->view('tracking-public', [...$data, 'settings' => app(MembershipSettings::class)->all()])
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
