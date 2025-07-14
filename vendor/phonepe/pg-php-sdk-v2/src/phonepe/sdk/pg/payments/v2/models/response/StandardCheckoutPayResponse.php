<?php

namespace PhonePe\payments\v2\models\response;

use PhonePe\payments\v2\models\response\PaymentInstrument\Pa;

class StandardCheckoutPayResponse implements \JsonSerializable
{

	private string $orderId;
	private string $state;
	private string $redirectUrl;
	private int $expiresAt;

	/**
	 * @param string $orderId
	 * @param string $state
	 * @param string $redirectUrl
	 * @param int $expiresAt
	 */
	public function __construct($orderId, $state, $redirectUrl, $expiresAt){
		$this->orderId = $orderId;
		$this->state = $state;
		$this->redirectUrl = $redirectUrl;
		$this->expiresAt = $expiresAt;
	}

	/**
	 * @return string
	 */
	public function getOrderId()
	{
		return $this->orderId;
	}

	/**
	 * @return string
	 */
	public function getState()
	{
		return $this->state;
	}

	/**
	 * @return string
	 */
	public function getRedirectUrl()
	{
		return $this->redirectUrl;
	}

	/**
	 * @return int
	 */
	public function getExpireAt()
	{
		return $this->expiresAt;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(): array
	{
		return get_object_vars($this);
	}
}