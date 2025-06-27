<?php


namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class OtpService
{
    public static function send(string $number): array
    {
        $otp = rand(100000, 999999);
        $fullNumber = '91' . $number;

        $payload = [
            "integrated_number" => "918219678757",
            "content_type" => "template",
            "payload" => [
                "messaging_product" => "whatsapp",
                "type" => "template",
                "template" => [
                    "name" => "authentication",
                    "language" => [
                        "code"   => "en_US",
                        "policy" => "deterministic"
                    ],
                    "namespace" => "c448fd19_1766_40ad_b98d_bae2703feb98",
                    "to_and_components" => [
                        [
                            "to" => $fullNumber,
                            "components" => [
                                "body_1" => [
                                    "type"  => "text",
                                    "value" => $otp
                                ],
                                "button_1" => [
                                    "subtype" => "url",
                                    "type"    => "text",
                                    "value"   => "COPY"
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'authkey' => '451198A9qD8Lu26821c9a6P1'
        ])->post('https://api.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/bulk/', $payload);

        if ($response->successful()) {
            $requestId = $response['request_id'] ?? null;

            if ($requestId) {
                Cache::put("otp_{$requestId}", [
                    'otp' => $otp,
                    'number' => $number,
                ], now()->addMinutes(5));

                return [
                    'success' => true,
                    'request_id' => $requestId,
                    'number' => $fullNumber,
                    'otp' => $otp
                ];
            }
        }

        return [
            'success' => false,
            'error' => $response->json()
        ];
    }
}
