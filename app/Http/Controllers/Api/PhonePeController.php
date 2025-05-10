<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PhonePeController extends Controller
{
    public function initiate(Request $request)
    {
        $merchantId   = config('app.phonepe.merchant_id');
        $saltKey      = config('app.phonepe.salt_key');
        $saltIndex    = config('app.phonepe.salt_index');
        $redirectUrl  = config('app.phonepe.redirect_url');
        $callbackUrl  = config('app.phonepe.callback_url');
        $env          = config('app.phonepe.env');


        $amount = (int) $request->amount; 
        $transactionId = uniqid('TXN_');

        $payload = [
            'merchantId' => $merchantId,
            'merchantTransactionId' => $transactionId,
            'merchantUserId' => 'M22CCW231A75L',
            'amount' => $amount,
            'redirectUrl' => $redirectUrl,
            'redirectMode' => 'POST',
            'callbackUrl' => $callbackUrl,
            'paymentInstrument' => [
                'type' => 'PAY_PAGE'
            ]
        ];
// dd($payload);

        $jsonPayload    = json_encode($payload);
        $base64Payload  = base64_encode($jsonPayload);
        $stringToHash   = $base64Payload . "/pg/v1/pay" . $saltKey;
        $xVerify        = hash('sha256', $stringToHash) . "###" . $saltIndex;

        $url = $env === 'prod'
            ? "https://api.phonepe.com/apis/hermes/pg/v1/pay"
            : "https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay";

        // 🔍 Log the environment setup
        Log::info('PhonePe Initiating Payment', [
            'env' => $env,
            'transactionId' => $transactionId,
            'merchantId' => $merchantId,
            'saltIndex' => $saltIndex,
            'amount' => $amount,
            'redirectUrl' => $redirectUrl,
            'callbackUrl' => $callbackUrl
        ]);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-VERIFY' => $xVerify,
                'X-MERCHANT-ID' => $merchantId
            ])->post($url, [
                'request' => $base64Payload
            ]);

            $responseData = $response->json();
            $paymentLink = $responseData['data']['instrumentResponse']['redirectInfo']['url'] ?? null;

            Log::info('PhonePe Response', [
                'transactionId' => $transactionId,
                'body' => $response->body()
            ]);

            return response()->json([
                'success' => true,
                'transactionId' => $transactionId,
                'paymentLink' => $paymentLink,
                'rawResponse' => $responseData
            ]);
        } catch (\Exception $e) {
            Log::error('PhonePe Payment Error', [
                'transactionId' => $transactionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'transactionId' => $transactionId,
                'paymentLink' => null,
                'message' => 'Payment initiation failed. Check logs or credentials.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function callback(Request $request)
    {
        Log::info("📥 PhonePe Callback Received", $request->all());

        return response()->json(['status' => 'callback received']);
    }
}
