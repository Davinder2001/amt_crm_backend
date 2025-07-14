<?php

namespace PhonePe\payments\v2\models\response\ResponseComponents;

use PhonePe\common\configs\Instruments;

class AccountInstrument extends Instrument
{
	public string $accountType;
	public string $maskedAccountNumber;
	public string $ifsc;
	public string $accountHolderName;

	/**
	 * @param string $accountType
	 * @param string $maskedAccountNumber
	 * @param string $ifsc
	 * @param string $accountHolderName
	 */
	public function __construct(string $accountType, string $maskedAccountNumber, string $ifsc, string $accountHolderName)
	{
		$this->type = Instruments::ACCOUNT;
		$this->accountType = $accountType;
		$this->maskedAccountNumber = $maskedAccountNumber;
		$this->ifsc = $ifsc;
		$this->accountHolderName = $accountHolderName;
	}

	public function getAccountType(): string
	{
		return $this->accountType;
	}

	public function getMaskedAccountNumber(): string
	{
		return $this->maskedAccountNumber;
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