<?php

namespace Unit\common\utils;

use PhonePe\common\utils\CurlHttpClient;
use PhonePe\common\utils\HttpResponse;
use PHPUnit\Framework\TestCase;

class RequestGeneratorTest extends TestCase
{
	public function testPostRequest(): void
	{
		// Set up test data
		$url = 'https://example.com';
		$body = '{"foo":"bar"}';
		$headers = ['Content-Type: application/json'];

		$response = CurlHttpClient::postRequest($url, $body, $headers);

		$this->assertInstanceOf(HttpResponse::class, $response);
		$this->assertEquals(200, $response->getHeaders()['http_code']);
	}

	public function testGetRequest(): void
	{
		$url = 'https://example.com';
		$headers = ['Authorization: Bearer token'];

		$response = CurlHttpClient::getRequest($url, $headers);

		$this->assertInstanceOf(HttpResponse::class, $response);
		$this->assertEquals(200, $response->getHeaders()['http_code']);
	}
}