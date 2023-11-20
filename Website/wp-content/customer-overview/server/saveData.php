<?php
include_once("common.php");

if(isset($_POST["saveData"])){
    $saveData = $_POST["saveData"];
    $data = $_POST["data"];
    saveData($saveData, $data);
    echo "OK";
}

