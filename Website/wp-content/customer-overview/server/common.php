<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php');

$config = null;

function loadConfig(){
	if(!is_dir(getDataPath())){
		mkdir(getDataPath());
	}

	$backUpPath = getDataPath() . "/backup"; 

	if(!is_dir($backUpPath)){
		mkdir($backUpPath);
	}

	$configPath = getDataPath() . "/config.json";
	
	if(file_exists($configPath)){
		$config = file_get_contents($configPath);
	}
	else{
		$config = '{ "columns": [] }';
	}
	$ret = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $config), false);
	return $ret;
}

function loadData($configName){
	if(!is_dir(getDataPath())){
		mkdir(getDataPath());
	}

	$backUpPath = getDataPath() . "/backup"; 

	if(!is_dir($backUpPath)){
		mkdir($backUpPath);
	}

	$filePath = getDataPath() . "/" . $configName;

	if(file_exists($filePath)){
		$data = file_get_contents($filePath);
	}
	else{
		$data = file_get_contents(getDataRoot() . "/default-" . $configName);
	}

	return $data;
}

function startApplication($isGUI = true) {

	$root = $_SERVER['DOCUMENT_ROOT'] . "/wp-content";
	require_once("{$root}/common/server/curl_api_cached.php");
	require_once("{$root}/common/server/curl_api.php");
	require_once("{$root}/common/server/common.php");
	require_once(__DIR__ . "/repositories.php");

	if($isGUI){
		require_once("{$root}/common/server/templates.php");
		?>
		<script src="/wp-content/customer-overview/client/common.js"></script>
		<?
		require_once("{$root}/common/server/css.php");
	}
}

function getDataPath(){
	$apiKey = getApiKey();
	return getDataRoot() . "/{$apiKey}";
}

function getDataRoot(){
	return $_SERVER['DOCUMENT_ROOT']. "/wp-content/data-customer-overview";
}

function getApiKey(){
	if(isset($_GET["demo"]) || current_user_can( 'administrator' )){
		return "demo";
	}

	$apiKey = get_user_meta( get_current_user_id(), 'apiKey', true );

	return $apiKey;
}

function isAdmin($user = null){
	if($user == null){
		return current_user_can( 'coadmin' ) || current_user_can( 'administrator' ) ;
	}

	return user_can($user, 'coadmin') || user_can($user, 'administrator');
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
		CURLOPT_POSTFIELDS => array("token" => $token, 'apiKey' => getApiKey()),
		CURLOPT_HTTPHEADER => array(
		'Content-Type: multipart/form-data'
		)
	));

	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}

function sanitizeColumnName($colName){
	$colName = strtolower($colName);
	return $colName;
}

function saveData($fileName, $data){
	if(!is_dir(getDataPath())){
		mkdir(getDataPath());
	}

	$backUpPath = getDataPath() . "/backup"; 

	if(!is_dir($backUpPath)){
		mkdir($backUpPath);
	}

	$path = getDataPath() . "/{$fileName}";

	copy($path, $backUpPath . "/{$fileName}");
	
	file_put_contents($path, str_replace('\"', '"', $data));
	return true;
}

