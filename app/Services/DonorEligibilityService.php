<?php

namespace App\Services;

use App\Models\Donation;
use Carbon\Carbon;

class DonorEligibilityService
{
    public function check(int $userId, ?Carbon $today = null): array
    {
        $today = ($today ?? now())->copy()->startOfDay();
        $interval = (int) config('donor.min_donation_interval_days', 60);

        $last = Donation::tercatat()
            ->where('user_id', $userId)
            ->orderByDesc('donation_date')
            ->first();

        if (! $last) {
            return ['eligible' => true, 'last_date' => null, 'next_date' => null, 'days_left' => 0];
        }

        $next = $last->donation_date->copy()->startOfDay()->addDays($interval);

        return [
            'eligible'  => $today->gte($next),
            'last_date' => $last->donation_date,
            'next_date' => $next,
            'days_left' => $today->lt($next) ? (int) $today->diffInDays($next) : 0,
        ];
    }
}