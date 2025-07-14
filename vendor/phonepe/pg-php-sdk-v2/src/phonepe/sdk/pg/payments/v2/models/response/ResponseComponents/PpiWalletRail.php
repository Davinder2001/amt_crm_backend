<?php

namespace PhonePe\payments\v2\models\response\ResponseComponents;

use PhonePe\common\configs\Rails;

class PpiWalletRail extends Rail
{
	public function __construct()
	{
		$this->type = Rails::PPI_WALLET;
	}
}