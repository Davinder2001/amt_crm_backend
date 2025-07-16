<?php

namespace PhonePe\payments\v2\models\response;

use JsonMapper;

class RefundStatusCheckResponse implements \JsonSerializable
{
	public string $originalMerchantOrderId;
	public $amount;
	public string $state;
	public $paymentDetails;

	/**
	 * @param $httpResponse
	 * @return RefundStatusCheckResponse
	 * @throws \JsonMapper_Exception
	 */
	public static function getInstance($httpResponse): RefundStatusCheckResponse
	{
		$mapper = new JsonMapper();
		$refundStatusCheckResponse = $mapper->map($httpResponse, new RefundStatusCheckResponse());
		return $refundStatusCheckResponse;

	}
	public function getOriginalMerchantOrderId(): string
	{
		return $this->originalMerchantOrderId;
	}

	/**
	 * @return mixed
	 */
	public function getAmount()
	{
		return $this->amount;
	}

	/**
	 * @return string
	 */
	public function getState(): string
	{
		return $this->state;
	}

	/**
	 * @return mixed
	 */
	public function getPaymentDetails()
	{
		return $this->paymentDetails;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		return get_object_vars($this);
	}
}