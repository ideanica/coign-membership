<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberTracking;
use App\Models\Service;
use App\Models\TrackingStatus;
use App\Models\User;
use App\Services\MembershipSettings;
use App\Services\TrackingWorkflow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TrackingController extends Controller
{
    public function index(Request $request)
    {
        $query = MemberTracking::with(['user', 'status', 'service'])->latest();
        if ($request->filled('q')) {
            $search = '%'.mb_substr((string) $request->query('q'), 0, 100).'%';
            $query->where(fn ($builder) => $builder->whereLike('reference', $search)->orWhereLike('title', $search)->orWhereHas('user', fn ($member) => $member->whereLike('name', $search)));
        }
        if ($request->filled('status')) {
            $query->where('tracking_status_id', $request->integer('status'));
        }

        return view('admin.tracking.index', ['trackings' => $query->paginate(15)->withQueryString(), 'statuses' => TrackingStatus::orderBy('sort_order')->get(), 'settings' => app(MembershipSettings::class)->all()]);
    }

    public function create()
    {
        return $this->form(new MemberTracking);
    }

    public function edit(MemberTracking $tracking)
    {
        return $this->form($tracking->load(['events', 'status']));
    }

    private function form(MemberTracking $tracking)
    {
        return view('admin.tracking.form', ['tracking' => $tracking, 'members' => User::where('is_admin', false)->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $tracking->user_id))->orderBy('name')->get(), 'services' => Service::where(fn ($query) => $query->where('is_active', true)->orWhere('id', $tracking->service_id))->orderBy('name')->get(), 'statuses' => TrackingStatus::where(fn ($query) => $query->where('is_active', true)->orWhere('id', $tracking->tracking_status_id))->orderBy('sort_order')->get(), 'settings' => app(MembershipSettings::class)->all()]);
    }

    private function values(Request $request, ?MemberTracking $tracking = null): array
    {
        $values = $request->validate([
            'user_id' => ['bail', 'nullable', 'integer', Rule::exists('users', 'id')->where('is_admin', false)->where('is_active', true)],
            'public_enabled' => ['sometimes', 'boolean'],
            'service_id' => ['bail', 'nullable', 'integer', Rule::exists('services', 'id')->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $tracking?->service_id))],
            'tracking_status_id' => ['bail', 'required', 'integer', Rule::exists('tracking_statuses', 'id')->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $tracking?->tracking_status_id))],
            'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:4000'], 'due_date' => ['nullable', 'date'], 'note' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($tracking) {
            unset($values['user_id']);
        }
        $values['public_enabled'] = $request->boolean('public_enabled');

        return $values;
    }

    public function store(Request $request, TrackingWorkflow $workflow)
    {
        $values = $this->values($request);
        $billing = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'unit_price' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'discount' => ['required', 'integer', 'min:0', 'max:10000000000000'],
            'bill_due_date' => ['nullable', 'date'],
            'customer_name' => [Rule::requiredIf(empty($values['user_id'])), 'nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
        ]);
        if ($billing['quantity'] * $billing['unit_price'] - $billing['discount'] < 1) {
            throw ValidationException::withMessages(['discount' => 'Diskon harus lebih kecil dari subtotal.']);
        }
        $note = $values['note'] ?? null;
        unset($values['note']);
        $tracking = $workflow->create($values, $request->user(), $note, $billing);

        return redirect()->route('admin.tracking.edit', $tracking)->with('success', 'Pesanan dan bill otomatis berhasil dibuat. Rincian bill sudah dikunci.');
    }

    public function update(Request $request, MemberTracking $tracking, TrackingWorkflow $workflow)
    {
        $values = $this->values($request, $tracking);
        $note = $values['note'] ?? null;
        unset($values['note']);
        $workflow->update($tracking, $values, $request->user(), $note);

        return redirect()->route('admin.tracking.edit', $tracking)->with('success', 'Tracking diperbarui. Perubahan status dan catatan tersimpan pada timeline.');
    }
}
