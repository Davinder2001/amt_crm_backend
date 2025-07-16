<?php

namespace PhonePe\payments\v2\models\response\ResponseComponents;

use PhonePe\common\configs\Instruments;

class EgvPaymentInstrument extends Instrument
{
	public string $cardNumber;
	public string $programId;

	/**
	 * @param string $cardNumber
	 * @param string $programId
	 */
	public function __construct(string $cardNumber, string $programId)
	{
		$this->type = Instruments::EGV;
		$this->cardNumber = $cardNumber;
		$this->programId = $programId;
	}

	public function getCardNumber(): string
	{
		return $this->cardNumber;
	}

	public function getProgramId(): string
	{
		return $this->programId;
	}


}