<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use App\Http\Resources\PaymentAndBillingResource;

class PaymentAndBillingController extends Controller
{
    public function adminBilling()
    {
        $userId = Auth::id();

        $payments = Payment::where('user_id', $userId)->get();

        return response()->json([
            'success'  => true,
            'payments' => PaymentAndBillingResource::collection($payments),
        ]);
    }
}
