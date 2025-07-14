<?php

namespace PhonePe\payments\v2\models\response;

use JsonMapper;
use PhonePe\common\configs\Constants;
use PhonePe\payments\v2\models\response\ResponseComponents\AccountInstrument;
use PhonePe\payments\v2\models\response\ResponseComponents\CreditCardInstrument;
use PhonePe\payments\v2\models\response\ResponseComponents\DebitCardInstrument;
use PhonePe\payments\v2\models\response\ResponseComponents\MetaInfo;
use PhonePe\payments\v2\models\response\ResponseComponents\NetBankingInstrument;
use PhonePe\payments\v2\models\response\ResponseComponents\PaymentDetail;
use PhonePe\payments\v2\models\response\ResponseComponents\PgRail;
use PhonePe\payments\v2\models\response\ResponseComponents\UpiRail;
use PhpParser\Node\Expr\FuncCall;

class StatusCheckResponse implements \JsonSerializable
{
	public string $orderId;
	public string $state;
	public int $amount;
	public int $expireAt;
	public MetaInfo $metaInfo;
	public string $errorCode;
	public string $detailedErrorCode;
	public $paymentDetails;

	public static function getInstance($httpResponse): StatusCheckResponse
	{
		$mapper = new JsonMapper();
		$statusCheckResponse = $mapper->map($httpResponse, new StatusCheckResponse());
		return $statusCheckResponse;

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
	 * @return int
	 */
	public function getAmount()
	{
		return $this->amount;
	}

	/**
	 * @return int
	 */
	public function getExpireAt()
	{
		return $this->expireAt;
	}

	/**
	 * @return MetaInfo
	 */
	public function getMetaInfo()
	{
		return $this->metaInfo;
	}

	/**
	 * @return array
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
