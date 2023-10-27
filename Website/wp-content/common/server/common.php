<?php

class Helper{

	static function logMsg($msg){
		//echo $msg;
		//file_put_contents( $_SERVER["DOCUMENT_ROOT"] . "/wp-content/nns.log", $msg . "\r\n", FILE_APPEND);
	}

	static function ToJson(){
		return json_encode($this);
	}

	static function ToObject($json){
		return json_decode($json);	
	}

	static function IsElementor(){
		return isset($_GET["post"]);
	}

	static function getHash($value){
		return hash("sha256", $value);
	}

}
