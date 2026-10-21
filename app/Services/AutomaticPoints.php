<?php

namespace App\Services;

use App\Models\PointRule;

class AutomaticPoints
{
    public function award(int $amount): array
    {
        $settings = app(MembershipSettings::class);
        if (($settings->all()['points_mode'] ?? 'ratio') === 'tiers') {
            $rule = PointRule::where('is_active', true)->where('minimum_amount', '<=', $amount)->where(fn ($query) => $query->whereNull('maximum_amount')->orWhere('maximum_amount', '>=', $amount))->orderByDesc('minimum_amount')->first();

            $points = $rule?->pointsFor($amount) ?? 0;
            $description = 'Pembelian tidak memenuhi rentang aturan poin aktif.';
            if ($rule) {
                $range = 'Rp'.number_format($rule->minimum_amount, 0, ',', '.').' sampai '.($rule->maximum_amount === null ? 'tanpa batas atas' : 'Rp'.number_format($rule->maximum_amount, 0, ',', '.'));
                $calculation = $rule->reward_type === 'multiple'
                    ? $rule->points.' poin per kelipatan Rp'.number_format($rule->step_amount, 0, ',', '.').' dari total pembelian; batas '.number_format($rule->maximum_points ?? PointRule::MAX_POINTS, 0, ',', '.').' poin'
                    : 'hadiah tetap '.$rule->points.' poin';
                $description = 'Aturan '.$rule->name.': '.$range.'; '.$calculation.'. Hasil '.$points.' poin.';
            }

            return ['points' => $points, 'description' => $description];
        }

        return ['points' => intdiv($amount, $settings->rupiahPerPoint()), 'description' => 'Poin otomatis: setiap Rp'.number_format($settings->rupiahPerPoint(), 0, ',', '.').' mendapat 1 poin.'];
    }
}
