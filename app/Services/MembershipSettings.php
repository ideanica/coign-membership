<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MembershipSettings
{
    public function all(): array
    {
        return Cache::remember('membership.public-settings', 60, fn (): array => DB::table('site_settings')->pluck('value', 'key')->all());
    }

    public function rupiahPerPoint(): int
    {
        return max(1, (int) ($this->all()['rupiah_per_point'] ?? config('membership.rupiah_per_point')));
    }
}
