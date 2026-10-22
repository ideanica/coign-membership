<?php

namespace App\Services;

use App\Models\MemberTracking;
use App\Models\TrackingStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TrackingWorkflow
{
    public function create(array $attributes, User $actor, ?string $note, ?array $billing = null): MemberTracking
    {
        return DB::transaction(function () use ($attributes, $actor, $note, $billing): MemberTracking {
            $status = TrackingStatus::findOrFail($attributes['tracking_status_id']);
            $tracking = MemberTracking::create([...$attributes, 'reference' => $attributes['reference'] ?? 'TRK-'.Str::ulid(), 'closed_at' => $status->is_final ? now() : null]);
            $tracking->events()->create(['actor_id' => $actor->id, 'status_label' => $status->label, 'status_color' => $status->color, 'note' => $note ?: 'Tracking dibuat.']);
            if ($billing !== null) {
                $customer = $tracking->user;
                $settings = app(MembershipSettings::class)->all();
                $items = array_map(fn (array $item): array => [
                    'name' => $item['name'],
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (int) $item['unit_price'],
                ], $billing['items'] ?? [[
                    'name' => $tracking->service?->name ?? $tracking->title,
                    'quantity' => $billing['quantity'], 'unit_price' => $billing['unit_price'],
                ]]);
                $subtotal = array_sum(array_map(fn (array $item): int => $item['quantity'] * $item['unit_price'], $items));
                $tracking->bill()->create([
                    'user_id' => $tracking->user_id, 'service_id' => $tracking->service_id,
                    'reference' => 'BILL-'.Str::ulid(), 'title' => $tracking->title,
                    'items' => $items,
                    'quantity' => count($items) === 1 ? $items[0]['quantity'] : 1,
                    'unit_price' => count($items) === 1 ? $items[0]['unit_price'] : $subtotal,
                    'discount' => $billing['discount'],
                    'total' => $subtotal - $billing['discount'],
                    'due_date' => $billing['bill_due_date'] ?? null,
                    'customer_name' => $customer?->name ?? $billing['customer_name'],
                    'customer_email' => $customer?->email ?? ($billing['customer_email'] ?? null),
                    'customer_number' => $customer?->member_number,
                    'service_name' => $tracking->service?->name ?? 'Layanan khusus',
                    'issuer_name' => $settings['brand_name'] ?? 'Coignitive',
                    'issuer_email' => $settings['support_email'] ?? null, 'issued_by' => $actor->id,
                ]);
            }

            return $tracking;
        });
    }

    public function update(MemberTracking $tracking, array $attributes, User $actor, ?string $note): void
    {
        DB::transaction(function () use ($tracking, $attributes, $actor, $note): void {
            $locked = MemberTracking::query()->whereKey($tracking->id)->lockForUpdate()->firstOrFail();
            if ($locked->bill()->exists() && array_key_exists('service_id', $attributes) && (int) $attributes['service_id'] !== (int) $locked->service_id) {
                throw ValidationException::withMessages(['service_id' => 'Layanan pesanan sudah tercatat pada bill dan tidak dapat diganti.']);
            }
            $status = TrackingStatus::findOrFail($attributes['tracking_status_id']);
            $changed = $locked->tracking_status_id !== $status->id || ($locked->closed_at !== null) !== $status->is_final;
            $closedAt = $status->is_final ? ($locked->closed_at ?? now()) : null;
            $locked->fill([...$attributes, 'closed_at' => $closedAt])->save();
            if ($changed || filled($note)) {
                $locked->events()->create(['actor_id' => $actor->id, 'status_label' => $status->label, 'status_color' => $status->color, 'note' => $note ?: 'Status tracking diperbarui.']);
            }
        });
    }
}
