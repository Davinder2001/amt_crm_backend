<?php

namespace Unit\payments\v2\standardCheckout;

use PhonePe\common\configs\MerchantConfig;
use PhonePe\payments\v2\models\request\StandardCheckoutRefundRequest;
use PhonePe\payments\v2\models\response\RefundStatusCheckResponse;
use PhonePe\payments\v2\models\response\StandardCheckoutRefundResponse;
use PHPUnit\Framework\TestCase;
use PhonePe\common\utils\CurlHttpClient;
use PhonePe\common\configs\Config;
use PhonePe\common\tokenHandler\TokenService;
use PhonePe\payments\v2\models\request\StandardCheckoutPayRequest;
use PhonePe\payments\v2\models\response\StandardCheckoutPayResponse;
use PhonePe\payments\v2\standardCheckout\StandardCheckoutClient;

class StandardCheckoutClientTest extends TestCase
{
	private $client;

	public function setUp(): void
	{
		$this->mockedConfig = $this->createMock(MerchantConfig::class);
		$this->mockedConfig->method('getClientId')->willReturn('clientId');
		$this->mockedConfig->method('getClientVersion')->willReturn(1);
		$this->mockedConfig->method('getClientSecret')->willReturn('clientSecret');
		$httpClient = new CurlHttpClient();
		$tokenService = new TokenService($this->mockedConfig, 'STAGE', $httpClient);

		$this->client =  StandardCheckoutClient::getInstance('test_client_id', 1, 'test_client_secret', 'STAGE', false, $httpClient);
	}

	public function testGetInstance()
	{
		$client = StandardCheckoutClient::getInstance('test_client_id', 1, 'test_client_secret', 'STAGE');
		$this->assertInstanceOf(StandardCheckoutClient::class, $client);
	}

	public function testPay()
	{
		$payRequest = new StandardCheckoutPayRequest();
		$payRequest->amount = 10;
		$payRequest->buyer = 'test_buyer';
		$payRequest->orderId = 'test_order_id';
		$payRequest->merchantId = 'test_merchant_id';
		// ...

		// Send the pay request using the client and confirm if the response is an instance of StandardCheckoutPayResponse
		$response = $this->client->pay($payRequest);
		$this->assertInstanceOf(StandardCheckoutPayResponse::class, $response);
	}

	public function testGetOrderStatus()
	{
		// Get the order status for a test merchant order ID and confirm if the response is an instance of StatusCheckResponse
		$response = $this->client->getOrderStatus('test_merchant_order_id');
		$this->assertInstanceOf(StatusCheckResponse::class, $response);
	}

	public function testRefund()
	{
		$refundRequest = new StandardCheckoutRefundRequest();
		$refundRequest->amount = 10;
		$refundRequest->orderId = 'test_order_id';
		// ...

		// Send the refund request using the client and confirm if the response is an instance of StandardCheckoutRefundResponse
		$response = $this->client->refund($refundRequest);
		$this->assertInstanceOf(StandardCheckoutRefundResponse::class, $response);
	}

	public function testGetRefundStatus()
	{
		// Get the refund status for a test merchant refund ID and confirm if the response is an instance of RefundStatusCheckResponse
		$response = $this->client->getRefundStatus('test_merchant_refund_id');
		$this->assertInstanceOf(RefundStatusCheckResponse::class, $response);
	}

}