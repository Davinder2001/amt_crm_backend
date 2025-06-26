<?php

namespace App\Http\Controllers\Api;

use App\Models\Package;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\PhonePePaymentService;
use Carbon\Carbon;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\SelectedCompanyService;
use Illuminate\Support\Facades\Validator;

use App\Http\Resources\PaymentAndBillingResource;

class PaymentAndBillingController extends Controller
{
    /**
     * Get the billing information for the currently authenticated user.
     *
     */
    public function adminBilling()
    {
        $userId = Auth::id();

        $payments = Payment::where('user_id', $userId)->get();

        return response()->json([
            'success'  => true,
            'payments' => PaymentAndBillingResource::collection($payments),
        ]);
    }


    /**
     * Upgrade the package for the currently selected company.
     *
     */

    public function upgradePackage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'package_id'   => 'required|exists:packages,id',
            'package_type' => 'required|in:monthly,annual,three_years',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors'  => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        $activeCompany     = SelectedCompanyService::getSelectedCompanyOrFail();
        $currentPackageId  = $activeCompany->company->package_id;
        $company           = $activeCompany->company;
        $newPackageId      = $data['package_id'];
        $selectedType      = $data['package_type'];

        $newPackage = Package::findOrFail($newPackageId);

        $price = match ($selectedType) {
            'monthly'     => $newPackage->monthly_price,
            'annual'      => $newPackage->annual_price,
            'three_years' => $newPackage->three_years_price,
        };

        $merchantOrderId = 'UPGRADE_' . uniqid();
        $amount = (int) ($price * 100);

        $oauthResponse = Http::asForm()->post(env('PHONEPE_OAUTH_URL'), [
            'client_id'      => env('PHONEPE_CLIENT_ID'),
            'client_version' => env('PHONEPE_CLIENT_VERSION'),
            'client_secret'  => env('PHONEPE_CLIENT_SECRET'),
            'grant_type'     => env('PHONEPE_GRANT_TYPE'),
        ]);

