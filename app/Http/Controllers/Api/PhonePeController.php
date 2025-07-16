<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use PhonePe\payments\v2\standardCheckout\StandardCheckoutClient;
use PhonePe\payments\v2\models\request\builders\StandardCheckoutPayRequestBuilder;
use PhonePe\Env;
use PhonePe\common\exceptions\PhonePeException;
use App\Http\Controllers\Controller;

class PhonePeController extends Controller
{
    public function initiate(Request $request)
    {
        $clientId = "SU2505092014223491407849";
        $clientVersion = 1;
        $clientSecret = "82316b40-10d6-49ec-b455-7965b5aa2eae";
        $env = Env::PRODUCTION;

        $client = StandardCheckoutClient::getInstance(
            $clientId,
            $clientVersion,
            $clientSecret,
            $env
        );

        $merchantOrderId = 'ORDER_' . uniqid();
        $amount = $request->input('amount', 100);
        $redirectUrl = $request->input('redirectUrl', url('/payment/redirect'));
        $message = "Your order details";

        $payRequest = StandardCheckoutPayRequestBuilder::builder()
            ->merchantOrderId($merchantOrderId)
            ->amount($amount)
            ->redirectUrl($redirectUrl)
            ->message($message)
            ->build();

        try {
            $payResponse = $client->pay($payRequest);

            if ($payResponse->getState() === "PENDING") {
                return response()->json([
                    'success' => true,
                    'redirect_url' => $payResponse->getRedirectUrl(),
                    'order_id' => $merchantOrderId
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Payment initiation failed: " . $payResponse->getState()
                ], 400);
            }
        } catch (PhonePeException $e) {
            return response()->json([
                'success' => false,
                'message' => "Error initiating payment: " . $e->getMessage()
            ], 500);
        }
    }
}
