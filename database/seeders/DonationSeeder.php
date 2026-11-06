<?php

namespace Database\Seeders;

use App\Models\Donation;
use App\Models\User;
use Illuminate\Database\Seeder;

class DonationSeeder extends Seeder
{
    public function run(): void
    {
        $donor = User::where('email', 'pendonor@bloodconnect.id')->first();
        $officer = User::where('email', 'admin.pmi@bloodconnect.id')->first();
        if (! $donor) {
            return;
        }

        foreach ([[20, 'KB-0001'], [110, 'KB-0002'], [200, 'KB-0003']] as [$daysAgo, $code]) {
            Donation::firstOrCreate(
                ['blood_bag_code' => $code],
                [
                    'user_id'        => $donor->id,
                    'recorded_by'    => $officer?->id,
                    'blood_type'     => 'O',
                    'donation_date'  => now()->subDays($daysAgo)->toDateString(),
                    'volume_ml'      => 350,
                    'status'         => Donation::STATUS_TERCATAT,
                ]
            );
        }
    }
}