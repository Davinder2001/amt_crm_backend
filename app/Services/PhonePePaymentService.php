<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class PhonePePaymentService
{
    public function initiateCompanyPayment(array $data, string $merchantOrderId, int $amount): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'success' => false,
                'message' => 'Failed to get PhonePe access token',
            ];
        }

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

        $callbackUrl = "$callbackUrl/api/v1/add-new-company/{$merchantOrderId}";
        $redirectUrl = "$baseUrl/confirm-company-payment/?orderId={$merchantOrderId}";

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
            'Authorization' => 'O-Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ])->post(env('PHONEPE_CHECKOUT_URL'), $checkoutPayload);

        $responseData = $checkoutResponse->json();
        $paymentUrl   = $responseData['redirectUrl'] ?? $responseData['data']['redirectUrl'] ?? null;

        if (!$checkoutResponse->ok() || !$paymentUrl) {
            return [
                'success'  => false,
                'message'  => 'Failed to initialize payment',
                'response' => $responseData,
            ];
        }

        return [
            'success'         => true,
            'merchantOrderId' => $merchantOrderId,
            'redirect_url'    => $paymentUrl,
        ];
    }


    public function checkAndUpdateStatus(string $orderId): array
    {
        $token = $this->getAccessToken();

        if (!$token) {
            return [
                'success' => false,
                'message' => 'Missing PhonePe access token.',
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => 'O-Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ])->get(env('PHONEPE_STATUS_URL') . "/{$orderId}/status");

        if (!$response->ok()) {
            return [
                'success' => false,
                'message' => 'Failed to fetch status.',
                'details' => $response->json(),
            ];
        }

        $statusData         = $response->json();
        $status             = strtoupper($statusData['state'] ?? 'FAILED');
        $paymentMode        = $statusData['paymentDetails'][0]['paymentMode'] ?? null;
        $transactionId      = $statusData['orderId'] ?? $orderId;
        $transactionAmount  = $statusData['amount'] / 100;
        $nowIST             = Carbon::now('Asia/Kolkata');

        $failReason         = $status === 'FAILED'   ? ($statusData['failureReason'] ?? 'Unknown failure')       : null;
        $declineReason      = $status === 'DECLINED' ? ($statusData['declineReason'] ?? 'Declined by bank')      : null;
        $userId             = Auth::id();

        $payment = Payment::updateOrCreate(
            ['order_id' => $orderId],
            [
                'user_id'             => $userId,
                'transaction_id'      => $transactionId,
                'payment_status'      => $status,
                'payment_method'      => $paymentMode,
                'payment_reason'      => 'Auto-check status update',
                'payment_fail_reason' => $failReason,
                'decline_reason'      => $declineReason,
                'transaction_amount'  => $transactionAmount,
                'payment_date'        => $nowIST->format('d/m/Y'),
                'payment_time'        => $nowIST->format('h:i A'),
                'refund'              => null,
                'refund_reason'       => null,
            ]
        );

        return [
            'success'         => true,
            'status'          => $status,
            'mode'            => $paymentMode,
            'transaction_id'  => $transactionId,
            'amount'          => $transactionAmount,
            'payment'         => $payment,
        ];
    }

    private function getAccessToken(): ?string
    {
        $response = Http::asForm()->post(env('PHONEPE_OAUTH_URL'), [
            'client_id'      => env('PHONEPE_CLIENT_ID'),
            'client_version' => env('PHONEPE_CLIENT_VERSION'),
            'client_secret'  => env('PHONEPE_CLIENT_SECRET'),
            'grant_type'     => env('PHONEPE_GRANT_TYPE'),
        ]);

        return $response->json('access_token') ?? null;
    }
}
