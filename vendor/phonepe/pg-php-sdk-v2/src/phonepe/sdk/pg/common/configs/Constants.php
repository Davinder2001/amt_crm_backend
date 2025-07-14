<?php

namespace PhonePe\common\configs;

class Constants
{
	# URLs and Endpoints
	const BASE_URL_PROD = "https://api.phonepe.com/apis/pg";
	const BASE_URL_STAGE = "https://api-testing.phonepe.com/apis/b2b-pg"; # TODO: Remove stage URls before package release for php SDK
//const BASE_URL_STAGE = "https://hermes.nixy.stg-drove.phonepe.nb6/";
	const BASE_URL_UAT = "https://api-preprod.phonepe.com/apis/pg";
	const BASE_URL_PROD_FOR_OAUTH = "https://api.phonepe.com/apis";
	const BASE_URL_STAGE_FOR_OAUTH = "https://api-testing.phonepe.com/apis";
	const BASE_URL_UAT_FOR_OAUTH = "https://api-preprod.phonepe.com/apis";
	const BASE_URL_PROD_EVENTS = "https://api.phonepe.com/apis/pg-ingestion";
	const BASE_URL_STAGE_EVENTS = "https://api-testing.phonepe.com/apis/pg-ingestion";
	const BASE_URL_UAT_EVENTS = "https://api-preprod.phonepe.com/apis/pg-ingestion";
	const OAUTH_ENDPOINT = "/identity-manager/v1/oauth/token";
	const ORDER_STATUS_ENDPOINT = "/checkout/v2/order";
	const REFUND_ENDPOINT = "/payments/v2/refund";

	# OAuth
	const CLIENT_CREDENTIALS = "client_credentials";
	const O_BEARER = "O-Bearer";

	# Other
	const BACKEND_SDK = "BACKEND_SDK";
	const EVENTS_CLIENT_VERSION = "PHP_SDK:2.0.0";

	const EVENTS_ENDPOINT = "/client/v1/backend/events/batch";

}