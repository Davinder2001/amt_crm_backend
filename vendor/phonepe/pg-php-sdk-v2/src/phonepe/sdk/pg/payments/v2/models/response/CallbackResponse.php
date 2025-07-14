<?php

namespace PhonePe\payments\v2\models\response;

use JsonMapper;
use PhonePe\payments\v2\models\response\ResponseComponents\Payload;

class CallbackResponse implements \JsonSerializable
{
	public $type;
	public Payload $payload;

	public function jsonSerialize(): array
	{
		return get_object_vars($this);
	}

	public static function getInstance($body): CallbackResponse
	{
		$obj = json_decode($body);
		$mapper = new JsonMapper();
		$callbackResponse = $mapper->map($obj, new CallbackResponse());
		return $callbackResponse;

	}

	/**
	 * @return mixed
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return Payload
	 */
	public function getPayload(): Payload
	{
		return $this->payload;
	}


}