<?php

namespace PhonePe\common\utils;

use PhonePe\common\exceptions\PhonePeException;

class CurlHttpClient
{
	/**
	 * @desc Helper function to send a post request
	 * @param string $url
	 * @param string $body
	 * @param $headers
	 * @return mixed
	 * @throws PhonePeException
	 */
	public static function postRequest($url, $body, $headers)
	{
		$headers_array = [];
		foreach ($headers as $key => $value) {
			$headers_array[] = $key . ":" . $value;
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER , $headers_array);

		$httpResponse = new HttpResponse();

		$response = curl_exec($ch);

		$httpResponse->setResponse($response);

		$responseHeaders = curl_getinfo($ch);
		$httpResponse->setHeaders($responseHeaders);

		$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($httpStatus == 200)
			return $httpResponse;
		else {
			$responseArray = json_decode($response, true);
			$data = $responseArray['data'] ?? $responseArray['context'];
			$code = $responseArray['code'] ?? $responseArray['errorCode'];
			throw new PhonePeException($responseArray['message'], $httpStatus, $code, $data);
		}
	}

	/**
	 * @desc Helper function to send a get request
	 * @param string $url
	 * @param string $headers
	 * @return mixed
	 * @throws PhonePeException
	 */
	public static function getRequest($url, $headers)
	{
		$headers_array = [];
		foreach ($headers as $key => $value) {
			$headers_array[] = $key . ":" . $value;
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_array);

		$httpResponse = new HttpResponse();

		$response = curl_exec($ch);
		$httpResponse->setResponse($response);

		$responseHeaders = curl_getinfo($ch);
		$httpResponse->setHeaders($responseHeaders);

		$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($httpStatus == 200)

			return $httpResponse;
		else {
			$responseArray = json_decode($response, true);
			$data = $responseArray['data'] ?? $responseArray['context'];
			$code = $responseArray['code'] ?? $responseArray['errorCode'];
			throw new PhonePeException($responseArray['message'], $httpStatus, $code, $data);
		}
	}

}