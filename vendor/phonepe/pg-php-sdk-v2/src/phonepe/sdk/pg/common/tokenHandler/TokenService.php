<?php

namespace PhonePe\common\tokenHandler;

use JsonMapper;
use PhonePe\common\configs\Constants;
use PhonePe\common\configs\Headers;
use PhonePe\common\configs\MerchantConfig;
use PhonePe\common\exceptions\PhonePeException;
use PhonePe\common\utils\CurlHttpClient;
use PhonePe\Env;
use PhonePe\payments\v2\models\response\StatusCheckResponse;

class TokenService
{
	/**
	 * @var MerchantConfig
	 */
	private $merchantConfig;
	/**
	 * @var string $env
	 */
	private $env;
	/**
	 * @var OAuthToken
	 */
	private $cachedTokenData;
	/**
	 * @var $httpClient
	 */
	private $httpClient;

	private $eventPublisher;
	/**
	 * @param MerchantConfig $merchantConfig
	 * @param string $env
	 */
	public function __construct(MerchantConfig $merchantConfig, $env, $httpClient)
	{
		$this->merchantConfig = $merchantConfig;
		$this->env = $env;
		$this->httpClient = $httpClient;
	}

	/**
	 * @return String
	 */
	public function getAuthHeaders(): String
	{
		return Constants::O_BEARER . " " . $this->setAuthTokenInHeaders();
	}

	/**
	 * @return String
	 */
	public function setAuthTokenInHeaders(): String
	{
		if($this->cachedTokenData == null) {
			$this->cachedTokenData = $this->prepareOAuthToken();
			return $this->cachedTokenData->getAccessToken();
		}

		$ttl = $this->cachedTokenData->getExpiresAt() - $this->cachedTokenData->getIssuedAt();
		$timeRemainingForTokenExpiry = $this->cachedTokenData->getExpiresAt() - time();

		if($timeRemainingForTokenExpiry < ($ttl/2)  ){
			$this->cachedTokenData = $this->prepareOAuthToken();
		}
		return $this->cachedTokenData->getAccessToken();
	}

	/**
	 * @return void
	 * @throws PhonePeException
	 */
	public function refreshToken(){
		$this->cachedTokenData = $this->prepareOAuthToken();
	}

	/**
	 * @return OAuthToken
	 */
	public function prepareOAuthToken(){
		try {
			$httpResponseObj = $this->httpClient::postRequest(Env::getBaseUrlForOAuth($this->env) . Constants::OAUTH_ENDPOINT, $this->preparePayload(), $this->prepareHeaders());
			$httpResponse = json_decode($httpResponseObj->getResponse());
			$mapper = new JsonMapper();
			$oAuthtoken = $mapper->map($httpResponse, new OAuthToken());
			return $oAuthtoken;
		}catch (PhonePeException $phonePeException){
			throw $phonePeException;
		}
	}
	/**
	 * @return string[]
	 */
	private function prepareHeaders(){
		return ["Content-type: " . Headers::X_WWW_FORM_URLENCODED];
	}

	/**
	 * @return string
	 */
	private function preparePayload(){
		$data = array(
			'client_id' => $this->merchantConfig->getClientId(),
			'client_version' => $this->merchantConfig->getClientVersion(),
			'client_secret' => $this->merchantConfig->getClientSecret(),
			'grant_type' => Constants::CLIENT_CREDENTIALS
		);
		return http_build_query($data);
	}

	/**
	 * @return OAuthToken
	 */
	public function getCachedTokenData(): OAuthToken
	{
		return $this->cachedTokenData;
	}

	/**
	 * @param OAuthToken $cachedTokenData
	 * @return void
	 */
	public function setCachedTokenData(OAuthToken $cachedTokenData)
	{
		$this->cachedTokenData = $cachedTokenData;
	}

}