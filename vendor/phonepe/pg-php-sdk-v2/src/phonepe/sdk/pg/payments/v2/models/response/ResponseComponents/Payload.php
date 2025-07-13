<?php

namespace PhonePe\payments\v2\models\response\ResponseComponents;

class Payload
{
	public $merchantId;
	public $merchantOrderId;
	public $orderId;
	public $state;
	public $amount;
	public $expireAt;
	public $paymentDetails;

	/**
	 * @return mixed
	 */
	public function getMerchantId()
	{
		return $this->merchantId;
	}

	/**
	 * @return mixed
	 */
	public function getMerchantOrderId()
	{
		return $this->merchantOrderId;
	}

	/**
	 * @return mixed
	 */
	public function getOrderId()
	{
		return $this->orderId;
	}

	/**
	 * @return mixed
	 */
	public function getState()
	{
		return $this->state;
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
	public function getExpireAt()
	{
		return $this->expireAt;
	}

	/**
	 * @return mixed
	 */
	public function getPaymentDetails()
	{
		return $this->paymentDetails;
	}


}