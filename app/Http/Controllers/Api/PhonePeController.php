<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class PhonePeController extends Controller
{
     public function initiate(Request $request)
    {
        $merchantId = "PGTESTPAYUAT"; // replace with PhonePe test merchant ID
        $saltKey = "e0b9b94e-8c48-4a13-a1c4-41354d8b3212"; // example test salt key
        $saltIndex = 1;
        $apiEndpoint = "/pg/v1/pay";
        $baseUrl = "https://api-preprod.phonepe.com/apis/pg-sandbox";

        $payload = [
            "merchantId" => $merchantId,
            "merchantTransactionId" => "MT" . time(),
            "merchantUserId" => "MUID123",
            "amount" => 10000,
            "redirectUrl" => "https://example.com/redirect",
            "redirectMode" => "REDIRECT",
            "callbackUrl" => "https://example.com/callback",
            "mobileNumber" => "9999999999",
            "paymentInstrument" => [
                "type" => "PAY_PAGE"
            ]
        ];

        $base64Payload = base64_encode(json_encode($payload));
        $checksum = hash('sha256', $base64Payload . $apiEndpoint . $saltKey);
        $xVerify = $checksum . "###" . $saltIndex;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-VERIFY' => $xVerify
        ])->post($baseUrl . $apiEndpoint, [
            'request' => $base64Payload
        ]);

        dd($response);

        return response()->json($response->json());
    }

}
