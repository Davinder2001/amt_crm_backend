<?php
namespace PhonePe;

use PhonePe\common\configs\Constants;

class Env
{
	const STAGE = "STAGE";
	const UAT = "UAT";
	const PRODUCTION = "PRODUCTION";

	/**
	 * @param string $env
	 * @return string
	 */
	public static function getBaseUrl($env): string
	{
		switch ($env) {
			case Env::PRODUCTION:
				return Constants::BASE_URL_PROD;
			case Env::UAT:
				return Constants::BASE_URL_UAT;
			case Env::STAGE:
				return Constants::BASE_URL_STAGE;
			default: return "Invalid Environment";
		}
	}

	/**
	 * @param string $env
	 * @return string
	 */
	public static function getEventsUrl($env): string
	{
		switch ($env) {
			case Env::PRODUCTION:
				return Constants::BASE_URL_PROD_EVENTS;
			case Env::UAT:
				return Constants::BASE_URL_UAT_EVENTS;
			case Env::STAGE:
				return Constants::BASE_URL_STAGE_EVENTS;
			default:
				return "Invalid Environment";
		}
	}

	public static function getBaseUrlForOAuth($env): string
	{
		switch ($env) {
			case Env::PRODUCTION:
				return Constants::BASE_URL_PROD_FOR_OAUTH;
			case Env::UAT:
				return Constants::BASE_URL_UAT_FOR_OAUTH;
			case Env::STAGE:
				return Constants::BASE_URL_STAGE_FOR_OAUTH;
			default:
				return "Invalid Environment";
		}
	}
}