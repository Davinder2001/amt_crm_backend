<?php

namespace PhonePe\common\exceptions;

use Exception;
use Throwable;

/**
 * PhonePe Generic Exception
 */
class PhonePeException extends Exception
{
	public $httpStatusCode;
	public $code;
	public $message;
	public $data;

	/**
	 * @param $message
	 * @param mixed $httpStatusCode optional
	 * @param mixed $code optional
	 * @param mixed $data optional
	 * @param Throwable|null $previous optional
	 */
	public function __construct( $message = '', $httpStatusCode = 0 , $code = 0, $data = null, Throwable $previous = null)
	{
		parent::__construct($message, $httpStatusCode, $previous);
		$this->httpStatusCode = $httpStatusCode;
		$this->message = $message;
		$this->code = $code;
		$this->data = $data;
	}

	public function getHttpStatusCode(): mixed
	{
		return $this->httpStatusCode;
	}

	/**
	 * @return mixed
	 */

	public function getData(): mixed
	{
		return $this->data;
	}

}