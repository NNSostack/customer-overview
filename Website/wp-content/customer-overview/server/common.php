<?php

function startApplication($isGUI = true) {
	$root = $_SERVER['DOCUMENT_ROOT'] . "/wp-content";
	require_once("{$root}/common/server/curl_api_cached.php");
	require_once("{$root}/common/server/curl_api.php");
	require_once("{$root}/common/server/common.php");
	require_once(__DIR__ . "/repositories.php");

	if($isGUI){
		require_once("{$root}/common/server/templates.php");
		require_once("{$root}/common/server/css.php");
	}
}

function getDataPath(){
	$apiKey = getApiKey();
	return $_SERVER['DOCUMENT_ROOT']. "/wp-content/data-customer-overview/{$apiKey}";
}

function getApiKey(){
	return "Economic_Vendor";
}

function createCustomer($token){
	$curl = curl_init();
	$url = 'https://handle-data.integrations.online-it-support.dk/handleData.ashx';

	curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_BINARYTRANSFER => true,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_SSL_VERIFYHOST => 0,
		CURLOPT_SSL_VERIFYPEER => 0,
		CURLOPT_VERBOSE => true,
		CURLOPT_POSTFIELDS => array("token" => $token, 'ApiKey' => getApiKey()),
		CURLOPT_HTTPHEADER => array(
		'Content-Type: multipart/form-data'
		)
	));

	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}

