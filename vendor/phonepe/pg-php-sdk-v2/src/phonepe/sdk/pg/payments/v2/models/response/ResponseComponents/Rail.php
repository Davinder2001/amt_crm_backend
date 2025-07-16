<?php

namespace PhonePe\payments\v2\models\response\ResponseComponents;

class Rail
{
	protected string $type;

	public function getType(): string
	{
		return $this->type;
	}

}