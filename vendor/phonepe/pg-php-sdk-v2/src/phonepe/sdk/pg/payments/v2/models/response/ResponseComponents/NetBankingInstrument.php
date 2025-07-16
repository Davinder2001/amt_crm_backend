<?php

namespace PhonePe\payments\v2\models\response\ResponseComponents;

use PhonePe\common\configs\Instruments;

class NetBankingInstrument extends Instrument
{
	public string $bankTransactionId;
	public string $bankId;
	public string $arn;
	public string $brn;

	/**
	 * @param string $bankTransactionId
	 * @param string $bankId
	 * @param string $arn
	 * @param string $brn
	 */
	public function __construct(string $bankTransactionId, string $bankId, string $arn, string $brn)
	{
		$this->type = Instruments::NET_BANKING;
		$this->bankTransactionId = $bankTransactionId;
		$this->bankId = $bankId;
		$this->arn = $arn;
		$this->brn = $brn;
	}

	public function getBankTransactionId(): string
	{
		return $this->bankTransactionId;
	}

	public function getBankId(): string
	{
		return $this->bankId;
	}

	public function getArn(): string
	{
		return $this->arn;
	}

	public function getBrn(): string
	{
		return $this->brn;
	}


}