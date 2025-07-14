<?php

namespace PhonePe\payments\v2\models\request;

class StandardCheckoutRefundRequest implements \JsonSerializable
{
	private string $merchantRefundId;
	private string $originalMerchantOrderId;
	private $amount;

	/**
	 * @param string $merchantRefundId
	 * @param string $originalMerchantOrderId
	 * @param $amount
	 */
	public function __construct(string $merchantRefundId, string $originalMerchantOrderId, $amount)
	{
		$this->merchantRefundId = $merchantRefundId;
		$this->originalMerchantOrderId = $originalMerchantOrderId;
		$this->amount = $amount;
	}

	public function getMerchantRefundId(): string
	{
		return $this->merchantRefundId;
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
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		return get_object_vars($this);
	}

}