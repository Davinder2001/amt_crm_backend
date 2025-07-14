<?php

namespace PhonePe\payments\v2\models\response\ResponseComponents;

class Instrument
{
	public $type;

	/**
	 * @return mixed
	 */
	public function getType()
	{
		return $this->type;
	}

}