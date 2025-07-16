<?php

namespace PhonePe\payments\v2\models\response\ResponseComponents;

use AllowDynamicProperties;

#[AllowDynamicProperties] class PaymentDetail
{
	public string $paymentMode;
	public $timestamp;
	public int $amount;
	public string $transactionId;
	public string $state;
	public string $errorCode;
	public string $detailedErrorCode;
	public Rail $rail;
	public Instrument $instrument;
	public $splitInstruments = array();

	public function getPaymentMode(): string
	{
		return $this->paymentMode;
	}

	/**
	 * @return mixed
	 */
	public function getTimestamp()
	{
		return $this->timestamp;
	}

	public function getAmount(): int
	{
		return $this->amount;
	}

	public function getTransactionId(): string
	{
		return $this->transactionId;
	}

	public function getState(): string
	{
		return $this->state;
	}

	public function getErrorCode(): string
	{
		return $this->errorCode;
	}

	public function getDetailedErrorCode(): string
	{
		return $this->detailedErrorCode;
	}

	public function getRail(): Rail
	{
		return $this->rail;
	}

	public function getInstrument(): Instrument
	{
		return $this->instrument;
	}

	public function getSplitInstruments(): array
	{
		return $this->splitInstruments;
	}



}