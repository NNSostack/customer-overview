<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php');

class Base{
	private ?string $type = null;
	private ?string $typeName = null;

	function __construct($type, $typeName) {
		$this->type = $type;
		$this->typeName = $typeName;
    }

	
	
}