<?php

class Repositories{
	public static function GetHttpClient($apiKey, $cacheTimeoutInHours){
		return new Curl_Cached(getHash($apiKey), $cacheTimeoutInHours);
	}
}