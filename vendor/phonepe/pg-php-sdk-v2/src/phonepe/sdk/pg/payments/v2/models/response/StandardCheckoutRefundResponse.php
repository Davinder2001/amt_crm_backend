<?php

namespace PhonePe\payments\v2\models\response;

class StandardCheckoutRefundResponse implements \JsonSerializable
{
	private string $refundId;
	private $amount;
	private $state;

	/**
	 * @param string $refundId
	 * @param $amount
	 * @param $state
	 */
	public function __construct(string $refundId, $amount, $state)
	{
		$this->refundId = $refundId;
		$this->amount = $amount;
		$this->state = $state;
	}

	public function getRefundId(): string
	{
		return $this->refundId;
	}

	/**
	 * @return mixed
	 */
	public function getAmount()
	{
		return $this->amount;
	}

	/**
	 * @return mixed
	 */
	public function getState()
	{
		return $this->state;
	}


	public function jsonSerialize(): array
	{
		return get_object_vars($this);
	}
}