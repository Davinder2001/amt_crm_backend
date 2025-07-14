<?php

namespace PhonePe\common\eventHandler;

class Event
{
	public string $merchantOrderId;
	public string $eventName;
	public string $eventTime;
	public array $data;

	public function __construct()
	{
	}

	public function setMerchantOrderId(string $merchantOrderId): void
	{
		$this->merchantOrderId = $merchantOrderId;
	}

	public function setEventName(string $eventName): void
	{
		$this->eventName = $eventName;
	}

	public function setEventTime(string $eventTime): void
	{
		$this->eventTime = $eventTime;
	}

	public function setData(array $data): void
	{
		$this->data = $data;
	}



}