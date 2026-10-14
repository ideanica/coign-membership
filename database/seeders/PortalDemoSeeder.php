<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\MemberTracking;
use App\Models\Service;
use App\Models\TrackingStatus;
use App\Models\User;
use App\Services\TrackingWorkflow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PortalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $member = User::where('email', 'dummy@coignitive.test')->first();
        $admin = User::where('is_admin', true)->where('is_active', true)->first();
        if (! $member || ! $admin) {
            $this->command?->warn('Dummy member and active admin are required. No records were created.');

            return;
        }

        DB::transaction(function () use ($member, $admin): void {
            $service = Service::firstOrCreate(['code' => 'DEMO-KONSULTASI'], ['name' => 'Konsultasi desain', 'description' => 'Diskusi kebutuhan desain dan rekomendasi solusi kreatif.', 'price' => 250000, 'delivery_days' => 3, 'is_active' => true]);
            $reference = 'DEMO-TRK-'.$member->id;
            $tracking = MemberTracking::where('reference', $reference)->first();
            if (! $tracking) {
                $start = TrackingStatus::where('is_active', true)->where('is_final', false)->orderBy('sort_order')->firstOrFail();
                $processing = TrackingStatus::where('label', 'Sedang diproses')->where('is_active', true)->first() ?? $start;
                $workflow = app(TrackingWorkflow::class);
                $tracking = $workflow->create(['user_id' => $member->id, 'service_id' => $service->id, 'tracking_status_id' => $start->id, 'reference' => $reference, 'title' => 'Sesi konsultasi desain', 'description' => 'Tracking contoh untuk sesi konsultasi dan penyusunan rekomendasi desain.', 'due_date' => now()->addDays(3)->toDateString()], $admin, 'Permintaan konsultasi sudah diterima.');
                $workflow->update($tracking, ['tracking_status_id' => $processing->id], $admin, 'Tim sedang menyiapkan materi dan jadwal sesi konsultasi.');
            }
            if ($service->price > 0) {
                Bill::firstOrCreate(['reference' => 'DEMO-BILL-'.$member->id], ['user_id' => $member->id, 'service_id' => $service->id, 'member_tracking_id' => $tracking->id, 'title' => 'Sesi konsultasi desain', 'quantity' => 1, 'unit_price' => $service->price, 'discount' => 0, 'total' => $service->price, 'due_date' => now()->addDays(3)->toDateString()]);
            }
        });
        $this->command?->info('Dummy tracking and unpaid bill are ready. Existing member points were not changed.');
    }
}
