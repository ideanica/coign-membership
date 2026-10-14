<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrackingStatus;
use App\Services\MembershipSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StatusController extends Controller
{
    public function index()
    {
        return view('admin.catalog.index', ['kind' => 'statuses', 'records' => TrackingStatus::orderBy('sort_order')->paginate(15), 'settings' => app(MembershipSettings::class)->all()]);
    }

    public function create()
    {
        return $this->form(new TrackingStatus);
    }

    public function edit(TrackingStatus $status)
    {
        return $this->form($status);
    }

    private function form(TrackingStatus $status)
    {
        return view('admin.catalog.form', ['kind' => 'statuses', 'record' => $status, 'settings' => app(MembershipSettings::class)->all()]);
    }

    private function values(Request $request, ?TrackingStatus $status = null): array
    {
        return $request->validate(['label' => ['required', 'string', 'max:100', Rule::unique('tracking_statuses')->ignore($status?->id)], 'color' => ['required', Rule::in(array_keys(TrackingStatus::COLORS))], 'sort_order' => ['required', 'integer', 'min:0', 'max:9999'], 'is_final' => ['required', 'boolean'], 'is_active' => ['required', 'boolean']]);
    }

    public function store(Request $request)
    {
        TrackingStatus::create($this->values($request));

        return redirect()->route('admin.statuses.index')->with('success', 'Label tracking ditambahkan.');
    }

    public function update(Request $request, TrackingStatus $status)
    {
        $status->update($this->values($request, $status));

        return redirect()->route('admin.statuses.index')->with('success', 'Label tracking diperbarui. Timeline lama mempertahankan label saat kejadian.');
    }

    public function destroy(TrackingStatus $status)
    {
        $status->update(['is_active' => false]);

        return back()->with('success', 'Label diarsipkan. Riwayat yang sudah ada tetap tersimpan.');
    }
}
