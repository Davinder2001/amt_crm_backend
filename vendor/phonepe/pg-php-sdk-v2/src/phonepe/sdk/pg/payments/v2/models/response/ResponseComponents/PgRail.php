<?php

namespace PhonePe\payments\v2\models\response\ResponseComponents;

use PhonePe\common\configs\Rails;

class PgRail extends Rail
{
	public string $transactionId;
	public string $authorizationCode;
	public string $serviceTransactionId;

	/**
	 * @param string $transactionId
	 * @param string $authorizationCode
	 * @param string $serviceTransactionId
	 */
	public function __construct(string $transactionId, string $authorizationCode, string $serviceTransactionId)
	{
		$this->type = Rails::PG;
		$this->transactionId = $transactionId;
		$this->authorizationCode = $authorizationCode;
		$this->serviceTransactionId = $serviceTransactionId;
	}

	public function getTransactionId(): string
	{
		return $this->transactionId;
	}

	public function getAuthorizationCode(): string
	{
		return $this->authorizationCode;
	}

	public function getServiceTransactionId(): string
	{
		return $this->serviceTransactionId;
	}
	
}