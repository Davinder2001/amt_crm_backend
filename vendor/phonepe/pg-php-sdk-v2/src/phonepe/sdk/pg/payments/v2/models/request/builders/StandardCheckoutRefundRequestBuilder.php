<?php

namespace PhonePe\payments\v2\models\request\builders;

use PhonePe\payments\v2\models\request\StandardCheckoutRefundRequest;

class StandardCheckoutRefundRequestBuilder
{
	private string $merchantRefundId;
	private string $originalMerchantOrderId;
	private $amount;

	public  function  merchantRefundId($merchantRefundId): StandardCheckoutRefundRequestBuilder
	{
		$this->merchantRefundId = $merchantRefundId;
		return $this;
	}

	public function originalMerchantOrderId($originalMerchantOrderId): StandardCheckoutRefundRequestBuilder
	{
		$this->originalMerchantOrderId =$originalMerchantOrderId;
		return $this;
	}

	public function amount($amount): StandardCheckoutRefundRequestBuilder
	{
		$this->amount = $amount;
		return $this;
	}

	public static function builder(): StandardCheckoutRefundRequestBuilder
	{
		return new StandardCheckoutRefundRequestBuilder();
	}

	public function build(): StandardCheckoutRefundRequest
	{
		return new StandardCheckoutRefundRequest(
			$this->merchantRefundId,
			$this->originalMerchantOrderId,
			$this->amount
		);
	}
}