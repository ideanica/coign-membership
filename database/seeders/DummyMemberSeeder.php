<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\MembershipLedger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DummyMemberSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $user = User::firstOrCreate(['email' => 'dummy@coignitive.test'], [
                'name' => 'Aditya Pratama',
                'password' => 'MemberDemo2026!',
            ]);

            if (! Hash::check('MemberDemo2026!', $user->password)) {
                throw new RuntimeException('The dummy email already belongs to a different account.');
            }

            $user->forceFill([
                'member_number' => $user->member_number ?? 'CG-'.now()->format('Y').'-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
                'member_tier' => 'Gold Member',
                'photo_path' => 'images/member-avatar.svg',
            ])->save();

            $ledger = app(MembershipLedger::class);
            $purchases = [
                ['2026-10-01', 'Workshop Visual Storytelling', 'Workshop', 250000, 'Transfer bank'],
                ['2026-10-02', 'Coffee & Connect', 'Event', 150000, 'QRIS'],
                ['2026-10-04', 'Personal Branding Class', 'Kelas', 200000, 'QRIS'],
                ['2026-10-05', 'Co-working Day Pass', 'Workspace', 200000, 'Transfer bank'],
                ['2026-10-07', 'Community Meetup', 'Event', 150000, 'QRIS'],
                ['2026-10-09', 'Design Thinking Class', 'Kelas', 300000, 'QRIS'],
                ['2026-10-11', 'Co-working Day Pass', 'Workspace', 250000, 'Transfer bank'],
                ['2026-10-14', 'Workshop Creative Coding', 'Workshop', 350000, 'QRIS'],
            ];

            foreach ($purchases as [$date, $title, $category, $amount, $payment]) {
                $reference = 'DUMMY-'.$user->id.'-INV-'.$date;
                if (! $user->membershipTransactions()->where('reference', $reference)->exists()) {
                    $ledger->recordPurchase($user, compact('reference', 'title', 'category', 'amount', 'payment') + ['occurred_at' => $date.' 10:00:00']);
                }
            }

            foreach ([['2026-10-06', 'Voucher kopi Rp20.000', 200], ['2026-10-10', 'Voucher workshop Rp40.000', 400]] as [$date, $title, $points]) {
                $reference = 'DUMMY-'.$user->id.'-RDM-'.$date;
                if (! $user->pointEntries()->where('reference', $reference)->exists()) {
                    $ledger->redeem($user, compact('reference', 'title', 'points') + [
                        'category' => 'Voucher',
                        'description' => 'Contoh penukaran poin akun dummy.',
                        'occurred_at' => $date.' 12:00:00',
                    ]);
                }
            }

            $this->command?->info('Dummy member ready: dummy@coignitive.test (Gold Member).');
        });
    }
}
