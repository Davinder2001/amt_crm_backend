<?php

namespace PhonePe\common\configs;

class MerchantConfig
{

	/**
	 * @desc The Client Id provided by PhonePe team
	 * @var string
	 */
	private $clientId;

	/**
	 * @desc The Client Version provided by PhonePe team
	 * @var int
	 */
	private $clientVersion;

	/**
	 * @desc The Client Secret provided by PhonePe team
	 * @var string
	 */
	private $clientSecret;

	/**
	 * @param string $clientId
	 * @param int $clientVersion
	 * @param string $clientSecret
	 */

	public function __construct($clientId, $clientVersion, $clientSecret)
	{
		$this->clientId = $clientId;
		$this->clientVersion = $clientVersion;
		$this->clientSecret = $clientSecret;
	}

	/**
	 * @return string
	 */
	public function getClientId()
	{
		return $this->clientId;
	}

	/**
	 * @param string $clientId
	 */
	public function setClientId($clientId)
	{
		$this->clientId = $clientId;
	}

	/**
	 * @return int
	 */
	public function getClientVersion()
	{
		return $this->clientVersion;
	}

	/**
	 * @param int $clientVersion
	 */
	public function setClientVersion($clientVersion)
	{
		$this->clientVersion = $clientVersion;
	}

	/**
	 * @return string
	 */
	public function getClientSecret()
	{
		return $this->clientSecret;
	}

	/**
	 * @param string $clientSecret
	 */
	public function setClientSecret($clientSecret)
	{
		$this->clientSecret = $clientSecret;
	}

}