<?php

namespace PhonePe\payments\v2\models\response\ResponseComponents;

use PhonePe\common\configs\Rails;

class PpiEgvRail extends Rail
{
	public function __construct()
	{
		$this->type = Rails::PPI_EGV;
	}
}