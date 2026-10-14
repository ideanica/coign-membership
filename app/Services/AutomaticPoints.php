<?php

namespace App\Services;

use App\Models\PointRule;

class AutomaticPoints
{
    public function award(int $amount): array
    {
        $settings = app(MembershipSettings::class);
        if (($settings->all()['points_mode'] ?? 'ratio') === 'tiers') {
            $rule = PointRule::where('is_active', true)->where('minimum_amount', '<=', $amount)->orderByDesc('minimum_amount')->first();

            return ['points' => $rule?->points ?? 0, 'description' => $rule ? 'Aturan '.$rule->name.': minimal Rp'.number_format($rule->minimum_amount, 0, ',', '.').' mendapat '.$rule->points.' poin.' : 'Pembelian belum memenuhi aturan poin aktif.'];
        }

        return ['points' => intdiv($amount, $settings->rupiahPerPoint()), 'description' => 'Poin otomatis: setiap Rp'.number_format($settings->rupiahPerPoint(), 0, ',', '.').' mendapat 1 poin.'];
    }
}
