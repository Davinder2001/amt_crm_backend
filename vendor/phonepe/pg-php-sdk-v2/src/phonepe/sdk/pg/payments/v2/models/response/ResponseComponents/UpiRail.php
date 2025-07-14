<?php

namespace PhonePe\payments\v2\models\response\ResponseComponents;

use PhonePe\common\configs\Rails;

class UpiRail extends Rail
{
	public string $utr;
	public string $upiTransactionId;
	public string $vpa;

	/**
	 * @param string $utr
	 * @param string $upiTransactionId
	 * @param string $vpa
	 */
	public function __construct(string $utr, string $upiTransactionId, string $vpa)
	{
		$this->type = Rails::UPI;
		$this->utr = $utr;
		$this->upiTransactionId = $upiTransactionId;
		$this->vpa = $vpa;
	}

	public function getUtr(): string
	{
		return $this->utr;
	}

	public function getUpiTransactionId(): string
	{
		return $this->upiTransactionId;
	}

	public function getVpa(): string
	{
		return $this->vpa;
	}

}