<?php

namespace PhonePe\payments\v2\models\request\builders;

use PhonePe\payments\v2\models\request\StandardCheckoutPayRequest;
use PhonePe\payments\v2\models\response\ResponseComponents\MetaInfo;
use PhonePe\payments\v2\standardCheckout\StandardCheckoutConstants;

class StandardCheckoutPayRequestBuilder
{

	private string $merchantOrderId;
	private int $amount;
	private string $message;
	private string $redirectUrl;

	private $metaInfo;

	/**
	 * @param string $merchantOrderId
	 * @return $this
	 */
	public function merchantOrderId($merchantOrderId): StandardCheckoutPayRequestBuilder
	{
		$this->merchantOrderId = $merchantOrderId;
		return $this;
	}

	/**
	 * @param int $amount
	 * @return $this
	 */
	public function amount($amount): StandardCheckoutPayRequestBuilder
	{
		$this->amount = $amount;
		return $this;
	}

	/**
	 * @param string $message
	 * @return $this
	 */
	public function message($message): StandardCheckoutPayRequestBuilder
	{
		$this->message = $message;
		return $this;
	}

	/**
	 * @param string $redirectUrl
	 * @return $this
	 */
	public function redirectUrl($redirectUrl): StandardCheckoutPayRequestBuilder
	{
		$this->redirectUrl = $redirectUrl;
		return $this;
	}

	public function udf1($udf1): StandardCheckoutPayRequestBuilder{
		$this->metaInfo['udf1'] = $udf1;
		return $this;
	}

	public function udf2($udf2): StandardCheckoutPayRequestBuilder{
		$this->metaInfo['udf2'] = $udf2;
		return $this;
	}

	public function udf3($udf3): StandardCheckoutPayRequestBuilder{
		$this->metaInfo['udf3'] = $udf3;
		return $this;
	}

	public function udf4($udf4): StandardCheckoutPayRequestBuilder{
		$this->metaInfo['udf4'] = $udf4;
		return $this;
	}

	public function udf5($udf5): StandardCheckoutPayRequestBuilder{
		$this->metaInfo['udf5'] = $udf5;
		return $this;
	}

	/**
	 * @return StandardCheckoutPayRequestBuilder
	 */
	public static function builder(): StandardCheckoutPayRequestBuilder
	{
		return new StandardCheckoutPayRequestBuilder();
	}

	/**
	 * @return StandardCheckoutPayRequest
	 */
	public function build(): StandardCheckoutPayRequest
	{
		$paymentFlow = array();
		$paymentFlow["type"] = StandardCheckoutConstants::STANDARD_CHECKOUT_PAYMENT_FLOW_TYPE;
		$paymentFlow["message"] = $this->message;
		$paymentFlow["merchantUrls"]["redirectUrl"] = $this->redirectUrl;

		return new StandardCheckoutPayRequest(
			$this->merchantOrderId,
			$this->amount,
			$this->metaInfo,
			$paymentFlow
		);
	}



}