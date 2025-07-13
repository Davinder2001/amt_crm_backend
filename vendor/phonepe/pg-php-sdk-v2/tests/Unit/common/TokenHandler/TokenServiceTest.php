<?php

namespace Unit\common\TokenHandler;

use JsonMapper;
use Mockery;
use PhonePe\common\configs\MerchantConfig;
use PhonePe\common\tokenHandler\OAuthToken;
use PhonePe\common\tokenHandler\TokenService;
use PhonePe\common\utils\CurlHttpClient;
use PhonePe\common\utils\HttpResponse;
use PHPUnit\Framework\TestCase;

class TokenServiceTest extends TestCase
{
	private $mockedConfig;

	protected function setUp(): void
	{
		parent::setUp();
		$this->mockedConfig = $this->createMock(MerchantConfig::class);
		$this->mockedConfig->method('getClientId')->willReturn('clientId');
		$this->mockedConfig->method('getClientVersion')->willReturn(1);
		$this->mockedConfig->method('getClientSecret')->willReturn('clientSecret');
	}


	public function testPrepareOAuthTokenReturnsInstanceOfOAuthToken()
	{
		// Arrange
		$access_token = 'access_token';
		$encrypted_access_token = 'encrypted_access_token';
		$refresh_token = 'refresh_token';
		$expires_in = 3600;
		$issued_at = time();
		$expires_at = $issued_at + $expires_in;
		$session_expires_at = $issued_at + 7200;
		$token_type = 'bearer';

		$tokenData = $this->getMockBuilder(OAuthToken::class)
			->setConstructorArgs([$access_token, $encrypted_access_token, $refresh_token, $expires_in, $issued_at, $expires_at, $session_expires_at, $token_type])
			->getMock();

		$mockedResponse = new HttpResponse(200, ['Content-Type' => 'application/json'], json_encode($tokenData));
		$mockedResponse->setResponse(json_encode($tokenData));

		$mockClass = Mockery::mock(CurlHttpClient::class);
		$mockClass->allows('postRequest')->andReturns($mockedResponse);
		$service = new TokenService($this->mockedConfig, 'STAGE', $mockClass);
		// Act
		$result = $service->prepareOAuthToken();
		// Assert
		$this->assertInstanceOf(OAuthToken::class, $result);
	}
}