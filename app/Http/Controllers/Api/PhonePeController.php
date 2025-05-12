<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class PhonePeController extends Controller
{
    public function initiate(Request $request)
    {
        $merchantId = "M22CCW231A75L";
        $saltKey = "82316b40-10d6-49ec-b455-7965b5aa2eae";
        $saltIndex = 1;
        $apiEndpoint = "/pg/v1/pay";
        $baseUrl = "https://api-preprod.phonepe.com/apis/pg-sandbox";

        $payload = [
            "merchantId" => $merchantId,
            "merchantTransactionId" => "MT" . time(),
            "merchantUserId" => "MUID123",
            "amount" => 10000, // in Paise
            "redirectUrl" => "https://webhook.site/redirect-url",
            "redirectMode" => "REDIRECT",
            "callbackUrl" => "https://webhook.site/callback-url",
            "mobileNumber" => "9999999999",
            "paymentInstrument" => [
                "type" => "PAY_PAGE"
            ]
        ];

        // Step 1: Encode to base64
        $jsonPayload = json_encode($payload);
        $base64Payload = base64_encode($jsonPayload);

        // Step 2: Compute X-VERIFY
        $checksum = hash('sha256', $base64Payload . $apiEndpoint . $saltKey);
        $xVerify = $checksum . "###" . $saltIndex;

        // Step 3: Send POST request
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-VERIFY' => $xVerify
        ])->post($baseUrl . $apiEndpoint, [
            'request' => $base64Payload
        ]);

        return response()->json($response->json());
    }

}