        if (!$oauthResponse->ok() || !$oauthResponse->json('access_token')) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get PhonePe access token',
                'details' => $oauthResponse->json()
            ], 500);
        }

        $accessToken = $oauthResponse->json('access_token');

        $host = request()->getHost();

        if (str_contains($host, 'localhost')) {
            $baseUrl     = env('PHONEPE_CALLBACK_BASE_URL_COMPANY');
            $callbackUrl = env('PHONEPE_CALLBACK_BASE_URL');
        } elseif (str_contains($host, 'amt.sparkweb.co.in')) {
            $baseUrl     = env('PHONEPE_CALLBACK_BASE_URL_COMPANY_PROD');
            $callbackUrl = env('PHONEPE_CALLBACK_BASE_URL_PROD');
        } else {
            $baseUrl     = env('PHONEPE_CALLBACK_BASE_URL_COMPANY_PROD');
            $callbackUrl = env('PHONEPE_CALLBACK_BASE_URL_PROD');
        }

        $callbackUrl = $callbackUrl . "/api/v1/upgrade-package/{$merchantOrderId}";
        // $redirectUrl = $baseUrl . "/confirm-upgrade-payment/?orderId={$merchantOrderId}";
        $redirectUrl = "http://localhost:3000/confirm-upgrade-payment/?orderId={$merchantOrderId}";

        $checkoutPayload = [
            "merchantOrderId" => $merchantOrderId,
            "amount"          => $amount,
            "paymentFlow"     => [
                "type" => "PG_CHECKOUT",
                "merchantUrls" => [
                    "redirectUrl" => $redirectUrl,
                    "callbackUrl" => $callbackUrl
                ]
            ]
        ];

        $checkoutResponse = Http::withHeaders([
            'Authorization' => 'O-Bearer ' . $accessToken,
            'Content-Type'  => 'application/json',
        ])->post(env('PHONEPE_CHECKOUT_URL'), $checkoutPayload);

        $responseData = $checkoutResponse->json();
        $paymentUrl   = $responseData['redirectUrl'] ?? $responseData['data']['redirectUrl'] ?? null;

        if (!$checkoutResponse->ok() || !$paymentUrl) {
            return response()->json([
                'success'  => false,
                'message'  => 'Failed to initialize payment',
                'response' => $responseData
            ], 500);
        }

        return response()->json([
            'success'         => true,
            'package_name'    => $newPackage->name,
            'package_type'    => $selectedType,
            'price'           => $price,
            'merchantOrderId' => $merchantOrderId,
            'redirect_url'    => $paymentUrl
        ]);
    }



    public function confirmUpgradePackage($order_id)
    {
        $paymentStatusData = app(PhonePePaymentService::class)->checkAndUpdateStatus($order_id);

        return response()->json([
            'success' => true,
            'message' => 'Package upgrade confirmed successfully.',
            'data'    => [
                'merchantOrderId'  => $order_id,
                'payment_status'   => $paymentStatusData['status'] ?? 'UNKNOWN',
                'payment_mode'     => $paymentStatusData['mode'] ?? null,
                'transaction_id'   => $paymentStatusData['transaction_id'] ?? null,
                'transaction_amt'  => $paymentStatusData['amount'] ?? null,
            ],
        ]);
    }




    /**
     * Request a refund for a payment by transaction ID.
     *
     */
    public function refundRequest(Request $request, $transaction_id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $superAdmins = User::withoutGlobalScopes()->role('super-admin')->get();

        if (!$superAdmins) {
            return response()->json([
                'success' => false,
                'message' => 'No super admins found to notify.',
            ], 404);
        };

        $payment = Payment::where('transaction_id', $transaction_id)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'No payment found for the given order ID.',
            ], 404);
        }

        if ($payment->payment_status !== 'COMPLETED') {
            return response()->json([
                'success' => false,
                'message' => 'Payment was not successfully completed. Refund cannot be requested.',
                'payment_status' => $payment->payment_status,
            ], 400);
        }


        if (!is_null($payment->refund)) {
            return response()->json([
                'success' => false,
                'message' => 'Refund has already been requested or processed.',
                'refund_status' => $payment->refund,
            ], 400);
        }

        $paymentDate     = $payment->payment_date;
        $now             = now()->startOfDay();
        $paymentDateOnly = Carbon::createFromFormat('d/m/Y', $paymentDate)->startOfDay();
        $diffInDays      = $paymentDateOnly->diffInDays($now);

        if ($diffInDays > 15) {
            return response()->json([
                'success' => false,
                'message' => 'Refund requests can only be made within 15 days of payment.',
                'payment_date' => $paymentDate,
            ], 400);
        }



        $payment->refund = 'request processed';
        $payment->refund_reason = $data['reason'];
        $payment->save();

        foreach ($superAdmins as $admin) {
            $admin->notify(new SystemNotification(
                'Refund Request Submitted',
                "A refund request was submitted for transaction #{$transaction_id}.",
                'warning',
                url("/admin/refunds/{$payment->id}")
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund request submitted successfully.',
            'data'    => $payment,
        ]);
    }


    /**
     * Get all refund requests with optional filters.
     *
     */
    public function getRefundRequests(Request $request)
    {
        $query = Payment::query();

        if ($request->has('status')) {
            $query->where('refund', $request->input('status'));
        } else {
            $query->whereNotNull('refund');
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $refunds = $query->orderByDesc('created_at')->get();

        return response()->json([
            'success' => true,
            'message' => 'Refund requests fetched successfully.',
            'data'    => $refunds,
        ]);
    }

    /**
     * Approve a refund request by transaction ID.
     *
     */
    public function approveRefundRequest($transactionId)
    {

        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found for the given transaction ID.',
            ], 404);
        }

        if ($payment->refund !== 'request processed') {
            return response()->json([
                'success' => false,
                'message' => 'Only processed refund requests can be approved.',
                'current_refund_status' => $payment->refund,
            ], 400);
        }

        $payment->refund = 'refund approved';
        $payment->save();

        if ($payment->user) {
            $payment->user->notify(new SystemNotification(
                'Refund Approved',
                "Your refund request for transaction #{$transactionId} has been approved.",
                'success',
                url("/user/payments/{$payment->id}")
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund request approved successfully.',
            'data'    => $payment,
        ]);
    }


    /**
     * Mark a refund as completed.
     *
     */
    public function markRefunded($transactionId)
    {
        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found for the given transaction ID.',
            ], 404);
        }

        if ($payment->refund !== 'refund approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved refund requests can be marked as refunded.',
                'current_refund_status' => $payment->refund,
            ], 400);
        }

        $payment->refund = 'refunded';
        $payment->save();

        if ($payment->user) {
            $payment->user->notify(new SystemNotification(
                'Refund Completed',
                "Your refund for transaction #{$transactionId} has been successfully processed.",
                'success',
                url("/user/payments/{$payment->id}")
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund marked as completed.',
            'data'    => $payment,
        ]);
    }

    /**
     * Decline a refund request.
     *
     */
    public function declineRefundRequest(Request $request, $transactionId)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found for the given transaction ID.',
            ], 404);
        }

        if ($payment->refund !== 'request processed') {
            return response()->json([
                'success' => false,
                'message' => 'Refund cannot be declined in current state.',
                'current_refund_status' => $payment->refund,
            ], 400);
        }

        $payment->refund = 'refund declined';
        $payment->decline_reason = $request->input('reason');
        $payment->save();

        if ($payment->user) {
            $payment->user->notify(new SystemNotification(
                'Refund Declined',
                "Your refund request for transaction #{$transactionId} has been declined. Reason: " . $request->input('reason'),
                'error',
                url("/user/payments/{$payment->id}")
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund request declined successfully.',
            'data'    => $payment,
        ]);
    }
}
