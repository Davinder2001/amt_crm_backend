<?php

namespace Unit;

use PhonePe\common\configs\Constants;
use PhonePe\Env;
use PHPUnit\Framework\TestCase;

class EnvTest extends TestCase
{
	public function testGetBaseUrl(): void
	{
		$this->assertEquals(Constants::BASE_URL_PROD, Env::getBaseUrl(Env::PRODUCTION));
		$this->assertEquals(Constants::BASE_URL_UAT, Env::getBaseUrl(Env::UAT));
		$this->assertEquals(Constants::BASE_URL_STAGE, Env::getBaseUrl(Env::STAGE));
		$this->assertEquals('Invalid Environment', Env::getBaseUrl('test'));
	}

	public function testGetEventUrl(): void
	{
		$this->assertEquals(Constants::BASE_URL_PROD, Env::getEventUrl(Env::PRODUCTION));
		$this->assertEquals(Constants::BASE_URL_UAT, Env::getEventUrl(Env::UAT));
		$this->assertEquals(Constants::BASE_URL_STAGE, Env::getEventUrl(Env::STAGE));
		$this->assertEquals('Invalid Environment', Env::getEventUrl('test'));
	}
}