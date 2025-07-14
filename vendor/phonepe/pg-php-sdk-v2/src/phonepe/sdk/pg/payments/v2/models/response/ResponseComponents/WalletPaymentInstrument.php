<?php

namespace PhonePe\payments\v2\models\response\ResponseComponents;

use PhonePe\common\configs\Instruments;

class WalletPaymentInstrument extends Instrument
{
	public string $walletId;

	/**
	 * @param string $walletId
	 */
	public function __construct(string $walletId)
	{
		$this->type = Instruments::WALLET;
		$this->walletId = $walletId;
	}

	public function getWalletId(): string
	{
		return $this->walletId;
	}


}