<?php

namespace App\Http\Controllers\Pendonor;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Services\DonorEligibilityService;
use Illuminate\Http\Request;

class DonorHistoryController extends Controller
{
    public function index(Request $request, DonorEligibilityService $service)
    {
        $user = $request->user();

        $donations = Donation::where('user_id', $user->id)
            ->orderByDesc('donation_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('donasi.pendonor.riwayat', [
            'user'        => $user,
            'donations'   => $donations,
            'eligibility' => $service->check($user->id),
        ]);
    }
}