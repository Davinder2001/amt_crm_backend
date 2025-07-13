<?php

namespace Unit\common\utils;

use PhonePe\common\utils\HttpRequest;
use PHPUnit\Framework\TestCase;

class HttpRequestTest extends TestCase
{
	public function testBuildPostRequest(): void
	{
		$payload = 'foo';
		$path = '/api';
		$baseUrl = 'https://example.com';
		$additionalHeaders = ['Authorization: Bearer token'];

		$request = HttpRequest::buildPostRequest($payload, $path, $baseUrl, $additionalHeaders);

		$this->assertInstanceOf(HttpRequest::class, $request);
		$this->assertEquals('https://example.com/api', $request->getUrl());
		$this->assertEquals('foo', $request->getPayload());
		$this->assertEquals(['Authorization: Bearer token'], $request->getHeaders());
	}

	public function testBuildGetRequest(): void
	{
		$path = '/';
		$baseUrl = 'https://example.com';
		$additionalHeaders = ['Authorization: Bearer token'];

		$request = HttpRequest::buildGetRequest($path, $baseUrl, $additionalHeaders);

		$this->assertInstanceOf(HttpRequest::class, $request);
		$this->assertEquals('https://example.com/', $request->getUrl());
		$this->assertEquals(['Authorization: Bearer token'], $request->getHeaders());
	}
}