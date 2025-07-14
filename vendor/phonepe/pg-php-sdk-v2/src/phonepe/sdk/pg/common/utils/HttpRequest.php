<?php

namespace PhonePe\common\utils;

use PhonePe\common\configs\Headers;
class HttpRequest
{

	public array $headers;
	public string $url;
	public string $payload;

	/**
	 * @return mixed
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

	/**
	 * @return mixed
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @return mixed
	 */
	public function getPayload()
	{
		return $this->payload;
	}

	/**
	 * @param string $payload
	 * @param string $path
	 * @param string $baseUrl
	 * @param $headers
	 * @return HttpRequest
	 */
	public static function buildPostRequest($payload, $path, $baseUrl, $headers): HttpRequest
	{
		$request = new HttpRequest();
		$request->url = ($baseUrl . $path);
		$request->payload = $payload;
		$request->headers = $headers;
		return $request;
	}

	/**
	 * @param string $path
	 * @param string $baseUrl
	 * @param array $headers
	 * @return HttpRequest
	 */
	public static function buildGetRequest($path, $baseUrl, $headers): HttpRequest
	{
		$request = new HttpRequest();
		$request->url = ($baseUrl . $path);
		$request->headers = $headers;
		return $request;
	}


}