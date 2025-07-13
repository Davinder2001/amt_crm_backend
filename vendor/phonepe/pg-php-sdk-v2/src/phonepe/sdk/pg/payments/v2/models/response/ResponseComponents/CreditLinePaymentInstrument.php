<?php

namespace PhonePe\payments\v2\models\response\ResponseComponents;

class CreditLinePaymentInstrument extends Instrument
{
	public string	$ifsc;
	public string $accountHolderName;

	/**
	 * @param string $ifsc
	 * @param string $accountHolderName
	 */
	public function __construct(string $ifsc, string $accountHolderName)
	{
		$this->ifsc = $ifsc;
		$this->accountHolderName = $accountHolderName;
	}

	public function getIfsc(): string
	{
		return $this->ifsc;
	}

	public function getAccountHolderName(): string
	{
		return $this->accountHolderName;
	}

}